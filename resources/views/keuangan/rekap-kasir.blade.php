<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Rekap Harian per Op. Kasir</h2>
    </x-slot>

    @push('styles')
        <link rel="stylesheet" href="{{ asset('_ds/industry-8c70c3bf-fa3d-4d54-8c9e-e44ac24ed178/styles.css') }}">
        <style>
            .cashier-report-page {
                width: 100%;
                max-width: 1120px;
                margin: 0 auto;
                padding: 34px 38px 42px;
                background: #fff;
                color: #111827;
                border: 1px solid #cbd5e1;
                box-shadow: 0 2px 8px rgb(15 23 42 / 8%);
                font-family: Arial, sans-serif;
            }

            .cashier-report-head {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 24px;
                margin-bottom: 22px;
            }

            .cashier-report-title {
                margin: 0;
                font-size: 17px;
                font-weight: 700;
                letter-spacing: .02em;
            }

            .cashier-report-meta {
                margin-top: 5px;
                font-size: 12px;
                line-height: 1.55;
                color: #334155;
            }

            .cashier-user-block + .cashier-user-block { margin-top: 25px; }
            .cashier-user-name { margin-bottom: 6px; font-size: 13px; font-weight: 700; }
            .cashier-user-name a { float: right; color: #475569; font-size: 11px; font-weight: 500; text-decoration: none; }

            .cashier-ledger {
                width: 100%;
                min-width: 760px;
                border-collapse: collapse;
                table-layout: fixed;
                font-size: 12px;
            }

            .cashier-ledger th,
            .cashier-ledger td {
                border: 1px solid #64748b;
                padding: 6px 8px;
                line-height: 1.25;
            }

            .cashier-ledger th {
                background: #e5e7eb;
                text-align: center;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .03em;
            }

            .cashier-ledger .number { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
            .cashier-ledger .subtotal-label { text-align: right; border-left-color: transparent; border-bottom-color: transparent; }
            .cashier-grand-total { background: #e5e7eb; font-weight: 700; }

            @media (max-width: 780px) {
                .cashier-report-page { padding: 22px 18px 28px; }
                .cashier-report-head { flex-direction: column; gap: 8px; }
            }

            @media print {
                @page { size: A4 portrait; margin: 10mm; }
                body { background: #fff !important; }
                header, nav, .no-print { display: none !important; }
                main { padding: 0 !important; }
                .cashier-report-page { max-width: none; padding: 0; border: 0; box-shadow: none; }
                .cashier-report-title { font-size: 11pt; }
                .cashier-report-meta, .cashier-user-name { font-size: 8pt; }
                .cashier-user-block { break-inside: avoid; }
                .cashier-ledger { min-width: 0; font-size: 7.5pt; }
                .cashier-ledger th { font-size: 7pt; }
                .cashier-ledger th, .cashier-ledger td { padding: 2px 4px; }
            }
        </style>
    @endpush

    @php
        $number = fn ($value) => number_format((float) $value, 0, ',', '.');
    @endphp

    <div style="display:flex; flex-direction:column; gap:20px;">
        <div class="blueprint no-print" style="max-width:1120px; width:100%; margin:0 auto; padding:16px;">
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
                <button type="button" onclick="window.print()" class="btn btn-secondary" style="height:36px;">Cetak</button>
            </form>
        </div>

        <article class="cashier-report-page">
            <header class="cashier-report-head">
                <div>
                    <h3 class="cashier-report-title">REKAP KASIR HARIAN PER USER SPEKTRUM</h3>
                    <div class="cashier-report-meta">
                        <div>Tanggal: {{ \Carbon\Carbon::parse($dari)->translatedFormat('d F Y') }}{{ $dari !== $sampai ? ' s/d '.\Carbon\Carbon::parse($sampai)->translatedFormat('d F Y') : '' }}</div>
                        @if ($selectedKasir)
                            <div>Kasir: {{ ucwords(mb_strtolower($selectedKasir->name)) }}</div>
                        @endif
                    </div>
                </div>
                <div class="cashier-report-meta" style="text-align:right;">
                    <div>{{ $jumlahTransaksi }} transaksi</div>
                    <div>Halaman 1</div>
                </div>
            </header>

            @forelse ($groups as $group)
                @php
                    $ledgerRows = collect([[
                        'number' => '',
                        'description' => 'Saldo Awal',
                        'debit' => 0,
                        'credit' => 0,
                    ]])->concat($group['details']);
                @endphp

                <section class="cashier-user-block">
                    <div class="cashier-user-name">
                        User : {{ $group['kasir'] }}
                        @if ($group['user_id'])
                            <a class="no-print" href="{{ route('keuangan.rekap-kasir.customer', ['kasir' => $group['user_id'], 'dari' => $dari, 'sampai' => $sampai]) }}">Lihat Customer &rarr;</a>
                        @endif
                    </div>
                    <div style="overflow-x:auto;">
                        <table class="cashier-ledger">
                            <thead>
                                <tr>
                                    <th style="width:22%;">NO. NOTA</th>
                                    <th>KETERANGAN</th>
                                    <th style="width:17%;">DEBET</th>
                                    <th style="width:17%;">KREDIT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($ledgerRows as $detail)
                                    <tr>
                                        <td>{{ $detail['number'] }}</td>
                                        <td>{{ $detail['description'] }}</td>
                                        <td class="number">{{ $number($detail['debit']) }}</td>
                                        <td class="number">{{ $number($detail['credit']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="subtotal-label"><strong>Sub Total :</strong></td>
                                    <td class="number"><strong>{{ $number($group['debit']) }}</strong></td>
                                    <td class="number"><strong>{{ $number($group['credit']) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            @empty
                <div style="padding:48px 12px; text-align:center; color:#64748b; font-size:13px;">
                    Belum ada transaksi Kasir pada periode ini.
                </div>
            @endforelse

            @if ($groups->isNotEmpty())
                <div style="overflow-x:auto; margin-top:26px;">
                    <table class="cashier-ledger cashier-grand-total">
                        <tfoot>
                            <tr>
                                <td colspan="2" style="text-align:right;"><strong>TOTAL KESELURUHAN :</strong></td>
                                <td class="number" style="width:17%;"><strong>{{ $number($totalMasuk) }}</strong></td>
                                <td class="number" style="width:17%;"><strong>{{ $number($totalKeluar) }}</strong></td>
                            </tr>
                            <tr>
                                <td colspan="3" style="text-align:right;"><strong>TOTAL BERSIH :</strong></td>
                                <td class="number"><strong>{{ $number($totalMasuk - $totalKeluar) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </article>
    </div>
</x-app-layout>
