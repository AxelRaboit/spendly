<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['income', 'savings', 'bills', 'expenses', 'debt'])],
            'label' => ['required', 'string', 'max:255'],
            'planned_amount' => ['required', 'numeric', 'min:0'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'position' => ['nullable', 'integer', 'min:0'],
            'month' => ['required', 'date_format:Y-m'],
            'is_recurring' => ['sometimes', 'boolean'],
        ];
    }
}
