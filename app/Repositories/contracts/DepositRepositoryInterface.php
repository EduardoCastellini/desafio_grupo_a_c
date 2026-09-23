<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;

interface DepositRepositoryInterface
{
    public function createDeposit(
        int $walletId,
        int $userId,
        int $amount,
        string $idempotencyKey,
    ): Transaction;
}