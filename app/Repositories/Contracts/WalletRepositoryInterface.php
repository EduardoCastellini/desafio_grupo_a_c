<?php

namespace App\Repositories\Contracts;

use App\Models\Wallet;

interface WalletRepositoryInterface
{
    public function findById(int $id): ?Wallet;

    public function findByTransactionKey(
        string $transactionKey
    ): ?Wallet;

    public function findByIdForUpdate(int $id): ?Wallet;

    public function save(Wallet $wallet): Wallet;
}