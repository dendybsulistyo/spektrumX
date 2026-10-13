<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // Tema "Ruang Cetak"; ganti ke view('auth.login') untuk kembali ke login lama.
        return view('auth.login-ruang-cetak');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $this->endOtherDevices($request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Satu akun hanya boleh login di satu komputer: sesi akun ini di perangkat
     * lain diakhiri, dan kunci "ingat saya" diganti agar perangkat lama tidak
     * bisa masuk otomatis. Perangkat lama melihat pesan di halaman login.
     */
    private function endOtherDevices(Request $request): void
    {
        $user = $request->user();

        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();
        if ($request->boolean('remember')) {
            Auth::guard('web')->login($user, true);
        }

        $otherSessions = DB::table('sessions')->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())->pluck('id');
        foreach ($otherSessions as $sessionId) {
            Cache::put(EnsureUserIsActive::REPLACED_SESSION_PREFIX.$sessionId, true, now()->addDay());
        }
        DB::table('sessions')->whereIn('id', $otherSessions)->delete();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
