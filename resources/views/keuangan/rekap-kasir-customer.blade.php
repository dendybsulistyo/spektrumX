<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Customer per Kasir — {{ $kasirUser->name }}</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-rekap-kasir-customer { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div id="industry-rekap-kasir-customer">
        <div style="max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <a href="{{ route('keuangan.rekap-kasir', ['dari' => $dari, 'sampai' => $sampai]) }}" class="text-muted" style="font-size: 13px;">&larr; Kembali ke Rekap Kasir</a>

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 180px;">
                        <label>Dari tanggal</label>
                        <input type="date" name="dari" value="{{ $dari }}" class="input">
                    </div>
                    <div class="field" style="width: 180px;">
                        <label>Sampai tanggal</label>
                        <input type="date" name="sampai" value="{{ $sampai }}" class="input">
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Terapkan
                    </button>
                    <p class="text-muted" style="font-size: 13px; margin-left: auto;">{{ $rows->count() }} customer &middot; {{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }}{{ $dari !== $sampai ? ' – '.\Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') : '' }}</p>
                </form>
            </div>

            <section style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);">
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Tunai</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 27px; line-height: 1;">{{ $fmt($totalTunai) }}</div>
                </div>
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">QRIS</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 27px; line-height: 1;">{{ $fmt($totalQris) }}</div>
                </div>
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Transfer</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 27px; line-height: 1;">{{ $fmt($totalTransfer) }}</div>
                </div>
            </section>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <h4 style="margin: 0 0 var(--space-4);">Customer yang dilayani {{ $kasirUser->name }}</h4>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 860px;">
                        <thead>
                            <tr>
                                <th style="width: 32px;">No</th><th>Customer</th><th style="text-align: right;">Indoor</th><th style="text-align: right;">Outdoor</th>
                                <th style="text-align: right;">Tunai</th><th style="text-align: right;">QRIS</th><th style="text-align: right;">Transfer</th>
                                <th style="text-align: right;">Total Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="text-muted">{{ $loop->iteration }}</td>
                                    <td style="font-family: var(--font-heading); font-weight: 600;">
                                        {{ $row['nama'] }}
                                        @if ($row['kode'])
                                            <span class="text-muted" style="font-size: 12px; font-weight: 400;">({{ $row['kode'] }})</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">{{ $row['indoor'] ?: '-' }}</td>
                                    <td style="text-align: right;">{{ $row['outdoor'] ?: '-' }}</td>
                                    <td style="text-align: right;">{{ $row['tunai'] > 0 ? $fmt($row['tunai']) : '-' }}</td>
                                    <td style="text-align: right;">{{ $row['qris'] > 0 ? $fmt($row['qris']) : '-' }}</td>
                                    <td style="text-align: right;">{{ $row['transfer'] > 0 ? $fmt($row['transfer']) : '-' }}</td>
                                    <td style="text-align: right; font-family: var(--font-heading); font-weight: 600;">{{ $fmt($row['total_dibayar']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada order yang diproses kasir ini pada rentang tanggal ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
