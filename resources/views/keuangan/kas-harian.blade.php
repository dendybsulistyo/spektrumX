<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Rekap Kas Harian</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-kas { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-kas .tag-tunai { background: #fef3c7; color: #92400e; }
            #industry-kas .tag-qris { background: #dbeafe; color: #1e3a8a; }
            #industry-kas .tag-transfer { background: #ede9fe; color: #5b21b6; }
        </style>
    @endpush

    @php
        $caraBayarTag = fn (?string $cb) => match ($cb) {
            'tunai' => 'tag tag-tunai',
            'qris' => 'tag tag-qris',
            'transfer' => 'tag tag-transfer',
            default => 'tag tag-neutral',
        };
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div id="industry-kas">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint" style="padding: var(--space-4);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 200px;">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" value="{{ $tanggal }}" class="input">
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Terapkan
                    </button>
                    <a href="{{ route('keuangan.kas-harian') }}" class="btn btn-secondary" style="height: 36px;">Hari Ini</a>
                    <p class="text-muted" style="font-size: 13px; margin-left: auto;">{{ $jumlahTransaksi }} transaksi pada {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</p>
                </form>
            </div>

            <section style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-4);">
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Tunai</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 31px; line-height: 1;">{{ $fmt($summary['tunai']['masuk'] - $summary['tunai']['keluar']) }}</div>
                    <div class="text-muted" style="font-size: 12px; margin-top: 4px;">Masuk {{ $fmt($summary['tunai']['masuk']) }} &middot; Keluar {{ $fmt($summary['tunai']['keluar']) }}</div>
                </div>
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">QRIS</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 31px; line-height: 1;">{{ $fmt($summary['qris']['masuk'] - $summary['qris']['keluar']) }}</div>
                    <div class="text-muted" style="font-size: 12px; margin-top: 4px;">Masuk {{ $fmt($summary['qris']['masuk']) }} &middot; Keluar {{ $fmt($summary['qris']['keluar']) }}</div>
                </div>
                <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker">Transfer</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 31px; line-height: 1;">{{ $fmt($summary['transfer']['masuk'] - $summary['transfer']['keluar']) }}</div>
                    <div class="text-muted" style="font-size: 12px; margin-top: 4px;">Masuk {{ $fmt($summary['transfer']['masuk']) }} &middot; Keluar {{ $fmt($summary['transfer']['keluar']) }}</div>
                </div>
                <div class="card blueprint" style="background: var(--color-accent-900); color: var(--color-bg); border-color: var(--color-accent-900);">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker" style="color: var(--color-accent-300);">Total Kas Bersih</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 31px; line-height: 1;">{{ $fmt($totalNet) }}</div>
                    <div style="font-size: 12px; margin-top: 4px; color: var(--color-accent-300);">Masuk {{ $fmt($totalMasuk) }} &middot; Keluar {{ $fmt($totalKeluar) }}</div>
                </div>
            </section>

            <div class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <h4 style="margin: 0 0 var(--space-4);">Rincian Transaksi</h4>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 1080px;">
                        <thead>
                            <tr>
                                <th>Waktu</th><th>No order</th><th>Tipe</th><th>Customer</th><th>Jenis</th><th>Cara bayar</th><th>No. referensi</th><th style="text-align: right;">Debit (masuk)</th><th style="text-align: right;">Kredit (keluar)</th><th>Kasir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="text-muted" style="white-space: nowrap;">{{ $row['waktu']?->format('H:i') }}</td>
                                    <td style="font-family: var(--font-heading); font-weight: 600;">{{ $row['no_order'] }}</td>
                                    <td><span class="tag tag-neutral">{{ $row['tipe'] }}</span></td>
                                    <td>{{ $row['customer'] }}</td>
                                    <td class="text-muted">{{ $row['jenis'] }}</td>
                                    <td><span class="{{ $caraBayarTag($row['cara_bayar']) }}">{{ $row['cara_bayar_label'] }}</span></td>
                                    <td class="text-muted">{{ $row['no_referensi'] ?? '-' }}</td>
                                    <td style="text-align: right; font-family: var(--font-heading); font-weight: 600;">{{ $row['debit'] ? $fmt($row['debit']) : '-' }}</td>
                                    <td style="text-align: right; font-family: var(--font-heading); font-weight: 600; color: #991b1b;">{{ $row['kredit'] ? $fmt($row['kredit']) : '-' }}</td>
                                    <td class="text-muted">{{ $row['kasir'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-muted" style="text-align: center; padding: var(--space-6);">Belum ada transaksi pada tanggal ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
