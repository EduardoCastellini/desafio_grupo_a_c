<?php

namespace App\Repositories\Eloquent;

use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;

final class WalletRepository implements WalletRepositoryInterface
{
    public function findById(int $id): ?Wallet
    {
        return Wallet::query()->find($id);
    }

    public function findByUserId(int $userId): ?Wallet
    {
        return Wallet::query()->where('user_id', $userId)->first();
    }

    public function findByTransactionKey(
        string $transactionKey
    ): ?Wallet {
        return Wallet::query()
            ->where('wallet_transaction_key', $transactionKey)
            ->first();
    }

    public function findByIdForUpdate(int $id): ?Wallet
    {
        return Wallet::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    public function save(Wallet $wallet): Wallet
    {
        $wallet->save();

        return $wallet;
    }
}
