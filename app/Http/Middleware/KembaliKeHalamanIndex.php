<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Setelah tambah/ubah/hapus data, kembali ke halaman daftar yang terakhir
 * dibuka (halaman ke-n, pencarian, filter) — bukan ke halaman pertama.
 *
 * - GET ke route bernama "*.index": URL lengkapnya diingat per path.
 * - Request non-GET yang me-redirect ke path index itu TANPA query string
 *   (mis. redirect()->route('harga-artwork.index')) diarahkan ke URL yang diingat.
 *
 * Redirect yang sudah membawa query sendiri tidak disentuh.
 */
class KembaliKeHalamanIndex
{
    public const SESSION_KEY = 'halaman_index';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->hasSession()) {
            return $response;
        }

        if ($request->isMethod('GET')) {
            $this->ingat($request, $response);
        } elseif ($response instanceof RedirectResponse) {
            $this->arahkan($request, $response);
        }

        return $response;
    }

    /** URL daftar yang diingat untuk route index ini, atau URL polosnya. */
    public static function url(string $routeName): string
    {
        $polos = route($routeName);
        $tersimpan = session(self::SESSION_KEY.'.'.self::kunci($polos));

        return $tersimpan ?: $polos;
    }

    private function ingat(Request $request, Response $response): void
    {
        $nama = $request->route()?->getName();

        if (! $nama || ! str_ends_with($nama, '.index') || $request->ajax() || ! $response->isSuccessful()) {
            return;
        }

        $kunci = self::SESSION_KEY.'.'.self::kunci($request->url());

        $request->getQueryString()
            ? $request->session()->put($kunci, $request->fullUrl())
            : $request->session()->forget($kunci);
    }

    private function arahkan(Request $request, RedirectResponse $response): void
    {
        $tujuan = $response->getTargetUrl();

        if (parse_url($tujuan, PHP_URL_QUERY) !== null) {
            return;
        }

        $tersimpan = $request->session()->get(self::SESSION_KEY.'.'.self::kunci($tujuan));

        if ($tersimpan) {
            $response->setTargetUrl($tersimpan);
        }
    }

    /** Kunci per path (host diabaikan: APP_URL bisa beda dengan host yang dipakai user). */
    private static function kunci(string $url): string
    {
        return md5(rtrim(parse_url($url, PHP_URL_PATH) ?: '/', '/'));
    }
}
