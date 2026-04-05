<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\BudgetSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BudgetPresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(BudgetSection::values())],
            'planned_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
