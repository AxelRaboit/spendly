<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'from_wallet_id' => [
                'required',
                'integer',
                Rule::exists('wallets', 'id')->where('user_id', $this->user()->id),
            ],
            'to_wallet_id' => [
                'required',
                'integer',
                Rule::exists('wallets', 'id')->where('user_id', $this->user()->id),
                Rule::notIn([$this->input('from_wallet_id')]),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
