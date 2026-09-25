<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;

interface TransferRepositoryInterface
{
    public function createTransfer(
        int $sourceWalletId,
        int $destinationWalletId,
        int $amount,
        int $userId,
        string $idempotencyKey,
    ): Transaction;
}
