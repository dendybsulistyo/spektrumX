<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class SystemStatusController extends Controller
{
    /**
     * Status OPcache dari sisi web (PHP-FPM), bukan CLI — `php -i` di
     * terminal membaca konfigurasi yang berbeda.
     */
    public function opcache(): JsonResponse
    {
        if (! function_exists('opcache_get_status')) {
            return response()->json(['terpasang' => false, 'aktif' => false]);
        }

        $status = opcache_get_status(false) ?: [];
        $config = opcache_get_configuration()['directives'] ?? [];
        $memory = $status['memory_usage'] ?? [];
        $stats = $status['opcache_statistics'] ?? [];

        return response()->json([
            'terpasang' => true,
            'aktif' => (bool) ($status['opcache_enabled'] ?? false),
            'php' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'memori_mb' => (int) (($config['opcache.memory_consumption'] ?? 0) / 1024 / 1024),
            'memori_terpakai_mb' => round(($memory['used_memory'] ?? 0) / 1024 / 1024, 1),
            'validate_timestamps' => (bool) ($config['opcache.validate_timestamps'] ?? true),
            'revalidate_freq' => $config['opcache.revalidate_freq'] ?? null,
            'max_accelerated_files' => $config['opcache.max_accelerated_files'] ?? null,
            'file_ter-cache' => $stats['num_cached_scripts'] ?? 0,
            'hit_rate_persen' => round($stats['opcache_hit_rate'] ?? 0, 1),
        ]);
    }
}
