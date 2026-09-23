<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferRequest;
use App\Services\Wallet\TransferService;
use Illuminate\Http\RedirectResponse;

class WalletTransferController extends Controller
{
    public function __construct(
        private readonly TransferService $transferService,
    ) {}

    public function store(TransferRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->transferService->execute(
            userId: $request->user()->id,
            amount: $validated['amount'],
            destinationWalletTransactionKey: $validated['wallet_transaction_key'],
            idempotencyKey: $validated['idempotency_key'],
        );

        return redirect()->route('wallet.show')->with(
            'status',
            'Transferência realizada com sucesso.',
        );
    }
}
