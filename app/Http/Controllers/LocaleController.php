<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => ['required', 'string', 'in:fr,en,es,de'],
        ]);

        $request->user()->update(['locale' => $request->locale]);

        return back();
    }
}
