<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => 'required|array|min:1',
            'rows.*.date' => 'required|string',
            'rows.*.amount' => 'required|string',
            'rows.*.type' => ['required', Rule::in(TransactionType::values())],
            'rows.*.category_id' => 'required|exists:categories,id',
            'rows.*.description' => 'nullable|string|max:255',
            'rows.*.tags' => 'nullable|string|max:255',
            'wallet_id' => 'required|exists:wallets,id',
        ];
    }
}
