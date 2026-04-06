<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DevPasswordController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('DevPassword');
    }

    public function check(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required']);

        if ($request->input('password') === config('app.dev_password')) {
            $request->session()->put('dev_password_verified', true);

            return redirect()->intended('/');
        }

        return back()->withErrors(['password' => 'Mot de passe incorrect.']);
    }
}
