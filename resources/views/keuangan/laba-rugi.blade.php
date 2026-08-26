<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Laba Rugi</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-laba-rugi { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-laba-rugi .lr-group-title { font-family: var(--font-heading); font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.04em; color: var(--color-text-muted, #6b7280); padding: var(--space-3) 0 var(--space-2); }
            #industry-laba-rugi .lr-subtotal td { border-top: 1px solid var(--color-divider); font-family: var(--font-heading); font-weight: 600; }
            #industry-laba-rugi .lr-negative { color: #b91c1c; }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => ($n < 0 ? '-Rp ' : 'Rp ').number_format(abs($n), 0, ',', '.');
    @endphp

    <div id="industry-laba-rugi">
        <div style="max-width: 1100px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="text-align:center; border-bottom: 2px solid var(--color-divider); padding-bottom: var(--space-3); margin-bottom: var(--space-4);">
                    <div style="font-family: var(--font-heading); font-weight: 700;">{{ config('app.name', 'CV. SPEKTRA DIGITAL ARTWORK') }}</div>
                    <div style="font-family: var(--font-heading); font-weight: 700; text-transform: uppercase;">Laporan Laba Rugi</div>
                    <div class="text-muted" style="font-size: 12px;">Periode {{ \Carbon\Carbon::parse($dari)->translatedFormat('d F Y') }} — {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d F Y') }}</div>
                </div>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 180px;">
                        <label>Dari</label>
                        <input type="date" name="dari" value="{{ $dari }}" class="input">
                    </div>
                    <div class="field" style="width: 180px;">
                        <label>Sampai</label>
                        <input type="date" name="sampai" value="{{ $sampai }}" class="input">
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Terapkan
                    </button>
                    <a href="{{ route('keuangan.laba-rugi') }}" class="btn btn-secondary" style="height: 36px;">Bulan Ini</a>
                    <p class="text-muted" style="font-size: 13px; margin-left: auto;">Periode {{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') }}</p>
                </form>
            </div>

            <section style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);">
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Laba Kotor</div>
                    <div class="{{ $labaKotor < 0 ? 'lr-negative' : '' }}" style="font-family: var(--font-heading); font-weight: 600; font-size: 26px; line-height: 1;">{{ $fmt($labaKotor) }}</div>
                    <div class="card-meta">Pendapatan − HPP</div>
                </div>
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Laba Usaha</div>
                    <div class="{{ $labaUsaha < 0 ? 'lr-negative' : '' }}" style="font-family: var(--font-heading); font-weight: 600; font-size: 26px; line-height: 1;">{{ $fmt($labaUsaha) }}</div>
                    <div class="card-meta">Laba Kotor − Biaya Usaha</div>
                </div>
                <div class="card blueprint" style="background: var(--color-accent-900); color: var(--color-bg); border-color: var(--color-accent-900);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker" style="color: var(--color-accent-300);">Laba Bersih</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 26px; line-height: 1;">{{ $fmt($labaBersih) }}</div>
                </div>
            </section>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 640px;">
                        <thead>
                            <tr><th>Akun</th><th style="text-align: right;">Jumlah</th></tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="2" class="lr-group-title">Pendapatan</td></tr>
                            @forelse ($pendapatan as $row)
                                <tr><td class="text-muted">{{ $row['nama'] }}</td><td style="text-align: right;">{{ $fmt($row['saldo']) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">Tidak ada aktivitas.</td></tr>
                            @endforelse
                            <tr class="lr-subtotal"><td>Total Pendapatan</td><td style="text-align: right;">{{ $fmt($totalPendapatan) }}</td></tr>

                            <tr><td colspan="2" class="lr-group-title">Harga Pokok Produksi &amp; Penjualan</td></tr>
                            @forelse ($hpp as $row)
                                <tr><td class="text-muted">{{ $row['nama'] }}</td><td style="text-align: right;">{{ $fmt($row['saldo']) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">Tidak ada aktivitas.</td></tr>
                            @endforelse
                            <tr class="lr-subtotal"><td>Total HPP</td><td style="text-align: right;">{{ $fmt($totalHpp) }}</td></tr>

                            <tr class="lr-subtotal"><td>Laba Kotor</td><td style="text-align: right;" class="{{ $labaKotor < 0 ? 'lr-negative' : '' }}">{{ $fmt($labaKotor) }}</td></tr>

                            <tr><td colspan="2" class="lr-group-title">Biaya Usaha</td></tr>
                            @forelse ($biayaUsaha as $row)
                                <tr><td class="text-muted">{{ $row['nama'] }}</td><td style="text-align: right;">{{ $fmt($row['saldo']) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">Tidak ada aktivitas.</td></tr>
                            @endforelse
                            <tr class="lr-subtotal"><td>Total Biaya Usaha</td><td style="text-align: right;">{{ $fmt($totalBiayaUsaha) }}</td></tr>

                            <tr class="lr-subtotal"><td>Laba Usaha</td><td style="text-align: right;" class="{{ $labaUsaha < 0 ? 'lr-negative' : '' }}">{{ $fmt($labaUsaha) }}</td></tr>

                            <tr><td colspan="2" class="lr-group-title">Pendapatan &amp; Biaya Lain-lain</td></tr>
                            @forelse ($pendapatanLain->concat($biayaLain) as $row)
                                <tr><td class="text-muted">{{ $row['nama'] }}</td><td style="text-align: right;">{{ $fmt($row['saldo']) }}</td></tr>
                            @empty
                                <tr><td colspan="2" class="text-muted">Tidak ada aktivitas.</td></tr>
                            @endforelse

                            <tr class="lr-subtotal"><td style="font-size: 16px;">Laba Bersih</td><td style="text-align: right; font-size: 16px;" class="{{ $labaBersih < 0 ? 'lr-negative' : '' }}">{{ $fmt($labaBersih) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
