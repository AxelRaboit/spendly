<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $accessibleWalletIds = $this->user()->accessibleWallets()->pluck('id')->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'wallet_id' => ['required', 'integer', Rule::in($accessibleWalletIds)],
        ];
    }
}
