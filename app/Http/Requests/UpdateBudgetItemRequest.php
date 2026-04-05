<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BudgetSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'planned_amount' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('user_id', $this->user()->id)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'type' => ['sometimes', Rule::in(BudgetSection::values())],
            'repeat_next_month' => ['sometimes', 'boolean'],
            'target_type' => ['nullable', Rule::in(['spending', 'saving', 'by_date'])],
            'target_amount' => ['nullable', 'numeric', 'min:0.01'],
            'target_deadline' => ['nullable', 'date'],
        ];
    }
}
