<?php

namespace App\Services\Wallet;

use App\Exceptions\InvalidTransactionException;
use App\Models\Transaction;
use App\Repositories\Contracts\DepositRepositoryInterface;

class DepositService
{
    public function __construct(
        private DepositRepositoryInterface $repository,
    ) {}

    public function execute(
        int $userId,
        int $amount,
        string $idempotencyKey,
    ): Transaction {
        if ($amount <= 0) {
            throw new InvalidTransactionException(
                'O valor do depósito deve ser maior que zero.'
            );
        }

        return $this->repository->createDeposit(
            userId: $userId,
            amount: $amount,
            idempotencyKey: $idempotencyKey,
        );
    }
}
