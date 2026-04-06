<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\WalletMember;
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
        $accessibleWalletIds = WalletMember::where('user_id', $this->user()->id)
            ->pluck('wallet_id')
            ->all();

        return [
            'from_wallet_id' => [
                'required',
                'integer',
                Rule::in($accessibleWalletIds),
            ],
            'to_wallet_id' => [
                'required',
                'integer',
                Rule::in($accessibleWalletIds),
                Rule::notIn([$this->input('from_wallet_id')]),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
