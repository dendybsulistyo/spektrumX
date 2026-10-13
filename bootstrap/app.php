<?php

use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureAnyPermission;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\PreventClickjacking;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(PreventClickjacking::class);
        $middleware->web(append: [EnsureUserIsActive::class]);
        $middleware->alias([
            'permission' => EnsurePermission::class,
            'permission.any' => EnsureAnyPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Klik Logout saat sesi sudah habis (419): tujuan user memang keluar,
        // jadi langsung keluarkan & arahkan ke login, bukan halaman error.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && $request->isMethod('post') && $request->is('logout')) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('status', 'Anda sudah keluar. Sesi sebelumnya sudah berakhir, silakan login kembali bila perlu.');
            }

            return null;
        });
    })->create();
