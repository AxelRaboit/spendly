<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BudgetSection;
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
            'type' => ['required', Rule::in(BudgetSection::values())],
            'label' => ['required', 'string', 'max:255'],
            'planned_amount' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'position' => ['nullable', 'integer', 'min:0'],
            'month' => ['required', 'date_format:Y-m'],
            'is_recurring' => ['sometimes', 'boolean'],
        ];
    }
}
