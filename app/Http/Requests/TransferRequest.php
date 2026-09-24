<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_transaction_key' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'uuid'],
            'source_wallet_id' => ['prohibited'],
        ];
    }
}
