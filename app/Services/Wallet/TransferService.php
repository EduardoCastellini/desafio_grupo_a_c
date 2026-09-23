<?php

namespace App\Services\Wallet;

use App\Exceptions\InvalidTransactionException;
use App\Models\Transaction;
use App\Repositories\Contracts\TransferRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;

class TransferService
{
    public function __construct(
        private TransferRepositoryInterface $repository,
        private WalletRepositoryInterface $walletRepository,
    ) {}

    public function execute(
        int $userId,
        int $amount,
        string $destinationWalletTransactionKey,
        string $idempotencyKey,
    ): Transaction {
        if ($amount <= 0) {
            throw new InvalidTransactionException(
                'O valor da transferência deve ser maior que zero.'
            );
        }

        $sourceWallet = $this->walletRepository->findByUserId($userId);
        $destinationWallet = $this->walletRepository->findByTransactionKey(
            $destinationWalletTransactionKey,
        );

        if ($sourceWallet === null || $destinationWallet === null) {
            throw new InvalidTransactionException(
                'A carteira de origem ou destino não foi encontrada.'
            );
        }

        if ($sourceWallet->id === $destinationWallet->id) {
            throw new InvalidTransactionException(
                'Não é possível transferir para a própria carteira.'
            );
        }

        return $this->repository->createTransfer(
            sourceWalletId: $sourceWallet->id,
            destinationWalletId: $destinationWallet->id,
            amount: $amount,
            userId: $userId,
            idempotencyKey: $idempotencyKey,
        );
    }
}
