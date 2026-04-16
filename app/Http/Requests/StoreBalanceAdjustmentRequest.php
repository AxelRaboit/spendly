<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBalanceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'new_balance' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'month' => ['nullable', 'string', 'date_format:Y-m'],
        ];
    }

    public function after(): array
    {
        return [
            $this->validateDateWithinBudgetMonth(...),
        ];
    }

    private function validateDateWithinBudgetMonth(Validator $validator): void
    {
        if (! $this->input('month')) {
            return;
        }

        $month = Carbon::createFromFormat('Y-m', $this->input('month'));
        $transactionDate = Carbon::parse($this->input('date'));

        if ($transactionDate->month !== $month->month || $transactionDate->year !== $month->year) {
            $validator->errors()->add(
                'date',
                __('validation.date_in_month', ['month' => $month->translatedFormat('F Y')])
            );
        }
    }
}
