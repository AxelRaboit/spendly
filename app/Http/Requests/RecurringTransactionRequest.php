<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Wallet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurringTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->filled('wallet_id')) {
            $wallet = Wallet::find($this->input('wallet_id'));
            if (! $wallet || $wallet->user_id !== $this->user()->id) {
                return false;
            }
        }

        if ($this->filled('category_id')) {
            $category = Category::find($this->input('category_id'));
            if (! $category || $category->user_id !== $this->user()->id) {
                return false;
            }
        }

        return $this->user() !== null;
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
