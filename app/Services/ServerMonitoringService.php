<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Throwable;

class ServerMonitoringService
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $diskTotal = (float) (disk_total_space(base_path()) ?: 0);
        $diskFree = (float) (disk_free_space(base_path()) ?: 0);
        $memory = $this->memory();

        return [
            'hostname' => gethostname() ?: php_uname('n'),
            'os' => php_uname('s').' '.php_uname('r'),
            'php_version' => PHP_VERSION,
            'cpu_cores' => $this->cpuCores(),
            'updated_at' => now(),
            'load' => sys_getloadavg() ?: null,
            'uptime_seconds' => $this->uptimeSeconds(),
            'disk' => [
                'total' => $diskTotal,
                'free' => $diskFree,
                'used' => max(0, $diskTotal - $diskFree),
                'percent' => $diskTotal > 0 ? (($diskTotal - $diskFree) / $diskTotal) * 100 : null,
            ],
            'memory' => $memory,
            'logs_size' => $this->directorySize(storage_path('logs')),
            'services' => [
                'Nginx' => $this->serviceStatus('nginx'),
                'PHP-FPM' => $this->phpFpmStatus(),
                'MySQL' => $this->mysqlStatus(),
            ],
        ];
    }

    /** @return array{total: ?int, available: ?int, used: ?int, percent: ?float} */
    private function memory(): array
    {
        $path = '/proc/meminfo';
        if (! is_readable($path)) {
            return PHP_OS_FAMILY === 'Darwin'
                ? $this->macMemory()
                : ['total' => null, 'available' => null, 'used' => null, 'percent' => null];
        }

        preg_match_all('/^(MemTotal|MemAvailable):\s+(\d+)\s+kB$/m', (string) file_get_contents($path), $matches, PREG_SET_ORDER);
        $values = collect($matches)->mapWithKeys(fn (array $match) => [$match[1] => (int) $match[2] * 1024]);
        $total = $values->get('MemTotal');
        $available = $values->get('MemAvailable');
        $used = $total !== null && $available !== null ? max(0, $total - $available) : null;

        return [
            'total' => $total,
            'available' => $available,
            'used' => $used,
            'percent' => $total && $used !== null ? ($used / $total) * 100 : null,
        ];
    }

    private function uptimeSeconds(): ?float
    {
        $path = '/proc/uptime';
        if (! is_readable($path)) {
            return null;
        }

        return (float) explode(' ', trim((string) file_get_contents($path)))[0];
    }

    private function cpuCores(): ?int
    {
        $path = '/proc/cpuinfo';
        if (! is_readable($path)) {
            if (PHP_OS_FAMILY !== 'Darwin') {
                return null;
            }

            try {
                $result = Process::timeout(2)->run('sysctl -n hw.ncpu');

                return $result->successful() ? (int) trim($result->output()) : null;
            } catch (Throwable) {
                return null;
            }
        }

        $cores = preg_match_all('/^processor\s*:/m', (string) file_get_contents($path));

        return $cores > 0 ? $cores : null;
    }

    /** @return array{total: ?int, available: ?int, used: ?int, percent: ?float} */
    private function macMemory(): array
    {
        try {
            $totalResult = Process::timeout(2)->run('sysctl -n hw.memsize');
            $pageSizeResult = Process::timeout(2)->run('sysctl -n hw.pagesize');
            $vmStatResult = Process::timeout(2)->run('vm_stat');
            if (! $totalResult->successful() || ! $pageSizeResult->successful() || ! $vmStatResult->successful()) {
                return ['total' => null, 'available' => null, 'used' => null, 'percent' => null];
            }

            $total = (int) trim($totalResult->output());
            $pageSize = (int) trim($pageSizeResult->output());
            preg_match_all('/^Pages (free|speculative|inactive|purgeable):\s+(\d+)\./m', $vmStatResult->output(), $matches, PREG_SET_ORDER);
            $availablePages = collect($matches)->sum(fn (array $match) => (int) $match[2]);
            $available = $availablePages * $pageSize;
            $used = max(0, $total - $available);

            return ['total' => $total, 'available' => $available, 'used' => $used, 'percent' => $total > 0 ? ($used / $total) * 100 : null];
        } catch (Throwable) {
            return ['total' => null, 'available' => null, 'used' => null, 'percent' => null];
        }
    }

    private function mysqlStatus(): ?bool
    {
        // A DB connection attempt can wait far longer than a dashboard page
        // should. On Ubuntu, systemd reports MySQL's process state instantly.
        return $this->serviceStatus('mysql') ?? $this->serviceStatus('mariadb');
    }

    private function phpFpmStatus(): ?bool
    {
        if (PHP_SAPI === 'fpm-fcgi') {
            return true;
        }

        return $this->serviceStatus('php8.3-fpm') ?? $this->serviceStatus('php8.2-fpm');
    }

    private function serviceStatus(string $service): ?bool
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return null;
        }

        try {
            return Process::timeout(2)->run("systemctl is-active --quiet {$service}")->successful();
        } catch (Throwable) {
            return null;
        }
    }

    private function directorySize(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        try {
            $size = 0;
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                $size += $file->getSize();
            }

            return $size;
        } catch (Throwable) {
            return 0;
        }
    }
}
