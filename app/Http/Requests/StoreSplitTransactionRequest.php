<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSplitTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', Rule::exists('wallets', 'id')->where('user_id', $this->user()->id)],
            'type' => ['required', Rule::in(TransactionType::values())],
            'description' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'tags' => ['nullable', 'array'],
            'splits' => ['required', 'array', 'min:2'],
            'splits.*.category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'splits.*.amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
