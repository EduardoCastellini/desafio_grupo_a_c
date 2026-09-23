<?php

namespace App\Repositories\Eloquent;

use App\Enums\TransactionType;
use App\Exceptions\InvalidTransactionException;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Contracts\DepositRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class DepositRepository implements DepositRepositoryInterface
{
    public function createDeposit(
        int $userId,
        int $amount,
        string $idempotencyKey,
    ): Transaction {
        return DB::transaction(function () use (
            $userId,
            $amount,
            $idempotencyKey,
        ) {
            $wallet = Wallet::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $existingTransaction = Transaction::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingTransaction) {
                if (
                    $existingTransaction->type !== TransactionType::DEPOSIT ||
                    $existingTransaction->amount !== $amount
                ) {
                    throw new InvalidTransactionException(
                        'A chave de idempotência já foi utilizada em outra operação.'
                    );
                }

                return $existingTransaction;
            }

            $transaction = Transaction::query()->create([
                'user_id' => $userId,
                'type' => TransactionType::DEPOSIT,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);

            LedgerEntry::query()->create([
                'transaction_id' => $transaction->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
            ]);

            $wallet->increment('balance', $amount);

            return $transaction;
        });
    }
}