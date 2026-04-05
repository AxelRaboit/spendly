<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BudgetSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickStartBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
            'suggestions' => ['required', 'array', 'min:1'],
            'suggestions.*.label' => ['required', 'string', 'max:255'],
            'suggestions.*.type' => ['required', 'string', Rule::in(BudgetSection::values())],
            'suggestions.*.planned_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
