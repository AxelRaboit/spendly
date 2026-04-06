<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $updateLocaleRequest): RedirectResponse
    {
        $updateLocaleRequest->user()->update(['locale' => $updateLocaleRequest->validated()['locale']]);

        return back();
    }
}
