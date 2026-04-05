<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->filled('wallet_id')) {
            $wallet = Wallet::find($this->input('wallet_id'));
            if (! $wallet || $wallet->user_id !== $this->user()->id) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => 'required|array|min:1',
            'rows.*.date' => 'required|string',
            'rows.*.amount' => 'required|string',
            'rows.*.type' => ['required', Rule::in(TransactionType::values())],
            'rows.*.category_id' => ['required', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'rows.*.description' => 'nullable|string|max:255',
            'rows.*.tags' => 'nullable|string|max:255',
            'wallet_id' => 'required|exists:wallets,id',
        ];
    }
}
