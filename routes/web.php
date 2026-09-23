<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WalletDepositController;
use App\Http\Controllers\WalletReversalController;
use App\Http\Controllers\WalletTransferController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/wallet/deposit', [WalletDepositController::class, 'store'])->name('wallet.deposit');
    Route::post('/wallet/transfer', [WalletTransferController::class, 'store'])->name('wallet.transfer');
    Route::post('/wallet/transactions/{transaction}/reverse', [WalletReversalController::class, 'store'])->name('wallet.transactions.reverse');
});

require __DIR__.'/settings.php';
