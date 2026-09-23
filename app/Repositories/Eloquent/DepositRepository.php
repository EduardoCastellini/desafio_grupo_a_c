<?php

namespace App\Repositories\Eloquent;

use App\Enums\TransactionType;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Contracts\DepositRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class DepositRepository implements DepositRepositoryInterface
{
    public function createDeposit(
        int $walletId,
        int $userId,
        int $amount,
        string $idempotencyKey,
    ): Transaction {
        return DB::transaction(function () use (
            $walletId,
            $userId,
            $amount,
            $idempotencyKey,
        ) {
            $wallet = Wallet::query()
                ->whereKey($walletId)
                ->lockForUpdate()
                ->firstOrFail();

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

            $wallet->balance += $amount;
            $wallet->save();

            return $transaction;
        });
    }
}