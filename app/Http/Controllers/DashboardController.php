<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $wallet = $user->wallet()->with('ledgerEntries.transaction.reversal')->first();

        return Inertia::render('dashboard', [
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
                    'can_reverse' => $entry->transaction !== null
                        && $entry->transaction->type !== TransactionType::REVERSAL
                        && $entry->transaction?->reversal === null,
                ])
                ->sortByDesc('id')
                ->values()
                ->all() ?? [],
        ]);
    }
}
