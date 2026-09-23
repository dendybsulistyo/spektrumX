<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Rekap Kas Harian</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #industry-kas { font-family: var(--font-body); color: #172033; background: #eef2f6; margin: calc(var(--space-8) * -1); padding: var(--space-8); min-height: calc(100vh - 132px); }
            #industry-kas .report-shell { overflow: hidden; background: #fff; border: 1px solid #d7dee8; border-radius: 4px; box-shadow: 0 1px 3px rgba(15, 23, 42, .06); }
            #industry-kas .report-title { padding: 22px 24px 16px; border-bottom: 2px solid #172033; }
            #industry-kas .report-title h3 { margin: 0; color: #172033; font-size: 17px; letter-spacing: .04em; }
            #industry-kas .report-title p { margin: 4px 0 0; color: #64748b; font-size: 13px; }
            #industry-kas .cashier-section { padding: 18px 24px 4px; }
            #industry-kas .cashier-name { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 13px; color: #64748b; }
            #industry-kas .cashier-name strong { color: #172033; font-size: 14px; }
            #industry-kas .ledger { width: 100%; border-collapse: collapse; table-layout: fixed; }
            #industry-kas .ledger th { padding: 8px 10px; background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; font-size: 11px; letter-spacing: .06em; text-transform: uppercase; }
            #industry-kas .ledger td { padding: 7px 10px; border: 1px solid #dce2ea; color: #334155; font-size: 13px; line-height: 1.35; }
            #industry-kas .ledger .invoice { width: 190px; color: #475569; font-family: var(--font-heading); }
            #industry-kas .ledger .money { width: 170px; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
            #industry-kas .ledger tbody tr:nth-child(even) td { background: #fbfcfe; }
            #industry-kas .subtotal { display: grid; grid-template-columns: minmax(0, 1fr) 170px 170px; margin-left: 190px; }
            #industry-kas .subtotal > div { padding: 8px 10px; border: 1px solid #cbd5e1; border-top: 0; background: #f8fafc; font-size: 13px; font-weight: 700; text-align: right; font-variant-numeric: tabular-nums; }
            #industry-kas .subtotal .label { color: #475569; }
            #industry-kas .report-total { display: grid; grid-template-columns: minmax(0, 1fr) 170px 170px; margin: 20px 24px 24px 214px; }
            #industry-kas .report-total > div { padding: 10px 12px; border: 1px solid #b8c2d0; border-top: 0; text-align: right; font-size: 14px; font-weight: 700; font-variant-numeric: tabular-nums; }
            #industry-kas .report-total > div:nth-child(-n+3) { border-top: 1px solid #b8c2d0; }
            #industry-kas .report-total .label { background: #f1f5f9; color: #475569; }
            #industry-kas .report-total .balance-label { background: #172033; color: #fff; grid-column: 1 / 3; }
            #industry-kas .report-total .balance { background: #172033; color: #fff; border-color: #172033; font-size: 17px; }
            @media (max-width: 768px) {
                #industry-kas { margin: -16px; padding: 16px; }
                #industry-kas .cashier-section, #industry-kas .report-title { padding-left: 14px; padding-right: 14px; }
                #industry-kas .ledger-wrap, #industry-kas .totals-wrap { overflow-x: auto; }
                #industry-kas .ledger { min-width: 760px; }
                #industry-kas .subtotal { min-width: 570px; margin-left: 190px; }
                #industry-kas .report-total { min-width: 570px; margin-left: 204px; margin-right: 14px; }
            }
            @media print {
                @page { size: A4 portrait; margin: 12mm 9mm; }
                nav, #industry-kas .filter-panel, #chat-widget { display: none !important; }
                html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
                #industry-kas { width: 100%; margin: 0; padding: 0; min-height: 0; background: #fff; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
                #industry-kas .report-shell { border: 0; box-shadow: none; }
                #industry-kas .report-title { display: block !important; padding: 0 0 8px; }
                #industry-kas .cashier-section { padding: 8px 0 2px; }
                #industry-kas .cashier-name { margin-bottom: 4px; }
                #industry-kas .ledger-wrap { padding: 3mm 0; box-decoration-break: clone; -webkit-box-decoration-break: clone; }
                #industry-kas .ledger { width: 100%; min-width: 0; table-layout: fixed; }
                #industry-kas .ledger thead { display: table-header-group; }
                #industry-kas .ledger tr { break-inside: avoid; }
                #industry-kas .ledger th, #industry-kas .ledger td { padding: 3px 5px; font-size: 8pt; }
                #industry-kas .ledger .invoice { width: 24%; }
                #industry-kas .ledger .money { width: 18%; }
                #industry-kas .subtotal { grid-template-columns: minmax(0, 1fr) 18% 18%; margin-left: 24%; }
                #industry-kas .subtotal > div { padding: 3px 5px; font-size: 8pt; }
                #industry-kas .report-total { grid-template-columns: minmax(0, 1fr) 18% 18%; margin: 10px 0 0 24%; break-inside: avoid; }
                #industry-kas .report-total > div { padding: 4px 5px; font-size: 8pt; }
                body.print-paper-a5 #industry-kas .report-title h3 { font-size: 10pt; }
                body.print-paper-a5 #industry-kas .report-title p, body.print-paper-a5 #industry-kas .cashier-name { font-size: 6.5pt; }
                body.print-paper-a5 #industry-kas .cashier-section { padding-top: 5px; }
                body.print-paper-a5 #industry-kas .ledger-wrap { padding-top: 2.5mm; padding-bottom: 2.5mm; }
                body.print-paper-a5 #industry-kas .ledger th, body.print-paper-a5 #industry-kas .ledger td,
                body.print-paper-a5 #industry-kas .subtotal > div, body.print-paper-a5 #industry-kas .report-total > div { padding: 2px 3px; font-size: 6.5pt; }
            }
        </style>
    @endpush

    @php
        $fmt = fn ($value) => number_format((float) $value, 0, ',', '.');
    @endphp

    <div id="industry-kas">
        <div style="max-width: 1240px; margin: 0 auto; display: flex; flex-direction: column; gap: var(--space-5);">
            <div class="filter-panel blueprint" style="padding: var(--space-4); background:#fff; border-color:#d7dee8;">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="GET" style="display: flex; align-items: flex-end; gap: var(--space-3); flex-wrap: wrap;">
                    <div class="field" style="width: 220px;">
                        <label>Nama Kasir</label>
                        <select name="kasir" class="input">
                            <option value="">Semua Kasir</option>
                            @foreach ($kasirUsers as $kasirUser)
                                <option value="{{ $kasirUser->id }}" @selected($kasirId === $kasirUser->id)>{{ ucwords(mb_strtolower($kasirUser->name)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field" style="width: 190px;">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" value="{{ $tanggal }}" class="input">
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height: 36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Terapkan
                    </button>
                    <a href="{{ route('keuangan.kas-harian', array_filter(['kasir' => $kasirId])) }}" class="btn btn-secondary" style="height: 36px;">Hari Ini</a>
                    <a href="{{ route('keuangan.kas-harian.excel', array_filter(['tanggal' => $tanggal, 'kasir' => $kasirId])) }}" class="btn btn-secondary" style="height:36px;">Export Excel</a>
                    <x-print-paper-controls />
                    <p class="text-muted" style="font-size: 13px; margin-left: auto;">
                        {{ $jumlahTransaksi }} baris transaksi{{ $selectedKasir?->name ? ' · '.ucwords(mb_strtolower($selectedKasir->name)) : '' }}
                    </p>
                </form>
            </div>

            <article class="report-shell">
                <header class="report-title">
                    <h3>REKAP KASIR HARIAN PER USER SPEKTRUM</h3>
                    <p>Tanggal: {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</p>
                </header>

                @forelse ($groups as $group)
                    <section class="cashier-section">
                        <div class="cashier-name">User: <strong>{{ $group['label'] }}</strong></div>
                        <div class="ledger-wrap">
                            <table class="ledger">
                                <thead>
                                    <tr>
                                        <th class="invoice">No. Nota</th>
                                        <th>Keterangan</th>
                                        <th class="money">Debet</th>
                                        <th class="money">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($group['rows'] as $row)
                                        <tr>
                                            <td class="invoice">{{ $row['no_nota'] ?: '' }}</td>
                                            <td>{{ $row['keterangan'] }}</td>
                                            <td class="money">{{ $fmt($row['debet']) }}</td>
                                            <td class="money">{{ $fmt($row['kredit']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="subtotal">
                                <div class="label">Sub Total:</div>
                                <div>{{ $fmt($group['subtotal_debet']) }}</div>
                                <div>{{ $fmt($group['subtotal_kredit']) }}</div>
                            </div>
                        </div>
                    </section>
                @empty
                    <div style="padding:48px 24px; text-align:center; color:#64748b;">Belum ada transaksi pada tanggal ini.</div>
                @endforelse

                @if ($groups->isNotEmpty())
                    <div class="totals-wrap">
                        <div class="report-total">
                            <div class="label">Total:</div>
                            <div>{{ $fmt($totalDebet) }}</div>
                            <div>{{ $fmt($totalKredit) }}</div>
                            <div class="balance-label">SALDO KAS:</div>
                            <div class="balance">{{ $fmt($saldoKas) }}</div>
                        </div>
                    </div>
                @endif
            </article>
        </div>
    </div>
</x-app-layout>
