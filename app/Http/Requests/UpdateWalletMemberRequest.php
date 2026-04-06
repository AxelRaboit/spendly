<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\WalletRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWalletMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(WalletRole::invitableValues())],
        ];
    }
}
