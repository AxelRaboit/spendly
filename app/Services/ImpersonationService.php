<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\HttpStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationService
{
    public function __construct(private readonly Request $request) {}

    public function impersonate(User $target, User $impersonator): void
    {
        $this->request->session()->put('impersonator_id', $impersonator->id);
        Auth::login($target);
    }

    public function leave(): User
    {
        $adminId = $this->request->session()->pull('impersonator_id');

        abort_if($adminId === null, HttpStatus::Forbidden->value, 'Not currently impersonating.');

        $admin = User::findOrFail($adminId);
        Auth::login($admin);

        return $admin;
    }

    public function isImpersonating(): bool
    {
        return $this->request->session()->has('impersonator_id');
    }
}
