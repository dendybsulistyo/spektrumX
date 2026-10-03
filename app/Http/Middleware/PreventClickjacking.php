<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventClickjacking
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->remove('X-Powered-By');

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $viteOrigin = $this->viteDevelopmentOrigin();
        $scriptSources = "'self' 'unsafe-inline' 'unsafe-eval'".($viteOrigin ? ' '.$viteOrigin : '');
        $styleSources = "'self' 'unsafe-inline'".($viteOrigin ? ' '.$viteOrigin : '');
        $connectSources = "'self'".($viteOrigin ? ' '.$viteOrigin.' '.preg_replace('/^http/', 'ws', $viteOrigin) : '');

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "script-src {$scriptSources}",
            "style-src {$styleSources}",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src {$connectSources}",
            "frame-src 'self'",
            "media-src 'self' blob:",
            "worker-src 'self' blob:",
        ]));

        return $response;
    }

    /**
     * Laravel's Vite dev server runs on a different port, so it is a
     * different CSP origin even on localhost. Only relax the policy while
     * running locally and only for the exact origin written by Vite.
     */
    private function viteDevelopmentOrigin(): ?string
    {
        if (! app()->environment('local') || ! is_file(public_path('hot'))) {
            return null;
        }

        $url = trim((string) file_get_contents(public_path('hot')));
        $parts = parse_url($url);
        if (! in_array($parts['scheme'] ?? null, ['http', 'https'], true) || empty($parts['host'])) {
            return null;
        }

        $host = $parts['host'];
        if (str_contains($host, ':') && ! str_starts_with($host, '[')) {
            $host = '['.$host.']';
        }

        return $parts['scheme'].'://'.$host.(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
