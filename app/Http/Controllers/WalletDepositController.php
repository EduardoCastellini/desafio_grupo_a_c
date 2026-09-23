<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Services\Wallet\DepositService;
use Illuminate\Http\RedirectResponse;

class WalletDepositController extends Controller
{
    public function __construct(
        private readonly DepositService $depositService,
    ) {}

    public function store(DepositRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->depositService->execute(
            userId: $request->user()->id,
            amount: $validated['amount'],
            idempotencyKey: $validated['idempotency_key'],
        );

        return redirect()->route('wallet.show')->with(
            'status',
            'Depósito realizado com sucesso.',
        );
    }
}
