<?php

use App\Models\User;
use App\Models\Wallet;
use App\Repositories\Contracts\UserRepositoryInterface;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    $this->assertDatabaseHas('wallets', [
        'user_id' => $user->id,
        'balance' => 0,
    ]);

    expect($user->wallet->wallet_transaction_key)->not->toBeEmpty();
});

test('user creation rolls back when wallet creation fails', function () {
    Wallet::creating(function (): void {
        throw new RuntimeException('Wallet creation failed.');
    });

    try {
        expect(fn () => app(UserRepositoryInterface::class)->createWithWallet([
            'name' => 'Test User',
            'email' => 'rollback@example.com',
            'password' => 'password',
        ]))->toThrow(RuntimeException::class);
    } finally {
        Wallet::flushEventListeners();
    }

    expect(User::query()->where('email', 'rollback@example.com')->exists())
        ->toBeFalse();
});
