<?php

use App\Enums\TransactionType;
use App\Exceptions\InvalidTransactionException;
use App\Exceptions\TransactionAlreadyReversedException;
use App\Models\LedgerEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\ReverseTransactionService;

function createWalletForReversal(User $user, string $key, int $balance = 0): Wallet
{
    return Wallet::query()->create([
        'user_id' => $user->id,
        'wallet_transaction_key' => $key,
        'balance' => $balance,
    ]);
}

function createTransferForReversal(User $sourceUser, User $destinationUser, int $amount = 250): array
{
    $sourceWallet = createWalletForReversal($sourceUser, 'source-wallet', 1000);
    $destinationWallet = createWalletForReversal($destinationUser, 'destination-wallet', 0);

    $transfer = Transaction::query()->create([
        'user_id' => $sourceUser->id,
        'type' => TransactionType::TRANSFER,
        'amount' => $amount,
        'idempotency_key' => 'transfer-for-reversal',
    ]);

    LedgerEntry::query()->create([
        'transaction_id' => $transfer->id,
        'wallet_id' => $sourceWallet->id,
        'amount' => -$amount,
    ]);
    LedgerEntry::query()->create([
        'transaction_id' => $transfer->id,
        'wallet_id' => $destinationWallet->id,
        'amount' => $amount,
    ]);

    $sourceWallet->update(['balance' => 750]);
    $destinationWallet->update(['balance' => $amount]);

    return [$sourceWallet, $destinationWallet, $transfer];
}

test('reverte uma transferência e cria uma transação de reversão com ledgers inversos', function () {
    $sourceUser = User::factory()->create();
    $destinationUser = User::factory()->create();
    [$sourceWallet, $destinationWallet, $transfer] = createTransferForReversal($sourceUser, $destinationUser, 250);

    $reversal = app(ReverseTransactionService::class)->execute(
        $sourceUser->id,
        $transfer->id,
        'reversal-001',
    );

    expect($reversal->type)->toBe(TransactionType::REVERSAL)
        ->and($reversal->reversal_of_id)->toBe($transfer->id)
        ->and($sourceWallet->fresh()->balance)->toBe(1000)
        ->and($destinationWallet->fresh()->balance)->toBe(0)
        ->and($reversal->ledgerEntries)->toHaveCount(2)
        ->and($reversal->ledgerEntries->pluck('amount')->sort()->values()->all())
        ->toBe([-250, 250])
        ->and(Transaction::query()->count())->toBe(2);
});

test('retry idempotente retorna a reversão criada e não duplica registros', function () {
    $sourceUser = User::factory()->create();
    $destinationUser = User::factory()->create();
    [$sourceWallet, $destinationWallet, $transfer] = createTransferForReversal($sourceUser, $destinationUser, 250);
    $service = app(ReverseTransactionService::class);

    $first = $service->execute($sourceUser->id, $transfer->id, 'reversal-retry');
    $second = $service->execute($sourceUser->id, $transfer->id, 'reversal-retry');

    expect($first->id)->toBe($second->id)
        ->and($sourceWallet->fresh()->balance)->toBe(1000)
        ->and($destinationWallet->fresh()->balance)->toBe(0)
        ->and(Transaction::query()->count())->toBe(2)
        ->and(LedgerEntry::query()->count())->toBe(4);
});

test('rejeita segunda reversão e reversão de reversão', function () {
    $sourceUser = User::factory()->create();
    $destinationUser = User::factory()->create();
    [, , $transfer] = createTransferForReversal($sourceUser, $destinationUser, 250);
    $service = app(ReverseTransactionService::class);

    $service->execute($sourceUser->id, $transfer->id, 'reversal-second');

    expect(fn () => $service->execute($sourceUser->id, $transfer->id, 'reversal-second-2'))
        ->toThrow(TransactionAlreadyReversedException::class);

    $reversal = Transaction::query()->where('user_id', $sourceUser->id)->where('type', TransactionType::REVERSAL)->first();

    expect(fn () => $service->execute($sourceUser->id, $reversal->id, 'reversal-of-reversal'))
        ->toThrow(InvalidTransactionException::class, 'Não é possível reverter uma reversão.');
});

test('rejeita transação inexistente e usuário sem relação com a transação', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $destinationUser = User::factory()->create();
    [, , $transfer] = createTransferForReversal($user, $destinationUser, 250);
    $service = app(ReverseTransactionService::class);

    expect(fn () => $service->execute($user->id, 9999, 'reversal-missing'))
        ->toThrow(InvalidTransactionException::class, 'A transação não foi encontrada.');

    expect(fn () => $service->execute($otherUser->id, $transfer->id, 'reversal-other-user'))
        ->toThrow(InvalidTransactionException::class, 'A transação não pertence ao usuário.');
});
