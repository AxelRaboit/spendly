<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DevPasswordMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $devPassword = config('app.dev_password');

        if (empty($devPassword)) {
            return $next($request);
        }

        if ($request->routeIs('dev.password.*')) {
            return $next($request);
        }

        if ($request->session()->get('dev_password_verified') === true) {
            return $next($request);
        }

        return redirect()->route('dev.password.show');
    }
}
