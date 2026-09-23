<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function show(Request $request): Response
    {
        $wallet = $request->user()->wallet()->with('ledgerEntries.transaction')->first();

        return Inertia::render('wallet/show', [
            'wallet' => [
                'id' => $wallet?->id,
                'balance' => $wallet?->balance ?? 0,
                'wallet_transaction_key' => $wallet?->wallet_transaction_key,
            ],
            'transactions' => $wallet?->ledgerEntries
                ->map(fn ($entry) => [
                    'id' => $entry->transaction?->id,
                    'type' => $entry->transaction?->type?->value ?? $entry->transaction?->type,
                    'amount' => $entry->amount,
                    'created_at' => $entry->created_at?->toISOString(),
                ])
                ->values()
                ->all() ?? [],
        ]);
    }
}
