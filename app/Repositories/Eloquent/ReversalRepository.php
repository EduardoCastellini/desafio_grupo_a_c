<?php

namespace App\Repositories\Eloquent;

use App\Enums\TransactionType;
use App\Exceptions\InvalidTransactionException;
use App\Exceptions\TransactionAlreadyReversedException;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Contracts\ReversalRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class ReversalRepository implements ReversalRepositoryInterface
{
    public function reverseTransaction(
        int $userId,
        int $transactionId,
        string $idempotencyKey,
    ): Transaction {
        return DB::transaction(function () use ($userId, $transactionId, $idempotencyKey): Transaction {
            $existingTransaction = Transaction::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingTransaction !== null) {
                $this->ensureCompatibleReversalRetry(
                    $existingTransaction,
                    $transactionId,
                );

                return $existingTransaction;
            }

            $originalTransaction = Transaction::query()
                ->whereKey($transactionId)
                ->lockForUpdate()
                ->first();

            if ($originalTransaction === null) {
                throw new InvalidTransactionException(
                    'A transação não foi encontrada.'
                );
            }

            if ($originalTransaction->user_id !== $userId) {
                throw new InvalidTransactionException(
                    'A transação não pertence ao usuário.'
                );
            }

            if ($originalTransaction->type === TransactionType::REVERSAL) {
                throw new InvalidTransactionException(
                    'Não é possível reverter uma reversão.'
                );
            }

            if ($originalTransaction->reversal()->exists()) {
                throw new TransactionAlreadyReversedException;
            }

            $ledgerEntries = $originalTransaction->ledgerEntries()->get();
            $walletIds = $ledgerEntries
                ->pluck('wallet_id')
                ->unique()
                ->sort()
                ->values();

            $lockedWallets = Wallet::query()
                ->whereIn('id', $walletIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($ledgerEntries as $ledgerEntry) {
                $wallet = $lockedWallets->get($ledgerEntry->wallet_id);

                if ($wallet === null) {
                    throw new InvalidTransactionException(
                        'A carteira relacionada à transação não foi encontrada.'
                    );
                }

                $reversalAmount = -$ledgerEntry->amount;

                if ($reversalAmount < 0 && $wallet->balance < abs($reversalAmount)) {
                    throw new InvalidTransactionException(
                        'Saldo insuficiente para aplicar a reversão.'
                    );
                }
            }

            $reversalTransaction = Transaction::query()->create([
                'user_id' => $userId,
                'type' => TransactionType::REVERSAL,
                'amount' => $originalTransaction->amount,
                'idempotency_key' => $idempotencyKey,
                'reversal_of_id' => $originalTransaction->id,
            ]);

            foreach ($ledgerEntries as $ledgerEntry) {
                $reversedAmount = -$ledgerEntry->amount;
                $wallet = $lockedWallets->get($ledgerEntry->wallet_id);

                LedgerEntry::query()->create([
                    'transaction_id' => $reversalTransaction->id,
                    'wallet_id' => $ledgerEntry->wallet_id,
                    'amount' => $reversedAmount,
                ]);

                $wallet->decrement('balance', $ledgerEntry->amount);
            }

            return $reversalTransaction;
        });
    }

    private function ensureCompatibleReversalRetry(
        Transaction $transaction,
        int $transactionId,
    ): void {
        if (
            $transaction->type !== TransactionType::REVERSAL
            || $transaction->reversal_of_id !== $transactionId
        ) {
            throw new InvalidTransactionException(
                'A chave de idempotência já foi utilizada em outra operação.'
            );
        }
    }
}
