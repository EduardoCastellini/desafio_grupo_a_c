<?php

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;

final class TransactionRepository implements TransactionRepositoryInterface
{
    public function create(array $data): Transaction
    {
        return Transaction::query()->create($data);
    }

    public function findById(int $id): ?Transaction
    {
        return Transaction::query()->find($id);
    }

    public function findByIdempotencyKey(
        int $userId,
        string $idempotencyKey
    ): ?Transaction {
        return Transaction::query()
            ->where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }
}