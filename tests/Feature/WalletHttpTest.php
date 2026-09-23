<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Str;

// beforeEach(function () {
//     $this->user = User::factory()->create();
// });

test('rotas financeiras exigem autenticação e validam valores monetários', function () {
    $user = User::factory()->create();
    $depositRoute = route('wallet.deposit');
    $transferRoute = route('wallet.transfer');

    $this->post($depositRoute, [
        'amount' => '10,00',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertRedirect(route('login'));

    $this->actingAs($user);

    $wallet = Wallet::query()->create([
        'user_id' => $user->id,
        'wallet_transaction_key' => 'wallet-' . $user->id,
        'balance' => 0,
    ]);

    $this->post($depositRoute, [
        'amount' => '10,00',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertRedirect(route('wallet.show'))
        ->assertSessionHasNoErrors();

    $this->assertSame(1000, $wallet->fresh()->balance);

    $destinationUser = User::factory()->create();
    $destinationWallet = Wallet::query()->create([
        'user_id' => $destinationUser->id,
        'wallet_transaction_key' => 'wallet-' . $destinationUser->id,
        'balance' => 0,
    ]);

    $this->post($transferRoute, [
        'wallet_transaction_key' => $destinationWallet->wallet_transaction_key,
        'amount' => '5.50',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertRedirect(route('wallet.show'));

    $this->assertSame(450, $wallet->fresh()->balance);
    $this->assertSame(550, $destinationWallet->fresh()->balance);

    $this->post($depositRoute, [
        'amount' => '0',
        'idempotency_key' => (string) Str::uuid(),
    ])->assertSessionHasErrors(['amount']);
});

test('reversão via HTTP rejeita transação já revertida e usa a chave de idempotência', function () {
     $user = User::factory()->create();
    $this->actingAs($user);

    $wallet = Wallet::query()->create([
        'user_id' => $user->id,
        'wallet_transaction_key' => 'wallet-'.$user->id,
        'balance' => 0,
    ]);

    $transaction = Transaction::query()->create([
        'user_id' => $user->id,
        'type' => 'deposit',
        'amount' => 1000,
        'idempotency_key' => (string) Str::uuid(),
    ]);

    $wallet->increment('balance', 1000);

    $this->post(route('wallet.transactions.reverse', ['transaction' => $transaction->id]), [
        'idempotency_key' => (string) Str::uuid(),
    ])->assertRedirect(route('wallet.show'));

    $this->post(route('wallet.transactions.reverse', ['transaction' => $transaction->id]), [
        'idempotency_key' => (string) Str::uuid(),
    ])->assertStatus(409);
});
