<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cheap "did this page's data change" signal for polling-based auto-refresh.
 * A controller bumps a key on every mutation; the page polls the matching
 * value and reloads itself when it drifts from what it rendered with.
 */
class PageVersion
{
    public static function touch(string $key): void
    {
        Cache::put(self::cacheKey($key), (string) microtime(true), now()->addDay());
    }

    public static function get(string $key): string
    {
        return Cache::get(self::cacheKey($key), '0');
    }

    private static function cacheKey(string $key): string
    {
        return "page-version:{$key}";
    }
}
