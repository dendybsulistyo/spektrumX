<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Dashboard</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            /* Halaman ini saja yang pakai token Industry — di-scope ke wrapper
               supaya tidak bocor ke navbar/layout app yang masih Tailwind. */
            #industry-dashboard { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-dashboard .tag-danger { background: var(--color-accent-900); color: var(--color-bg); }
            #industry-dashboard .tag-success { background: #d1fae5; color: #065f46; }
            #industry-dashboard .tag-info { background: #dbeafe; color: #1e3a8a; }
            #industry-dashboard .tag-red { background: #fee2e2; color: #991b1b; }
            #industry-dashboard .tag-indigo { background: #e0e7ff; color: #3730a3; }
            #industry-dashboard .tag-cyan { background: #cffafe; color: #155e75; }
            #industry-dashboard .tag-amber { background: #fef3c7; color: #92400e; }
            #industry-dashboard .tag-purple { background: #f3e8ff; color: #6b21a8; }
            #industry-dashboard .tag-pink { background: #fce7f3; color: #9d174d; }
            #industry-dashboard .tag-teal { background: #ccfbf1; color: #115e59; }
        </style>
    @endpush

    @php
        $tagFor = fn (?string $status) => match ($status) {
            'baru' => 'tag tag-outline',
            'desain' => 'tag tag-indigo',
            'cetak' => 'tag tag-cyan',
            'finishing' => 'tag tag-amber',
            'qc' => 'tag tag-purple',
            'bungkus' => 'tag tag-pink',
            'siap_diambil' => 'tag tag-teal',
            'selesai' => 'tag tag-success',
            'batal' => 'tag tag-danger',
            default => 'tag tag-neutral',
        };
        $bayarTagFor = fn (?string $statusBayar) => match ($statusBayar) {
            'lunas' => 'tag tag-success',
            'dp' => 'tag tag-info',
            'belum_bayar', 'hutang' => 'tag tag-red',
            default => 'tag tag-outline',
        };
        $statusLabel = fn (?string $status) => match ($status) {
            'baru' => 'Baru', 'desain' => 'Desain', 'cetak' => 'Cetak', 'finishing' => 'Finishing',
            'qc' => 'QC', 'bungkus' => 'Bungkus', 'siap_diambil' => 'Siap Diambil',
            'selesai' => 'Selesai', 'batal' => 'Batal', default => ucfirst($status ?? '-'),
        };
        $bayarLabel = fn (?string $statusBayar) => match ($statusBayar) {
            'lunas' => 'Lunas', 'belum_bayar' => 'Belum Bayar', 'hutang' => 'Hutang', 'dp' => 'DP', default => '-',
        };
        $cards = [
            ['label' => 'Order masuk', 'value' => $stats['total'], 'meta' => 'Semua tipe order'],
            ['label' => 'Menunggu bayar', 'value' => $stats['belum_bayar'], 'meta' => 'Belum lunas'],
            ['label' => 'Lunas', 'value' => $stats['lunas'], 'meta' => 'Pembayaran diterima'],
            ['label' => 'VIP / Hutang', 'value' => $stats['hutang'], 'meta' => 'Rp '.number_format($stats['hutang_nominal'], 0, ',', '.')],
            ['label' => 'DP / Piutang Berjalan', 'value' => $stats['dp'], 'meta' => 'Rp '.number_format($stats['dp_nominal'], 0, ',', '.')],
        ];
        $stages = [
            ['num' => '01', 'label' => 'Desain', 'count' => $stats['desain']],
            ['num' => '02', 'label' => 'Cetak', 'count' => $stats['cetak']],
            ['num' => '03', 'label' => 'Finishing', 'count' => $stats['finishing']],
            ['num' => '04', 'label' => 'QC', 'count' => $stats['qc']],
            ['num' => '05', 'label' => 'Bungkus', 'count' => $stats['bungkus']],
            ['num' => '06', 'label' => 'Siap Diambil', 'count' => $stats['siap_diambil']],
            ['num' => '07', 'label' => 'Selesai', 'count' => $stats['selesai']],
        ];
        $tipeAbbr = ['Indoor' => 'IN', 'Outdoor' => 'OUT', 'Artwork' => 'ART'];
    @endphp

    <div id="industry-dashboard">
        <div style="max-width: 1480px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-8);">

            <header style="display: flex; align-items: flex-end; justify-content: space-between; gap: var(--space-6); flex-wrap: wrap;">
                <div>
                    <div class="card-kicker" style="margin-bottom: 4px;">Ringkasan operasional</div>
                    <h2 style="margin: 0;">Dashboard</h2>
                </div>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 160px;">
                        <label>Dari tanggal</label>
                        <input class="input" type="date" name="from" value="{{ $from }}">
                    </div>
                    <div class="field" style="width: 160px;">
                        <label>Sampai tanggal</label>
                        <input class="input" type="date" name="to" value="{{ $to }}">
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Terapkan
                    </button>
                    @if ($from || $to)
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="height: 36px;">Reset</a>
                    @endif
                </form>
            </header>

            <section style="display: grid; grid-template-columns: repeat(6, 1fr); gap: var(--space-4);">
                @foreach ($cards as $card)
                    <div class="card blueprint"><i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                        <div class="card-kicker">{{ $card['label'] }}</div>
                        <div style="font-family: var(--font-heading); font-weight: 600; font-size: 40px; line-height: 1;">{{ $card['value'] }}</div>
                        <div class="card-meta">{{ $card['meta'] }}</div>
                    </div>
                @endforeach
                <div class="card blueprint" style="background: #5c0a0a; color: #fff; border-color: #5c0a0a;">
                    <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                    <div class="card-kicker" style="color: #fca5a5; font-weight: 700; font-size: 11px;">Telat &gt; 3&times;24 jam</div>
                    <div style="font-family: var(--font-heading); font-weight: 600; font-size: 40px; line-height: 1; color: #fff;">{{ $stats['telat'] }}</div>
                    <div class="card-meta" style="color: #fca5a5;">Belum siap diambil</div>
                </div>
            </section>

            <section class="blueprint" style="display: grid; grid-template-columns: repeat(7, 1fr);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                @foreach ($stages as $s)
                    <div style="padding: var(--space-3) var(--space-4); border-left: 1px solid var(--color-divider); display: flex; flex-direction: column; gap: 4px;">
                        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 8px;">
                            <span class="card-kicker">{{ $s['label'] }}</span>
                            <span class="text-muted" style="font-size: 10px; letter-spacing: 0.08em;">{{ $s['num'] }}</span>
                        </div>
                        <div style="font-family: var(--font-heading); font-weight: 600; font-size: 32px; line-height: 1;">{{ $s['count'] }}</div>
                    </div>
                @endforeach
            </section>

            <section class="blueprint" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="display: flex; align-items: baseline; justify-content: space-between; gap: var(--space-4); flex-wrap: wrap; margin-bottom: var(--space-4);">
                    <div>
                        <h4 style="margin: 0 0 2px;">Monitoring order terbaru</h4>
                        <div class="text-muted" style="font-size: 12px;">
                            @if ($from || $to)
                                Order dari {{ $from ?: 'awal' }} sampai {{ $to ?: 'sekarang' }} —
                            @else
                                {{ $recent->count() }} order terbaru —
                            @endif
                            alur: kasir &rarr; layout &rarr; cetak &rarr; finishing &rarr; QC &rarr; bungkus &rarr; pengambilan.
                        </div>
                    </div>
                    <span class="tag tag-outline">{{ $recent->count() }} order ditampilkan</span>
                </div>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 1240px;">
                        <thead>
                            <tr>
                                <th style="width: 32px;">No</th><th>No order</th><th>Tipe</th><th>Customer</th><th>Masuk</th><th>Status</th><th>Bayar</th><th style="text-align: right;">Lama proses</th><th>Kasir</th><th>Desain</th><th>Cetak</th><th>Finishing</th><th>QC</th><th>Bungkus</th><th>Ambil</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $row)
                                <tr>
                                    <td class="text-muted">{{ $loop->iteration }}</td>
                                    <td style="font-family: var(--font-heading); font-weight: 600; letter-spacing: 0.03em;">{{ $row['no_order'] }}</td>
                                    <td><span class="tag tag-neutral">{{ $tipeAbbr[$row['tipe']] ?? $row['tipe'] }}</span></td>
                                    <td>{{ $row['customer'] ? ucwords(mb_strtolower($row['customer'])) : '-' }}</td>
                                    <td class="text-muted" style="white-space: nowrap;">{{ $row['created_at']?->format('d M Y H:i') ?? '-' }}</td>
                                    <td style="white-space: nowrap;">
                                        <span class="{{ $tagFor($row['status']) }}">{{ $statusLabel($row['status']) }}</span>
                                        @if ($row['progress'])
                                            <span class="tag tag-outline" style="margin-left: 4px;">{{ $row['progress'] }}</span>
                                        @endif
                                        @if ($row['telat'])
                                            <span class="tag tag-red" style="margin-left: 4px;">Telat</span>
                                        @endif
                                    </td>
                                    <td><span class="{{ $bayarTagFor($row['status_bayar']) }}">{{ $bayarLabel($row['status_bayar']) }}</span></td>
                                    <td style="text-align: right; white-space: nowrap;">{{ $row['durasi'] ?? '-' }}</td>
                                    <td class="text-muted">{{ $row['kasir'] ?? '-' }}</td>
                                    <td class="text-muted">{{ $row['desain_by'] ?? '-' }}</td>
                                    <td class="text-muted">{{ $row['cetak_by'] ?? '-' }}</td>
                                    <td class="text-muted">{{ $row['finishing_by'] ?? '-' }}</td>
                                    <td class="text-muted">{{ $row['qc_by'] ?? '-' }}</td>
                                    <td class="text-muted">{{ $row['bungkus_by'] ?? '-' }}</td>
                                    <td class="text-muted">{{ $row['pengambilan_by'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="15" class="text-muted" style="text-align: center; padding: var(--space-6);">Tidak ada order pada rentang ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
