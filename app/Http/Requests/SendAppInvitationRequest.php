<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendAppInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'credential_email' => ['nullable', 'email', 'max:255'],
            'credential_password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
