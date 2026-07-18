@php
    $fmtRp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    $fmtNum = fn ($v) => number_format((float) $v, 0, ',', '.');
    $maxTrend = $trend->max('omzet') ?: 1;
    $maxQty = $topProdukQty->max('total_qty') ?: 1;
    $maxOmzetProduk = $topProdukOmzet->max('total_omzet') ?: 1;
    $maxCust = $topCustomers->max('total_belanja') ?: 1;
    $maxBottomQty = $bottomProduk->max('total_qty') ?: 1;
    $tunai = $statusPembayaran->firstWhere('Tunai', 1);
    $nonTunai = $statusPembayaran->firstWhere('Tunai', 0);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">Data Warehouse</h2>
                <p class="text-xs text-gray-400 mt-0.5">
                    Sumber: histori transaksi {{ \Illuminate\Support\Carbon::parse($dataRange['min'])->translatedFormat('M Y') }}
                    – {{ \Illuminate\Support\Carbon::parse($dataRange['max'])->translatedFormat('M Y') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" style="--dw-blue:#2a78d6;--dw-blue-soft:#cde2fb;--dw-ink:#0b0b0b;--dw-ink2:#52514e;--dw-muted:#898781;--dw-grid:#e1e0d9;">

        {{-- Filter bar --}}
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Dari Tanggal</label>
                    <input type="date" name="from" value="{{ $from }}"
                           min="{{ $dataRange['min'] }}" max="{{ $dataRange['max'] }}"
                           class="rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" value="{{ $to }}"
                           min="{{ $dataRange['min'] }}" max="{{ $dataRange['max'] }}"
                           class="rounded-md border-gray-300 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Terapkan
                </button>
                @if ($from || $to)
                    <a href="{{ route('data-warehouse.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset (semua data)</a>
                @endif
                <p class="text-xs text-gray-400 ml-auto">
                    KPI, tren &amp; top customer mengikuti filter tanggal. Breakdown produk selalu all-time.
                </p>
            </form>
        </div>

        {{-- KPI tiles --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Omzet</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $fmtRp($kpi->total_omzet) }}</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Transaksi</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $fmtNum($kpi->total_transaksi) }}</p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Rata-rata / Transaksi</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">
                    {{ $fmtRp($kpi->total_transaksi > 0 ? $kpi->total_omzet / $kpi->total_transaksi : 0) }}
                </p>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-5">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Customer Aktif</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $fmtNum($kpi->total_customer) }}</p>
            </div>
        </div>

        {{-- Trend chart --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Tren Omzet Bulanan</h3>
            <p class="text-xs text-gray-400 mb-4">Hover batang untuk lihat nilai persis.</p>

            @if ($trend->isEmpty())
                <p class="text-sm text-gray-400 py-8 text-center">Tidak ada data pada rentang ini.</p>
            @else
                <div class="overflow-x-auto pb-2">
                    <div class="flex items-end gap-1" style="min-width: {{ max(700, $trend->count() * 16) }}px; height: 180px;">
                        @foreach ($trend as $row)
                            @php $h = max(2, round(($row->omzet / $maxTrend) * 160)); @endphp
                            <div class="flex flex-col items-center justify-end h-full" style="width: 14px;">
                                <div title="{{ \Illuminate\Support\Carbon::parse($row->bulan.'-01')->translatedFormat('F Y') }}: {{ $fmtRp($row->omzet) }} ({{ $row->jumlah }} transaksi)"
                                     style="width: 8px; height: {{ $h }}px; background-color: var(--dw-blue); border-radius: 2px 2px 0 0; cursor: default;"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex gap-1 mt-1" style="min-width: {{ max(700, $trend->count() * 16) }}px;">
                        @foreach ($trend as $i => $row)
                            <div style="width: 14px;" class="text-center">
                                @if ($i % 3 === 0)
                                    <span class="text-[9px] text-gray-400 whitespace-nowrap" style="writing-mode: vertical-rl;">
                                        {{ \Illuminate\Support\Carbon::parse($row->bulan.'-01')->translatedFormat('M/y') }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Metode pembayaran --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Metode Pembayaran</h3>
            @php
                $tunaiOmzet = $tunai->omzet ?? 0;
                $nonTunaiOmzet = $nonTunai->omzet ?? 0;
                $totalMetode = max(1, $tunaiOmzet + $nonTunaiOmzet);
                $tunaiPct = round($tunaiOmzet / $totalMetode * 100, 1);
                $nonTunaiPct = round($nonTunaiOmzet / $totalMetode * 100, 1);
            @endphp
            <div class="flex h-3 rounded-full overflow-hidden bg-gray-100">
                <div style="width: {{ $tunaiPct }}%; background-color: var(--dw-blue);" title="Tunai: {{ $tunaiPct }}%"></div>
                <div style="width: {{ $nonTunaiPct }}%; background-color: var(--dw-blue-soft);" title="Non-Tunai: {{ $nonTunaiPct }}%"></div>
            </div>
            <div class="flex gap-6 mt-3 text-sm">
                <p class="flex items-center gap-2">
                    <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: var(--dw-blue);"></span>
                    Tunai — {{ $fmtRp($tunaiOmzet) }} ({{ $fmtNum($tunai->jumlah ?? 0) }} transaksi)
                </p>
                <p class="flex items-center gap-2">
                    <span class="inline-block w-2.5 h-2.5 rounded-full" style="background-color: var(--dw-blue-soft);"></span>
                    Non-Tunai — {{ $fmtRp($nonTunaiOmzet) }} ({{ $fmtNum($nonTunai->jumlah ?? 0) }} transaksi)
                </p>
            </div>
        </div>

        {{-- Produk terlaris & kurang laku --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">Produk Terlaris (by Qty)</h3>
                <p class="text-xs text-gray-400 mb-4">All-time, top 10 berdasarkan jumlah unit terjual.</p>
                <div class="space-y-3">
                    @foreach ($topProdukQty as $i => $p)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-gray-700 font-medium truncate pr-2">{{ $i + 1 }}. {{ $p->Produk }}</span>
                                <span class="text-gray-500 whitespace-nowrap">{{ $fmtNum($p->total_qty) }} unit</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div style="width: {{ max(2, round($p->total_qty / $maxQty * 100)) }}%; background-color: var(--dw-blue); height: 100%; border-radius: 9999px;"
                                     title="{{ $fmtNum($p->total_qty) }} unit — {{ $fmtRp($p->total_omzet) }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-700 mb-1">Produk Paling Kurang Laku</h3>
                <p class="text-xs text-gray-400 mb-4">All-time, di antara produk yang pernah terjual minimal 1x.</p>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($bottomProduk as $p)
                            <tr>
                                <td class="py-2 text-gray-700 truncate max-w-[220px]">{{ $p->Produk }}</td>
                                <td class="py-2 text-right text-gray-500 whitespace-nowrap">{{ $fmtNum($p->total_qty) }} unit</td>
                            </tr>
                        @empty
                            <tr><td class="py-4 text-center text-gray-400">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Produk by omzet --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Produk dengan Omzet Tertinggi</h3>
            <p class="text-xs text-gray-400 mb-4">All-time, top 10 berdasarkan total omzet.</p>
            <div class="space-y-3">
                @foreach ($topProdukOmzet as $i => $p)
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-700 font-medium truncate pr-2">{{ $i + 1 }}. {{ $p->Produk }}</span>
                            <span class="text-gray-500 whitespace-nowrap">{{ $fmtRp($p->total_omzet) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div style="width: {{ max(2, round($p->total_omzet / $maxOmzetProduk * 100)) }}%; background-color: var(--dw-blue); height: 100%; border-radius: 9999px;"
                                 title="{{ $fmtRp($p->total_omzet) }} — {{ $fmtNum($p->total_qty) }} unit"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top customer --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-1">Top 10 Customer</h3>
            <p class="text-xs text-gray-400 mb-4">Berdasarkan total belanja pada rentang tanggal terpilih.</p>
            @if ($topCustomers->isEmpty())
                <p class="text-sm text-gray-400 py-4 text-center">Tidak ada data pada rentang ini.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase text-gray-400">
                        <tr>
                            <th class="pb-2 w-8">#</th>
                            <th class="pb-2">Customer</th>
                            <th class="pb-2 text-right">Transaksi</th>
                            <th class="pb-2 text-right w-1/3">Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($topCustomers as $i => $c)
                            <tr>
                                <td class="py-2 text-gray-400">{{ $i + 1 }}</td>
                                <td class="py-2 text-gray-800 font-medium">{{ $c->NmCust }} <span class="text-gray-400 font-normal">({{ $c->KdCust }})</span></td>
                                <td class="py-2 text-right text-gray-500">{{ $fmtNum($c->jumlah_transaksi) }}</td>
                                <td class="py-2">
                                    <div class="flex items-center gap-2 justify-end">
                                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden w-full max-w-[140px]">
                                            <div style="width: {{ max(2, round($c->total_belanja / $maxCust * 100)) }}%; background-color: var(--dw-blue); height: 100%; border-radius: 9999px;"></div>
                                        </div>
                                        <span class="text-gray-700 whitespace-nowrap">{{ $fmtRp($c->total_belanja) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
