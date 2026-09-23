<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge([
                'amount' => $this->parseAmountToCents($this->input('amount')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    private function parseAmountToCents(mixed $amount): int
    {
        $value = (string) $amount;
        $value = trim($value);
        $value = str_replace(' ', '', $value);

        if ($value === '') {
            return 0;
        }

        if (str_contains($value, ',') && str_contains($value, '.')) {
            if (strrpos($value, ',') > strrpos($value, '.')) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return (int) round((float) $value * 100);
    }
}
