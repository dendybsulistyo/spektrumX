<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Laporan Kasir Harian</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            #daily-cashier-ledger { margin:calc(var(--space-8) * -1); padding:var(--space-8); min-height:calc(100vh - 132px); background:#eef2f6; color:#172033; font-family:var(--font-body); }
            #daily-cashier-ledger .report { overflow:hidden; background:#fff; border:1px solid #d7dee8; border-radius:4px; box-shadow:0 1px 3px rgba(15,23,42,.06); }
            #daily-cashier-ledger .report-head { display:flex; justify-content:space-between; gap:24px; padding:22px 24px 16px; border-bottom:2px solid #172033; }
            #daily-cashier-ledger .report-head h3 { margin:0; color:#172033; font-size:17px; letter-spacing:.04em; }
            #daily-cashier-ledger .report-meta { margin-top:4px; color:#64748b; font-size:12px; line-height:1.55; }
            #daily-cashier-ledger .ledger-wrap { padding:20px 24px 24px; overflow-x:auto; }
            #daily-cashier-ledger .ledger { width:100%; min-width:920px; border-collapse:collapse; table-layout:fixed; }
            #daily-cashier-ledger .ledger th { padding:8px 9px; background:#f1f5f9; border:1px solid #cbd5e1; color:#475569; font-size:11px; letter-spacing:.06em; text-transform:uppercase; }
            #daily-cashier-ledger .ledger td { padding:7px 9px; border:1px solid #dce2ea; color:#334155; font-size:12px; line-height:1.35; }
            #daily-cashier-ledger .ledger tbody tr:nth-child(even) td { background:#fbfcfe; }
            #daily-cashier-ledger .ledger .time { width:135px; color:#64748b; white-space:nowrap; font-variant-numeric:tabular-nums; }
            #daily-cashier-ledger .ledger .invoice { width:180px; color:#334155; font-family:var(--font-heading); }
            #daily-cashier-ledger .ledger .money { width:155px; text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
            #daily-cashier-ledger .totals td { background:#f1f5f9; font-size:13px; font-weight:700; }
            #daily-cashier-ledger .balance td { background:#172033; color:#fff; border-color:#172033; font-size:14px; font-weight:700; }
            @media (max-width:768px) {
                #daily-cashier-ledger { margin:-16px; padding:16px; }
                #daily-cashier-ledger .report-head { padding:18px 14px; flex-direction:column; gap:8px; }
                #daily-cashier-ledger .ledger-wrap { padding:14px; }
            }
            @media print {
                @page { size:A4 portrait; margin:12mm 9mm; }
                nav, #daily-cashier-ledger .filter-panel, #chat-widget { display:none !important; }
                html, body { margin:0 !important; padding:0 !important; background:#fff !important; }
                #daily-cashier-ledger { width:100%; margin:0; padding:0; min-height:0; background:#fff; print-color-adjust:exact; -webkit-print-color-adjust:exact; }
                #daily-cashier-ledger .report { border:0; box-shadow:none; }
                #daily-cashier-ledger .report-head { display:flex !important; padding:0 0 10px; }
                #daily-cashier-ledger .ledger-wrap { padding:3mm 0; overflow:visible; box-decoration-break:clone; -webkit-box-decoration-break:clone; }
                #daily-cashier-ledger .ledger { width:100%; min-width:0; font-size:7pt; table-layout:fixed; }
                #daily-cashier-ledger .ledger thead { display:table-header-group; }
                #daily-cashier-ledger .ledger tr { break-inside:avoid; }
                #daily-cashier-ledger .ledger th, #daily-cashier-ledger .ledger td { padding:2px 4px; font-size:7pt; }
                #daily-cashier-ledger .ledger .time { width:16%; }
                #daily-cashier-ledger .ledger .invoice { width:22%; }
                #daily-cashier-ledger .ledger .money { width:15%; }
                #daily-cashier-ledger .ledger tfoot { break-inside:avoid; }
                body.print-paper-a5 #daily-cashier-ledger .report-head h3 { font-size:10pt; }
                body.print-paper-a5 #daily-cashier-ledger .report-meta { font-size:6.5pt; }
                body.print-paper-a5 #daily-cashier-ledger .report-head { padding-bottom:6px; }
                body.print-paper-a5 #daily-cashier-ledger .ledger-wrap { padding-top:2.5mm; padding-bottom:2.5mm; }
                body.print-paper-a5 #daily-cashier-ledger .ledger th, body.print-paper-a5 #daily-cashier-ledger .ledger td { padding:1.5px 2px; font-size:5.8pt; }
            }
        </style>
    @endpush

    @php
        $number = fn ($value) => number_format((float) $value, 0, ',', '.');
    @endphp

    <div id="daily-cashier-ledger">
        <div style="max-width:1320px; width:100%; margin:0 auto; display:flex; flex-direction:column; gap:var(--space-5);">
            <div class="filter-panel blueprint" style="padding:16px; background:#fff; border-color:#d7dee8;">
                <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>
                <form method="GET" style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
                    <div class="field" style="width:220px;">
                        <label>Nama Kasir</label>
                        <select name="kasir" class="input">
                            <option value="">Semua Kasir</option>
                            @foreach ($kasirUsers as $kasirUser)
                                <option value="{{ $kasirUser->id }}" @selected($kasirId === $kasirUser->id)>{{ ucwords(mb_strtolower($kasirUser->name)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field" style="width:190px;">
                        <label>Tanggal transaksi</label>
                        <input type="date" name="tanggal" value="{{ $tanggal }}" class="input">
                    </div>
                    <button type="submit" class="btn btn-primary blueprint" style="height:36px;">
                        <i class="corner tl"></i><i class="corner tr"></i><i class="corner bl"></i><i class="corner br"></i>Terapkan
                    </button>
                    <a href="{{ route('keuangan.laporan-kasir-harian', array_filter(['kasir' => $kasirId])) }}" class="btn btn-secondary" style="height:36px;">Hari Ini</a>
                    <a href="{{ route('keuangan.laporan-kasir-harian.excel', array_filter(['tanggal' => $tanggal, 'kasir' => $kasirId])) }}" class="btn btn-secondary" style="height:36px;">Export Excel</a>
                    <x-print-paper-controls />
                </form>
            </div>

            <article class="report">
                <header class="report-head">
                    <div>
                        <h3>LAPORAN KASIR HARIAN SPEKTRUM</h3>
                        <div class="report-meta">Tanggal: {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</div>
                    </div>
                    <div class="report-meta" style="text-align:right;">
                        <div>{{ $selectedKasir ? 'Kasir: '.ucwords(mb_strtolower($selectedKasir->name)) : 'Semua Kasir' }}</div>
                        <div>{{ $jumlahTransaksi }} baris transaksi</div>
                    </div>
                </header>

                <div class="ledger-wrap">
                    <table class="ledger">
                        <thead>
                            <tr>
                                <th class="time">Tanggal &amp; Jam</th>
                                <th class="invoice">No. Nota</th>
                                <th>Keterangan</th>
                                <th class="money">Debet</th>
                                <th class="money">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="time">{{ $row['occurred_at']->format('d/m/Y H:i') }}</td>
                                    <td class="invoice">{{ $row['no_nota'] ?: '' }}</td>
                                    <td>{{ $row['keterangan'] }}</td>
                                    <td class="money">{{ $number($row['debet']) }}</td>
                                    <td class="money">{{ $number($row['kredit']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="totals">
                                <td colspan="3" style="text-align:right;">TOTAL:</td>
                                <td class="money">{{ $number($totalDebet) }}</td>
                                <td class="money">{{ $number($totalKredit) }}</td>
                            </tr>
                            <tr class="balance">
                                <td colspan="4" style="text-align:right;">SALDO KAS:</td>
                                <td class="money">{{ $number($saldoKas) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </article>
        </div>
    </div>
</x-app-layout>
