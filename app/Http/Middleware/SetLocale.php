<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        $locale = $user !== null ? $user->locale : config('app.fallback_locale', Locale::DEFAULT->value);

        App::setLocale(in_array($locale, Locale::values(), true) ? $locale : Locale::DEFAULT->value);

        return $next($request);
    }
}
