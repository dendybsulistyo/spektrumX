<?php

namespace App\Http\Middleware;

use App\Support\FinanceMenuAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless($request->user()?->hasPermission($permission), 403);

        // keuangan.view dibagi per menu (Perpajakan / Akuntansi).
        if ($permission === 'keuangan.view') {
            abort_unless(FinanceMenuAccess::allows($request->user(), $request->route()?->getName()), 403);
        }

        return $next($request);
    }
}
