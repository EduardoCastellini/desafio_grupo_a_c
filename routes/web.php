<?php

use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletDepositController;
use App\Http\Controllers\WalletReversalController;
use App\Http\Controllers\WalletTransferController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();
        $wallet = $user->wallet()->with('ledgerEntries.transaction')->first();

        return Inertia\Inertia::render('dashboard', [
            'wallet' => $wallet ? [
                'id' => $wallet->id,
                'balance' => $wallet->balance,
                'wallet_transaction_key' => $wallet->wallet_transaction_key,
            ] : null,
            'transactions' => $wallet?->ledgerEntries
                ->map(fn ($entry) => [
                    'id' => $entry->transaction?->id,
                    'type' => $entry->transaction?->type?->value ?? $entry->transaction?->type,
                    'amount' => $entry->amount,
                    'created_at' => $entry->created_at?->toISOString(),
                ])
                ->sortByDesc('id')
                ->values()
                ->all() ?? [],
        ]);
    })->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    // Route::get('/wallet', [WalletController::class, 'show'])->name('wallet.show');
    Route::post('/wallet/deposit', [WalletDepositController::class, 'store'])->name('wallet.deposit');
    Route::post('/wallet/transfer', [WalletTransferController::class, 'store'])->name('wallet.transfer');
    Route::post('/wallet/transactions/{transaction}/reverse', [WalletReversalController::class, 'store'])->name('wallet.transactions.reverse');
});

require __DIR__.'/settings.php';
