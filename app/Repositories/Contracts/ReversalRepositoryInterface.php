<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;

interface ReversalRepositoryInterface
{
    public function reverseTransaction(
        int $userId,
        int $transactionId,
        string $idempotencyKey,
    ): Transaction;
}
