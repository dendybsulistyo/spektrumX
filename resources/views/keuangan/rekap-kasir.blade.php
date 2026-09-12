<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Rekap Harian per Op. Kasir</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-rekap-kasir { font-family: var(--font-body); color: var(--color-text); background: var(--color-bg); margin: calc(var(--space-8) * -1); padding: var(--space-8); }
            #industry-rekap-kasir .cashier-detail { width:100%; border-collapse:collapse; font-size:12px; }
            #industry-rekap-kasir .cashier-detail th,#industry-rekap-kasir .cashier-detail td { border:1px solid #64748b; padding:5px 7px; }
            #industry-rekap-kasir .cashier-detail th { background:#e2e8f0; text-align:center; }
            #industry-rekap-kasir .cashier-detail .number { text-align:right; white-space:nowrap; }
            @media print {
                @page { size:A4 portrait; margin:10mm; }
                body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
                #industry-rekap-kasir { margin:0; padding:0; background:#fff; }
                #industry-rekap-kasir .screen-summary { display:none !important; }
                #industry-rekap-kasir .print-report { box-shadow:none !important; border:0 !important; padding:0 !important; }
                #industry-rekap-kasir .cashier-group { break-inside:avoid; margin-bottom:14px !important; }
                #industry-rekap-kasir .cashier-detail { font-family:Arial,sans-serif; font-size:8pt; }
                #industry-rekap-kasir .cashier-detail th,#industry-rekap-kasir .cashier-detail td { padding:2px 4px; }
            }
        </style>
    @endpush

    @php
        $fmt = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    @endphp

    <div id="industry-rekap-kasir">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-6);">

            <div class="blueprint no-print" style="padding: var(--space-4);">
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
                    <button type="button" onclick="window.print()" class="btn btn-secondary" style="height:36px;">Cetak</button>
                    <p class="text-muted" style="font-size: 13px; margin-left: auto;">{{ $jumlahTransaksi }} transaksi &middot; {{ \Carbon\Carbon::parse($dari)->translatedFormat('d M Y') }}{{ $dari !== $sampai ? ' – '.\Carbon\Carbon::parse($sampai)->translatedFormat('d M Y') : '' }}</p>
                </form>
            </div>

            <section class="screen-summary" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-4);">
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

            <div class="blueprint screen-summary" style="padding: var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <h4 style="margin: 0 0 var(--space-4);">Per Operator Kasir</h4>
                <div style="overflow-x: auto;">
                    <table class="table" style="min-width: 640px;">
                        <thead>
                            <tr>
                                <th>Kasir</th><th style="text-align: right;">Jumlah Transaksi</th><th style="text-align: right;">Masuk</th><th style="text-align: right;">Keluar</th><th style="text-align: right;">Net</th><th style="text-align: right;">Aksi</th>
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
                                    <td style="text-align: right;">
                                        @if ($row['user_id'])
                                            <a href="{{ route('keuangan.rekap-kasir.customer', ['kasir' => $row['user_id'], 'dari' => $dari, 'sampai' => $sampai]) }}" class="text-muted" style="font-size: 13px;">
                                                Lihat Customer &rarr;
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted" style="text-align: center; padding: var(--space-6);">Belum ada transaksi pada rentang tanggal ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="blueprint print-report" style="padding:var(--space-6);">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <div style="margin-bottom:16px;">
                    <h3 style="margin:0;font-size:16px;">REKAP KASIR HARIAN PER USER SPEKTRUM</h3>
                    <strong>Tanggal: {{ \Carbon\Carbon::parse($dari)->translatedFormat('d F Y') }}{{ $dari !== $sampai ? ' s/d '.\Carbon\Carbon::parse($sampai)->translatedFormat('d F Y') : '' }}</strong>
                </div>
                @forelse($groups as $group)
                    <section class="cashier-group" style="margin-bottom:20px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                            <strong>User: {{ $group['kasir'] }}</strong>
                            @if($group['user_id'])<a class="no-print text-muted" href="{{ route('keuangan.rekap-kasir.customer', ['kasir'=>$group['user_id'],'dari'=>$dari,'sampai'=>$sampai]) }}">Lihat Customer &rarr;</a>@endif
                        </div>
                        @foreach($group['sections'] as $section)
                            <div style="margin-top:9px;font-size:11px;font-weight:700;">{{ $section['name'] }}</div>
                            <table class="cashier-detail">
                                <thead><tr><th style="width:25%;">NO. NOTA</th><th>KETERANGAN</th><th style="width:18%;">DEBET</th><th style="width:18%;">KREDIT</th></tr></thead>
                                <tbody>
                                    @foreach($section['rows'] as $detail)<tr><td>{{ $detail['number'] }}</td><td>{{ $detail['description'] }}</td><td class="number">{{ number_format($detail['debit'],0,',','.') }}</td><td class="number">{{ number_format($detail['credit'],0,',','.') }}</td></tr>@endforeach
                                </tbody>
                                <tfoot><tr><td colspan="2" class="number"><strong>Sub Total:</strong></td><td class="number"><strong>{{ number_format($section['debit'],0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($section['credit'],0,',','.') }}</strong></td></tr></tfoot>
                            </table>
                        @endforeach
                        <table class="cashier-detail" style="margin-top:9px;"><tfoot><tr><td class="number"><strong>Total User {{ $group['kasir'] }}:</strong></td><td class="number" style="width:18%;"><strong>{{ number_format($group['debit'],0,',','.') }}</strong></td><td class="number" style="width:18%;"><strong>{{ number_format($group['credit'],0,',','.') }}</strong></td></tr></tfoot></table>
                    </section>
                @empty
                    <p class="text-muted" style="text-align:center;padding:24px;">Belum ada transaksi kasir pada periode ini.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
