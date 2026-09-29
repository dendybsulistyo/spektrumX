<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnyPermission
{
    /**
     * Allow a route when the authenticated user owns at least one of the
     * listed permissions. This keeps shared operational endpoints explicit
     * without granting a broad role access to unrelated modules.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        abort_unless(
            $user && collect($permissions)->contains(fn (string $permission) => $user->hasPermission($permission)),
            403
        );

        return $next($request);
    }
}
