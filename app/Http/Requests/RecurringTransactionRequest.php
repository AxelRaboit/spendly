<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => 'required|exists:wallets,id',
            'category_id' => 'required|exists:categories,id',
            'type' => ['required', Rule::in(TransactionType::values())],
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'day_of_month' => 'required|integer|min:1|max:28',
            'active' => 'boolean',
        ];
    }
}
