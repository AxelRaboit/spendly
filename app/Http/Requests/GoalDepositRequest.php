<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoalDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        $goal = $this->route('goal');

        if (! $goal || $goal->user_id !== $this->user()->id) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'category_id' => 'required|exists:categories,id',
            'date' => 'required|date',
            'description' => 'nullable|string|max:255',
        ];
    }
}
