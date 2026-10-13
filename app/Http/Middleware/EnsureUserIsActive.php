<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang dinonaktifkan langsung dikeluarkan pada request berikutnya
 * (pengaman bila masih ada sesi / "ingat saya" yang tersisa).
 */
class EnsureUserIsActive
{
    /** Penanda sesi yang diakhiri karena akunnya login di komputer lain. */
    public const REPLACED_SESSION_PREFIX = 'sesi-tergeser:';

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        // Sesi ini diakhiri karena akun login di komputer lain: beri tahu di halaman login.
        if (! $user && ! $request->expectsJson() && $request->hasSession()
            && Cache::pull(self::REPLACED_SESSION_PREFIX.$request->session()->getId())) {
            $request->session()->flash('status', 'Akun Anda baru saja login di komputer lain, jadi sesi di komputer ini diakhiri. Satu akun hanya bisa dipakai di satu komputer.');
        }

        if ($user && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Akun Anda sudah dinonaktifkan. Hubungi admin bila ini keliru.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 401)
                : redirect()->route('login')->with('status', $message);
        }

        return $next($request);
    }
}
