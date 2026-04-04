<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    private const SUPPORTED = ['fr', 'en', 'es', 'de'];

    public function handle(Request $request, Closure $next): mixed
    {
        $user   = $request->user();
        $locale = $user !== null ? $user->locale : config('app.fallback_locale', 'fr');

        App::setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'fr');

        return $next($request);
    }
}
