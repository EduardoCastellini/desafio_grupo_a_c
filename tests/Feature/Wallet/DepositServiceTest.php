<?php

use App\Enums\TransactionType;
use App\Exceptions\InvalidTransactionException;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\DepositService;

test('cria um deposito valido e atualiza o saldo da carteira', function () {
    $user = User::factory()->create();
    Wallet::query()->create([
        'user_id' => $user->id,
        'wallet_transaction_key' => 'wallet-'.$user->id,
        'balance' => 0,
    ]);

    $service = app(DepositService::class);

    $transaction = $service->execute($user->id, 1500, 'deposit-001');

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->type)->toBe(TransactionType::DEPOSIT)
        ->and($transaction->amount)->toBe(1500)
        ->and($transaction->idempotency_key)->toBe('deposit-001');

    $wallet = $user->fresh()->wallet;

    expect($wallet->balance)->toBe(1500)
        ->and(Transaction::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->first()->amount)->toBe(1500);
});

test('retry idempotente retorna a mesma transacao sem duplicar saldo', function () {
    $user = User::factory()->create();
    Wallet::query()->create([
        'user_id' => $user->id,
        'wallet_transaction_key' => 'wallet-'.$user->id,
        'balance' => 0,
    ]);

    $service = app(DepositService::class);

    $first = $service->execute($user->id, 200, 'deposit-duplicate');
    $second = $service->execute($user->id, 200, 'deposit-duplicate');

    expect($first->id)->toBe($second->id)
        ->and($user->fresh()->wallet->balance)->toBe(200)
        ->and(Transaction::query()->count())->toBe(1)
        ->and(LedgerEntry::query()->count())->toBe(1);
});

test('reuso indevido da chave de idempotencia e rejeitado', function () {
    $user = User::factory()->create();
    Wallet::query()->create([
        'user_id' => $user->id,
        'wallet_transaction_key' => 'wallet-'.$user->id,
        'balance' => 0,
    ]);

    $service = app(DepositService::class);

    $service->execute($user->id, 100, 'deposit-mismatch');

    expect(fn () => $service->execute($user->id, 250, 'deposit-mismatch'))
        ->toThrow(InvalidTransactionException::class, 'A chave de idempotência já foi utilizada em outra operação.');
});

test('valor zero ou negativo e rejeitado', function () {
    $user = User::factory()->create();
    Wallet::query()->create([
        'user_id' => $user->id,
        'wallet_transaction_key' => 'wallet-'.$user->id,
        'balance' => 0,
    ]);

    $service = app(DepositService::class);

    expect(fn () => $service->execute($user->id, 0, 'deposit-zero'))
        ->toThrow(InvalidTransactionException::class, 'O valor do depósito deve ser maior que zero.');

    expect(fn () => $service->execute($user->id, -50, 'deposit-negative'))
        ->toThrow(InvalidTransactionException::class, 'O valor do depósito deve ser maior que zero.');
});
