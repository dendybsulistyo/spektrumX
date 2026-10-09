<?php

namespace App\Http\Middleware;

use App\Support\FinanceMenuAccess;
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
        // keuangan.view hanya berlaku bila menu halaman ini diberikan ke role user.
        $allowed = $user && collect($permissions)->contains(fn (string $permission) => $permission === 'keuangan.view'
            ? FinanceMenuAccess::allows($user, $request->route()?->getName())
            : $user->hasPermission($permission));
        abort_unless($allowed, 403);

        return $next($request);
    }
}
