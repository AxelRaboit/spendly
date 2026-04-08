<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\WalletMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_balance' => ['required', 'numeric', 'min:0'],
            'mode' => ['required', Rule::in(WalletMode::values())],
        ];
    }
}
