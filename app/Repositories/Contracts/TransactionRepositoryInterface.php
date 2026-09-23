<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;

interface TransactionRepositoryInterface
{
    public function create(array $data): Transaction;

    public function findById(int $id): ?Transaction;

    public function findByIdempotencyKey(
        int $userId,
        string $idempotencyKey
    ): ?Transaction;
}