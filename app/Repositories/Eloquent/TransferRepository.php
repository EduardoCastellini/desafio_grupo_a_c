<?php

namespace App\Repositories\Eloquent;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransactionException;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Contracts\TransferRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TransferRepository implements TransferRepositoryInterface
{
    public function createTransfer(
        int $sourceWalletId,
        int $destinationWalletId,
        int $amount,
        int $userId,
        string $idempotencyKey,
    ): Transaction {
        return DB::transaction(function () use (
            $sourceWalletId,
            $destinationWalletId,
            $amount,
            $userId,
            $idempotencyKey,
        ): Transaction {
            $existingTransaction = Transaction::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingTransaction !== null) {
                $this->ensureCompatibleRetry(
                    $existingTransaction,
                    $sourceWalletId,
                    $destinationWalletId,
                    $amount,
                );

                return $existingTransaction;
            }

            $walletIds = collect([$sourceWalletId, $destinationWalletId])
                ->sort()
                ->values();

            $lockedWallets = Wallet::query()
                ->whereIn('id', $walletIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($lockedWallets->count() !== 2) {
                throw new InvalidTransactionException(
                    'A carteira de origem ou destino não foi encontrada.'
                );
            }

            $existingTransaction = Transaction::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingTransaction !== null) {
                $this->ensureCompatibleRetry(
                    $existingTransaction,
                    $sourceWalletId,
                    $destinationWalletId,
                    $amount,
                );

                return $existingTransaction;
            }

            $sourceWallet = $lockedWallets->get($sourceWalletId);
            $destinationWallet = $lockedWallets->get($destinationWalletId);

            if ($sourceWallet->user_id !== $userId) {
                throw new InvalidTransactionException(
                    'A carteira de origem não pertence ao usuário.'
                );
            }

            if ($sourceWallet->balance < $amount) {
                Log::warning('wallet transfer insufficient balance', [
                    'user_id' => $userId,
                    'idempotency_key' => $idempotencyKey,
                    'operation_type' => 'transfer',
                    'amount' => $amount,
                    'source_wallet_id' => $sourceWalletId,
                    'destination_wallet_id' => $destinationWalletId,
                    'source_balance' => $sourceWallet->balance,
                ]);

                throw new InsufficientBalanceException;
            }

            $transaction = Transaction::query()->create([
                'user_id' => $userId,
                'type' => TransactionType::TRANSFER,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);

            LedgerEntry::query()->create([
                'transaction_id' => $transaction->id,
                'wallet_id' => $sourceWallet->id,
                'amount' => -$amount,
            ]);
            LedgerEntry::query()->create([
                'transaction_id' => $transaction->id,
                'wallet_id' => $destinationWallet->id,
                'amount' => $amount,
            ]);

            $sourceWallet->decrement('balance', $amount);
            $destinationWallet->increment('balance', $amount);

            Log::info('wallet transfer created', [
                'user_id' => $userId,
                'transaction_id' => $transaction->id,
                'idempotency_key' => $idempotencyKey,
                'operation_type' => 'transfer',
                'amount' => $amount,
                'source_wallet_id' => $sourceWalletId,
                'destination_wallet_id' => $destinationWalletId,
            ]);

            return $transaction;
        });
    }

    private function ensureCompatibleRetry(
        Transaction $transaction,
        int $sourceWalletId,
        int $destinationWalletId,
        int $amount,
    ): void {
        $ledgerEntries = $transaction->ledgerEntries()->get();
        $hasSourceEntry = $ledgerEntries
            ->where('wallet_id', $sourceWalletId)
            ->where('amount', -$amount)
            ->isNotEmpty();
        $hasDestinationEntry = $ledgerEntries
            ->where('wallet_id', $destinationWalletId)
            ->where('amount', $amount)
            ->isNotEmpty();

        if (
            $transaction->type !== TransactionType::TRANSFER
            || $transaction->amount !== $amount
            || $ledgerEntries->count() !== 2
            || ! $hasSourceEntry
            || ! $hasDestinationEntry
        ) {
            Log::warning('wallet transfer idempotency violation', [
                'user_id' => $transaction->user_id,
                'transaction_id' => $transaction->id,
                'idempotency_key' => $transaction->idempotency_key,
                'operation_type' => 'transfer',
                'amount' => $amount,
                'source_wallet_id' => $sourceWalletId,
                'destination_wallet_id' => $destinationWalletId,
            ]);

            throw new InvalidTransactionException(
                'A chave de idempotência já foi utilizada em outra operação.'
            );
        }
    }
}
