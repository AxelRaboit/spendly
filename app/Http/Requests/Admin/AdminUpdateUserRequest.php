<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Currency;
use App\Enums\Locale;
use App\Enums\PlanType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminUpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user')->id)],
            'password' => ['nullable', 'string', Password::min(8)],
            'plan' => ['required', 'string', Rule::in(PlanType::values())],
            'locale' => ['required', 'string', Rule::in(Locale::values())],
            'currency' => ['required', 'string', Rule::in(Currency::values())],
        ];
    }
}
