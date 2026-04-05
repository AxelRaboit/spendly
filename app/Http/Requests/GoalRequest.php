<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wallet_id' => 'nullable|exists:wallets,id',
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'target_amount' => 'required|numeric|min:0.01',
            'saved_amount' => 'nullable|numeric|min:0',
            'deadline' => 'nullable|date',
            'color' => 'nullable|string|max:7',
        ];
    }
}
