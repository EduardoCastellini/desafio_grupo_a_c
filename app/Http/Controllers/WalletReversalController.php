<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReverseTransactionRequest;
use App\Models\Transaction;
use App\Services\Wallet\ReverseTransactionService;
use Illuminate\Http\RedirectResponse;

class WalletReversalController extends Controller
{
    public function __construct(
        private readonly ReverseTransactionService $reverseTransactionService,
    ) {}

    public function store(ReverseTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->reverseTransactionService->execute(
            userId: $request->user()->id,
            transactionId: $transaction->id,
            idempotencyKey: $request->validated()['idempotency_key'],
        );

        return redirect()->route('dashboard')->with(
            'status',
            'Transação revertida com sucesso.',
        );
    }
}
