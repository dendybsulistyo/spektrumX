<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">Monitor Server</h2>
                <p class="mt-1 text-sm text-gray-500">Status server tempat SpektrumX berjalan.</p>
            </div>
            <a href="{{ route('server-monitor.index') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Muat Ulang</a>
        </div>
    </x-slot>

    @php
        $formatBytes = fn (?float $bytes) => $bytes === null ? 'Tidak tersedia' : ($bytes >= 1073741824 ? number_format($bytes / 1073741824, 2, ',', '.').' GB' : number_format($bytes / 1048576, 1, ',', '.').' MB');
        $formatPercent = fn (?float $percent) => $percent === null ? 'Tidak tersedia' : number_format($percent, 1, ',', '.').'%';
        $status = fn (?bool $active) => $active === true ? ['Aktif', 'bg-emerald-100 text-emerald-700'] : ($active === false ? ['Tidak aktif', 'bg-red-100 text-red-700'] : ['Tidak tersedia', 'bg-gray-100 text-gray-600']);
        $uptime = $snapshot['uptime_seconds'] === null ? 'Tidak tersedia' : \Carbon\CarbonInterval::seconds((int) $snapshot['uptime_seconds'])->cascade()->forHumans(['parts' => 3, 'short' => true]);
        $loadPercent = $snapshot['load'] && $snapshot['cpu_cores'] ? min(100, ($snapshot['load'][0] / $snapshot['cpu_cores']) * 100) : null;
        $scale = function (?float $percent): array {
            if ($percent === null) return ['Tidak tersedia', '#d1d5db', 'text-gray-600', 0];
            if ($percent >= 90) return ['Kritis', '#ef4444', 'text-red-600', $percent];
            if ($percent >= 70) return ['Perlu perhatian', '#f59e0b', 'text-amber-600', $percent];
            return ['Aman', '#10b981', 'text-emerald-600', $percent];
        };
        [$diskLabel, $diskColor, $diskTextColor, $diskPercent] = $scale($snapshot['disk']['percent']);
        [$memoryLabel, $memoryColor, $memoryTextColor, $memoryPercent] = $scale($snapshot['memory']['percent']);
        [$loadLabel, $loadColor, $loadTextColor, $loadScale] = $scale($loadPercent);
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-gray-200 bg-white px-5 py-4 text-sm text-gray-600">
                <div class="flex flex-wrap items-center justify-between gap-2"><span><strong class="text-gray-900">{{ $snapshot['hostname'] }}</strong> · {{ $snapshot['os'] }} · PHP {{ $snapshot['php_version'] }}</span><span>Diperbarui {{ $snapshot['updated_at']->format('d-m-Y H:i:s') }}</span></div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-5"><div class="flex items-center justify-between"><p class="text-sm text-gray-500">Kapasitas Disk</p><span class="text-xs font-semibold {{ $diskTextColor }}">{{ $diskLabel }}</span></div><p class="mt-2 text-2xl font-bold text-gray-900">{{ $formatPercent($snapshot['disk']['percent']) }}</p><div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full" style="width: {{ $diskPercent }}%; background-color: {{ $diskColor }}"></div></div><p class="mt-2 text-sm text-gray-500">{{ $formatBytes($snapshot['disk']['used']) }} / {{ $formatBytes($snapshot['disk']['total']) }}</p></div>
                <div class="rounded-lg border border-gray-200 bg-white p-5"><div class="flex items-center justify-between"><p class="text-sm text-gray-500">Memori Terpakai</p><span class="text-xs font-semibold {{ $memoryTextColor }}">{{ $memoryLabel }}</span></div><p class="mt-2 text-2xl font-bold text-gray-900">{{ $formatPercent($snapshot['memory']['percent']) }}</p><div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full" style="width: {{ $memoryPercent }}%; background-color: {{ $memoryColor }}"></div></div><p class="mt-2 text-sm text-gray-500">{{ $formatBytes($snapshot['memory']['used']) }} / {{ $formatBytes($snapshot['memory']['total']) }}</p></div>
                <div class="rounded-lg border border-gray-200 bg-white p-5"><div class="flex items-center justify-between"><p class="text-sm text-gray-500">Server Load</p><span class="text-xs font-semibold {{ $loadTextColor }}">{{ $loadLabel }}</span></div><p class="mt-2 text-2xl font-bold text-gray-900">{{ $snapshot['load'] ? number_format($snapshot['load'][0], 2, ',', '.') : 'Tidak tersedia' }}</p><div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100"><div class="h-full rounded-full" style="width: {{ $loadScale }}%; background-color: {{ $loadColor }}"></div></div><p class="mt-2 text-sm text-gray-500">{{ $snapshot['cpu_cores'] ? $snapshot['cpu_cores'].' core · ' : '' }}5 m: {{ $snapshot['load'] ? number_format($snapshot['load'][1], 2, ',', '.') : '-' }} · 15 m: {{ $snapshot['load'] ? number_format($snapshot['load'][2], 2, ',', '.') : '-' }}</p></div>
                <div class="rounded-lg border border-gray-200 bg-white p-5"><p class="text-sm text-gray-500">Uptime</p><p class="mt-2 text-2xl font-bold text-gray-900">{{ $uptime }}</p><p class="mt-1 text-sm text-gray-500">Log Laravel: {{ $formatBytes($snapshot['logs_size']) }}</p></div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
                <div class="border-b border-gray-200 px-5 py-4"><h3 class="font-semibold text-gray-900">Status Layanan</h3><p class="mt-1 text-sm text-gray-500">Pemeriksaan read-only; tidak ada service yang dijalankan atau dihentikan dari halaman ini.</p></div>
                <div class="grid divide-y divide-gray-100 md:grid-cols-3 md:divide-x md:divide-y-0">
                    @foreach ($snapshot['services'] as $name => $active)
                        @php [$label, $class] = $status($active); @endphp
                        <div class="flex items-center justify-between px-5 py-4"><span class="font-medium text-gray-800">{{ $name }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $class }}">{{ $label }}</span></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
