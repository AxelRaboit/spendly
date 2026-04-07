<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        abort_unless((bool) config('demo.enabled'), HttpStatus::NotFound->value);

        $accessPassword = config('demo.access_password');

        if ($accessPassword) {
            $request->validate([
                'access_password' => ['required', 'string'],
            ]);

            if ($request->input('access_password') !== $accessPassword) {
                return back()->withErrors(['access_password' => __('demo.wrong_password')]);
            }
        }

        $user = User::where('is_demo', true)->first();

        if (! $user) {
            return redirect()->route('login')->with('status', 'Demo account not available.');
        }

        Auth::login($user, remember: false);

        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
