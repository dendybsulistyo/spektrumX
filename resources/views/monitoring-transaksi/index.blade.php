@php
    $fmtRp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $fmtNum = fn ($v) => number_format((float) $v, 0, ',', '.');
    $maxOmzet = collect($periods)->max('omzet') ?: 1;

    $periodLabel = function (string $periode) use ($granularitas) {
        return match ($granularitas) {
            'harian' => \Illuminate\Support\Carbon::parse($periode)->translatedFormat('d M Y'),
            'mingguan' => str_replace('-W', ' — Minggu ke-', $periode),
            'tahunan' => $periode,
            default => \Illuminate\Support\Carbon::parse($periode.'-01')->translatedFormat('F Y'),
        };
    };

    $queryWithout = fn (array $except) => collect(request()->query())->except($except)->all();
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Monitoring Transaksi</h2>
    </x-slot>

    <div class="space-y-6">
        {{-- Filter bar --}}
        <div class="bg-white rounded-lg border border-gray-200 p-4 space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500 mr-1">Tampilkan:</span>
                <a href="{{ route('monitoring-transaksi.index', $queryWithout(['status']) + ['status' => 'semua']) }}"
                   class="px-3 py-1.5 rounded-md text-xs font-semibold {{ $status === 'semua' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Semua Order
                </a>
                <a href="{{ route('monitoring-transaksi.index', $queryWithout(['status']) + ['status' => 'lunas']) }}"
                   class="px-3 py-1.5 rounded-md text-xs font-semibold {{ $status === 'lunas' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Lunas Saja
                </a>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-gray-500 mr-1">Per:</span>
                @foreach (['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan', 'tahunan' => 'Tahunan'] as $key => $label)
                    <a href="{{ route('monitoring-transaksi.index', ['status' => $status, 'granularitas' => $key]) }}"
                       class="px-3 py-1.5 rounded-md text-xs font-semibold {{ $granularitas === $key ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <form method="GET" class="flex flex-wrap items-end gap-3 pt-2 border-t border-gray-100">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="granularitas" value="{{ $granularitas }}">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Dari Tanggal</label>
                    <input type="date" name="from" value="{{ $from }}" class="rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" value="{{ $to }}" class="rounded-md border-gray-300 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">Terapkan</button>
            </form>
        </div>

        {{-- KPI tiles --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-lg border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Omzet {{ $status === 'lunas' ? '(Lunas)' : '(Semua Order)' }}</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $fmtRp($kpi['total_omzet']) }}</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Transaksi</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $fmtNum($kpi['total_transaksi']) }}</p>
            </div>
        </div>

        {{-- Trend chart --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Tren Omzet</h3>
            <p class="text-xs text-gray-400 mb-4">Hover batang untuk lihat nilai persis.</p>

            @if (empty($periods))
                <p class="text-sm text-gray-400 py-8 text-center">Tidak ada data pada rentang ini.</p>
            @else
                <div class="overflow-x-auto pb-2">
                    <div class="flex items-end gap-1" style="min-width: {{ max(700, count($periods) * 20) }}px; height: 180px;">
                        @foreach ($periods as $periode => $data)
                            @php $h = max(2, round(($data['omzet'] / $maxOmzet) * 160)); @endphp
                            <div class="flex flex-col items-center justify-end h-full" style="width: 18px;">
                                <div title="{{ $periodLabel($periode) }}: {{ $fmtRp($data['omzet']) }} ({{ $data['jumlah'] }} transaksi)"
                                     style="width: 10px; height: {{ $h }}px; background-color: #4f46e5; border-radius: 2px 2px 0 0; cursor: default;"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Detail table --}}
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Rincian per Periode</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Periode</th>
                            <th class="px-4 py-3 text-right">Indoor</th>
                            <th class="px-4 py-3 text-right">Outdoor</th>
                            <th class="px-4 py-3 text-right">Artwork</th>
                            <th class="px-4 py-3 text-right">Total Transaksi</th>
                            <th class="px-4 py-3 text-right">Total Omzet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse (array_reverse($periods, true) as $periode => $data)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $periodLabel($periode) }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $data['jenis']['Indoor']['jumlah'] ?? 0 }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $data['jenis']['Outdoor']['jumlah'] ?? 0 }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $data['jenis']['Artwork']['jumlah'] ?? 0 }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ $fmtNum($data['jumlah']) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ $fmtRp($data['omzet']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada data pada rentang ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
