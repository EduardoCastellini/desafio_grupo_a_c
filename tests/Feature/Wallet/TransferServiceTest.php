<?php

use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransactionException;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\TransferService;

function createWalletForTransfer(User $user, string $key, int $balance = 0): Wallet
{
    return Wallet::query()->create([
        'user_id' => $user->id,
        'wallet_transaction_key' => $key,
        'balance' => $balance,
    ]);
}

test('transfere saldo e cria os dois lançamentos', function () {
    $sourceUser = User::factory()->create();
    $destinationUser = User::factory()->create();
    $sourceWallet = createWalletForTransfer($sourceUser, 'source-wallet', 1500);
    $destinationWallet = createWalletForTransfer($destinationUser, 'destination-wallet');

    $transaction = app(TransferService::class)->execute(
        $sourceUser->id,
        500,
        $destinationWallet->wallet_transaction_key,
        'transfer-001',
    );

    expect($transaction->type)->toBe(TransactionType::TRANSFER)
        ->and($sourceWallet->fresh()->balance)->toBe(1000)
        ->and($destinationWallet->fresh()->balance)->toBe(500)
        ->and($transaction->ledgerEntries)->toHaveCount(2)
        ->and($transaction->ledgerEntries->pluck('amount')->sort()->values()->all())
        ->toBe([-500, 500]);
});

test('retry idempotente não duplica transferência', function () {
    $sourceUser = User::factory()->create();
    $destinationUser = User::factory()->create();
    $sourceWallet = createWalletForTransfer($sourceUser, 'source-wallet', 1000);
    $destinationWallet = createWalletForTransfer($destinationUser, 'destination-wallet');
    $service = app(TransferService::class);

    $first = $service->execute($sourceUser->id, 400, $destinationWallet->wallet_transaction_key, 'transfer-retry');
    $second = $service->execute($sourceUser->id, 400, $destinationWallet->wallet_transaction_key, 'transfer-retry');

    expect($first->id)->toBe($second->id)
        ->and($sourceWallet->fresh()->balance)->toBe(600)
        ->and($destinationWallet->fresh()->balance)->toBe(400)
        ->and(Transaction::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->count())->toBe(2);
});

test('rejeita saldo insuficiente, destino inexistente e a própria carteira', function () {
    $user = User::factory()->create();
    $wallet = createWalletForTransfer($user, 'source-wallet', 100);
    $service = app(TransferService::class);

    expect(fn () => $service->execute($user->id, 101, 'missing-wallet', 'transfer-insufficient'))
        ->toThrow(InvalidTransactionException::class);

    $destinationUser = User::factory()->create();
    $destinationWallet = createWalletForTransfer($destinationUser, 'destination-wallet');

    expect(fn () => $service->execute($user->id, 101, $destinationWallet->wallet_transaction_key, 'transfer-insufficient'))
        ->toThrow(InsufficientBalanceException::class);

    expect(fn () => $service->execute($user->id, 1, $wallet->wallet_transaction_key, 'transfer-self'))
        ->toThrow(InvalidTransactionException::class);
});

test('rejeita reuso da chave com parâmetros incompatíveis', function () {
    $sourceUser = User::factory()->create();
    $destinationUser = User::factory()->create();
    createWalletForTransfer($sourceUser, 'source-wallet', 1000);
    $destinationWallet = createWalletForTransfer($destinationUser, 'destination-wallet');
    $service = app(TransferService::class);

    $service->execute($sourceUser->id, 100, $destinationWallet->wallet_transaction_key, 'transfer-mismatch');

    expect(fn () => $service->execute($sourceUser->id, 200, $destinationWallet->wallet_transaction_key, 'transfer-mismatch'))
        ->toThrow(InvalidTransactionException::class, 'A chave de idempotência já foi utilizada em outra operação.');
});
