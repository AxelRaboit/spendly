<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    public function login(): RedirectResponse
    {
        abort_unless((bool) config('demo.enabled'), HttpStatus::NotFound->value);

        $user = User::where('is_demo', true)->first();

        if (! $user) {
            return redirect()->route('login')->with('status', 'Demo account not available.');
        }

        Auth::login($user, remember: false);

        request()->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
