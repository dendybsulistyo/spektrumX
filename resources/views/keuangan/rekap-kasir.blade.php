<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Rekap Harian per Op. Kasir</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-rekap-kasir { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div id="industry-rekap-kasir">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

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
                    <a href="{{ route('keuangan.rekap-kasir') }}" class="btn btn-secondary" style="height: 36px;">Hari Ini</a>
                    <p class="text-muted" style="font-size: 13px; margin-left: auto;">{{ $jumlahTransaksi }} transaksi &middot; {{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }}{{ $dari !== $sampai ? ' – '.\Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') : '' }}</p>
                </form>
            </div>

            <section style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);">
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Total Masuk</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 31px; line-height: 1;">{{ $fmt($totalMasuk) }}</div>
                </div>
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Total Keluar (Refund)</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 31px; line-height: 1; color: #991b1b;">{{ $fmt($totalKeluar) }}</div>
                </div>
                <div class="card blueprint" style="background: var(--color-accent-900); color: var(--color-bg); border-color: var(--color-accent-900);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker" style="color: var(--color-accent-300);">Total Bersih</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 31px; line-height: 1;">{{ $fmt($totalMasuk - $totalKeluar) }}</div>
                </div>
            </section>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <h4 style="margin: 0 0 var(--space-4);">Per Operator Kasir</h4>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 640px;">
                        <thead>
                            <tr>
                                <th>Kasir</th><th style="text-align: right;">Jumlah Transaksi</th><th style="text-align: right;">Masuk</th><th style="text-align: right;">Keluar</th><th style="text-align: right;">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td style="font-family: var(--font-heading); font-weight: 600;">{{ $row['kasir'] }}</td>
                                    <td class="text-muted" style="text-align: right;">{{ $row['jumlah_transaksi'] }}</td>
                                    <td style="text-align: right;">{{ $fmt($row['masuk']) }}</td>
                                    <td style="text-align: right; color: #991b1b;">{{ $row['keluar'] > 0 ? $fmt($row['keluar']) : '-' }}</td>
                                    <td style="text-align: right; font-family: var(--font-heading); font-weight: 600;">{{ $fmt($row['net']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted" style="text-align: center; padding: var(--space-6);">Belum ada transaksi pada rentang tanggal ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
