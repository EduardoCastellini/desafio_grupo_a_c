<?php

namespace App\Services\Wallet;

use App\Enums\TransactionType;
use App\Exceptions\InvalidTransactionException;
use App\Models\Transaction;
use App\Repositories\Contracts\ReversalRepositoryInterface;

class ReverseTransactionService
{
    public function __construct(
        private ReversalRepositoryInterface $repository,
    ) {}

    public function execute(
        int $userId,
        int $transactionId,
        string $idempotencyKey,
    ): Transaction {
        $transaction = Transaction::query()->find($transactionId);

        if ($transaction === null) {
            throw new InvalidTransactionException(
                'A transação não foi encontrada.'
            );
        }

        if ($transaction->user_id !== $userId) {
            throw new InvalidTransactionException(
                'A transação não pertence ao usuário.'
            );
        }

        if ($transaction->type === TransactionType::REVERSAL) {
            throw new InvalidTransactionException(
                'Não é possível reverter uma reversão.'
            );
        }

        return $this->repository->reverseTransaction(
            userId: $userId,
            transactionId: $transactionId,
            idempotencyKey: $idempotencyKey,
        );
    }
}
