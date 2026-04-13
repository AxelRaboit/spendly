<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $roleName): HttpResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        try {
            $role = UserRole::from($roleName);
        } catch (\ValueError) {
            abort(HttpResponse::HTTP_INTERNAL_SERVER_ERROR, 'Invalid role configuration');
        }

        $user = auth()->user();
        $implied = $role === UserRole::User && $user->hasRole(UserRole::Dev->value);

        if (! $implied && ! $user->hasRole($role->value)) {
            abort(HttpResponse::HTTP_FORBIDDEN, 'Unauthorized - insufficient role');
        }

        return $next($request);
    }
}
