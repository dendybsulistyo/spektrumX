<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Rekap Kasir Harian Spektrum</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #cashier-summary { margin: calc(var(--space-8) * -1); padding: var(--space-8); min-height: calc(100vh - 132px); background:#eef2f6; color:#172033; font-family:var(--font-body); }
            #cashier-summary .report { overflow:hidden; background:#fff; border:1px solid #d7dee8; border-radius:4px; box-shadow:0 1px 3px rgba(15,23,42,.06); }
            #cashier-summary .report-head { display:flex; justify-content:space-between; gap:24px; padding:22px 24px 16px; border-bottom:2px solid #172033; }
            #cashier-summary .report-head h3 { margin:0; font-size:17px; letter-spacing:.04em; color:#172033; }
            #cashier-summary .report-meta { margin-top:4px; color:#64748b; font-size:12px; line-height:1.55; }
            #cashier-summary .section { padding:18px 24px 2px; }
            #cashier-summary .section-title { display:flex; align-items:center; gap:8px; margin:0 0 8px; color:#334155; font-size:13px; font-weight:700; }
            #cashier-summary .section-title::before { content:""; width:4px; height:15px; background:#2563eb; border-radius:1px; }
            #cashier-summary .ledger { width:100%; border-collapse:collapse; table-layout:fixed; }
            #cashier-summary .ledger th { padding:8px 10px; background:#f1f5f9; border:1px solid #cbd5e1; color:#475569; font-size:11px; letter-spacing:.06em; text-transform:uppercase; }
            #cashier-summary .ledger td { padding:7px 10px; border:1px solid #dce2ea; color:#334155; font-size:13px; line-height:1.35; }
            #cashier-summary .ledger tbody tr:nth-child(even) td { background:#fbfcfe; }
            #cashier-summary .ledger .money { width:180px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
            #cashier-summary .subtotal { display:grid; grid-template-columns:minmax(0,1fr) 180px 180px; }
            #cashier-summary .subtotal > div { padding:8px 10px; border:1px solid #cbd5e1; border-top:0; background:#f8fafc; text-align:right; font-size:13px; font-weight:700; font-variant-numeric:tabular-nums; }
            #cashier-summary .subtotal .label { color:#475569; }
            #cashier-summary .grand-total { display:grid; grid-template-columns:minmax(0,1fr) 180px 180px; margin:22px 24px 24px; }
            #cashier-summary .grand-total > div { padding:10px 12px; border:1px solid #b8c2d0; border-top:0; text-align:right; font-size:14px; font-weight:700; font-variant-numeric:tabular-nums; }
            #cashier-summary .grand-total > div:nth-child(-n+3) { border-top:1px solid #b8c2d0; }
            #cashier-summary .grand-total .label { background:#f1f5f9; color:#475569; }
            #cashier-summary .grand-total .balance-label { grid-column:1 / 3; background:#172033; color:#fff; }
            #cashier-summary .grand-total .balance { background:#172033; color:#fff; border-color:#172033; font-size:17px; }
            @media (max-width:768px) {
                #cashier-summary { margin:-16px; padding:16px; }
                #cashier-summary .report-head { padding:18px 14px; flex-direction:column; gap:8px; }
                #cashier-summary .section { padding-left:14px; padding-right:14px; }
                #cashier-summary .ledger-wrap, #cashier-summary .total-wrap { overflow-x:auto; }
                #cashier-summary .ledger, #cashier-summary .subtotal, #cashier-summary .grand-total { min-width:720px; }
                #cashier-summary .grand-total { margin-left:14px; margin-right:14px; }
            }
            @media print {
                @page { size:A4 portrait; margin:12mm 9mm; }
                nav, #cashier-summary .filter-panel, #chat-widget { display:none !important; }
                html, body { margin:0 !important; padding:0 !important; background:#fff !important; }
                #cashier-summary { width:100%; margin:0; padding:0; min-height:0; background:#fff; print-color-adjust:exact; -webkit-print-color-adjust:exact; }
                #cashier-summary .report { border:0; box-shadow:none; }
                #cashier-summary .report-head { display:flex !important; padding:0 0 8px; }
                #cashier-summary .section { padding:8px 0 1px; break-inside:auto; }
                #cashier-summary .section-title { break-after:avoid; }
                #cashier-summary .ledger-wrap { padding:3mm 0; overflow:visible; box-decoration-break:clone; -webkit-box-decoration-break:clone; }
                #cashier-summary .ledger { width:100%; min-width:0; table-layout:fixed; }
                #cashier-summary .ledger thead { display:table-header-group; }
                #cashier-summary .ledger tr { break-inside:avoid; }
                #cashier-summary .ledger th, #cashier-summary .ledger td { padding:3px 5px; font-size:8pt; }
                #cashier-summary .ledger .money { width:18%; }
                #cashier-summary .subtotal { min-width:0; grid-template-columns:minmax(0,1fr) 18% 18%; }
                #cashier-summary .subtotal > div { padding:3px 5px; font-size:8pt; }
                #cashier-summary .grand-total { min-width:0; grid-template-columns:minmax(0,1fr) 18% 18%; margin:10px 0 0; break-inside:avoid; }
                #cashier-summary .grand-total > div { padding:4px 5px; font-size:8pt; }
                body.print-paper-a5 #cashier-summary .report-head h3 { font-size:10pt; }
                body.print-paper-a5 #cashier-summary .report-meta, body.print-paper-a5 #cashier-summary .section-title { font-size:6.5pt; }
                body.print-paper-a5 #cashier-summary .section { padding-top:5px; }
                body.print-paper-a5 #cashier-summary .ledger-wrap { padding-top:2.5mm; padding-bottom:2.5mm; }
                body.print-paper-a5 #cashier-summary .ledger th, body.print-paper-a5 #cashier-summary .ledger td,
                body.print-paper-a5 #cashier-summary .subtotal > div, body.print-paper-a5 #cashier-summary .grand-total > div { padding:2px 3px; font-size:6.5pt; }
            }
        </style>
    @endpush

    @php
        $number = fn ($value) => number_format((float) $value, 0, ',', '.');
    @endphp

    <div id="cashier-summary">
        <div style="max-width:1240px; width:100%; margin:0 auto; display:flex; flex-direction:column; gap:var(--space-5);">
            <div class="filter-panel blueprint" style="padding:16px; background:#fff; border-color:#d7dee8;">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="GET" style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
                    <div class="field" style="width:220px;">
                        <label>Nama Kasir</label>
                        <select name="kasir" class="input">
                            <option value="">Gabungan Semua Kasir</option>
                            @foreach ($kasirUsers as $kasirUser)
                                <option value="{{ $kasirUser->id }}" @selected($kasirId === $kasirUser->id)>{{ ucwords(mb_strtolower($kasirUser->name)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field" style="width:170px;">
                        <label>Dari tanggal</label>
                        <input type="date" name="dari" value="{{ $dari }}" class="input">
                    </div>
                    <div class="field" style="width:170px;">
                        <label>Sampai tanggal</label>
                        <input type="date" name="sampai" value="{{ $sampai }}" class="input">
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height:36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Terapkan
                    </button>
                    <a href="{{ route('keuangan.rekap-kasir', array_filter(['kasir' => $kasirId])) }}" class="btn btn-secondary" style="height:36px;">Hari Ini</a>
                    <a href="{{ route('keuangan.rekap-kasir.excel', array_filter(['dari' => $dari, 'sampai' => $sampai, 'kasir' => $kasirId])) }}" class="btn btn-secondary" style="height:36px;">Export Excel</a>
                    <x-print-paper-controls />
                </form>
            </div>

            <article class="report">
                <header class="report-head">
                    <div>
                        <h3>REKAP KASIR HARIAN SPEKTRUM</h3>
                        <div class="report-meta">
                            Tanggal: {{ \Carbon\Carbon::parse($dari)->translatedFormat('d F Y') }}{{ $dari !== $sampai ? ' s/d '.\Carbon\Carbon::parse($sampai)->translatedFormat('d F Y') : '' }}
                        </div>
                    </div>
                    <div class="report-meta" style="text-align:right;">
                        <div>{{ $selectedKasir ? 'Kasir: '.ucwords(mb_strtolower($selectedKasir->name)) : 'Gabungan '.$cashierCount.' kasir' }}</div>
                        <div>{{ $jumlahTransaksi }} baris transaksi</div>
                    </div>
                </header>

                @forelse ($sections as $section)
                    <section class="section">
                        <h4 class="section-title">{{ $section['label'] }}</h4>
                        <div class="ledger-wrap">
                            <table class="ledger">
                                <thead>
                                    <tr>
                                        <th>Keterangan</th>
                                        <th class="money">Debet</th>
                                        <th class="money">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section['rows'] as $row)
                                        <tr>
                                            <td>{{ $row['description'] }}</td>
                                            <td class="money">{{ $number($row['debit']) }}</td>
                                            <td class="money">{{ $number($row['credit']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if ($section['key'] !== 'opening')
                                <div class="subtotal">
                                    <div class="label">Sub Total:</div>
                                    <div>{{ $number($section['debit']) }}</div>
                                    <div>{{ $number($section['credit']) }}</div>
                                </div>
                            @endif
                        </div>
                    </section>
                @empty
                    <div style="padding:48px 24px; text-align:center; color:#64748b;">Belum ada transaksi Kasir pada periode ini.</div>
                @endforelse

                @if ($sections->isNotEmpty())
                    <div class="total-wrap">
                        <div class="grand-total">
                            <div class="label">Total:</div>
                            <div>{{ $number($totalDebet) }}</div>
                            <div>{{ $number($totalKredit) }}</div>
                            <div class="balance-label">SALDO KAS:</div>
                            <div class="balance">{{ $number($saldoKas) }}</div>
                        </div>
                    </div>
                @endif
            </article>
        </div>
    </div>
</x-app-layout>
