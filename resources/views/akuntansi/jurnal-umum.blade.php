<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Jurnal Umum</h2></x-slot>

    <style>
        #jurnal-umum table { width:100%; border-collapse:collapse; font-size:12px; }
        #jurnal-umum th { padding:6px 8px; background:#4b5563; color:#fff; font-weight:700; text-align:center; white-space:nowrap; border:1px solid #374151; }
        #jurnal-umum td { padding:2px 8px; border-left:1px solid #d1d5db; border-right:1px solid #d1d5db; white-space:nowrap; }
        #jurnal-umum tbody.trx tr:last-child td { border-bottom:1px solid #d1d5db; }
        #jurnal-umum tbody.trx + tbody.trx tr:first-child td { border-top:6px solid #f3f4f6; }
        #jurnal-umum .num { text-align:right; font-variant-numeric:tabular-nums; }
        #jurnal-umum .center { text-align:center; }
        #jurnal-umum .wrap { white-space:normal; min-width:220px; }
        #jurnal-umum .unbalanced td { background:#fef2f2; }
        #jurnal-umum .unbalanced .indicator { color:#b91c1c; font-weight:700; }
        #jurnal-umum tfoot td { padding:6px 8px; border:1px solid #9ca3af; background:#f3f4f6; font-weight:700; }
        @media print {
            @page { size:A4 landscape; margin:8mm; }
            header, nav, .no-print { display:none !important; }
            #jurnal-umum table { font-size:8pt; }
            #jurnal-umum thead { display:table-header-group; }
            #jurnal-umum tbody.trx { break-inside:avoid; }
        }
    </style>

    <div id="jurnal-umum" class="py-6">
        <div class="mx-auto max-w-[1700px] space-y-4 px-4 sm:px-6">
            <form class="no-print flex flex-wrap items-end gap-3 rounded-xl border bg-white p-4 shadow-sm">
                <label class="text-sm text-gray-600">Dari<input type="date" name="dari" value="{{ $dari }}" class="mt-1 block rounded-lg border-gray-300"></label>
                <label class="text-sm text-gray-600">Sampai<input type="date" name="sampai" value="{{ $sampai }}" class="mt-1 block rounded-lg border-gray-300"></label>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
                <button type="button" onclick="window.print()" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak Landscape</button>
            </form>

            <section class="rounded-xl border bg-white p-4 shadow-sm">
                <div class="mb-3 text-center">
                    <h1 class="text-base font-bold">JURNAL UMUM</h1>
                    <p class="text-sm">{{ \Carbon\Carbon::parse($dari)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($sampai)->translatedFormat('d F Y') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th><th>Bukti</th><th>Keterangan</th><th>Nama Akun</th><th>Kode Akun</th>
                                <th>Nama Akun Pembantu</th><th>Kode Bantu</th><th>Debet</th><th>Kredit</th><th>Indikator</th>
                            </tr>
                        </thead>
                        {{-- Satu tbody per transaksi (NoTrans) agar terpisah seperti format Excel. --}}
                        @forelse ($transactions as $transaction)
                            <tbody @class(['trx', 'unbalanced' => ! $transaction->balanced])>
                                @foreach ($transaction->rows as $entry)
                                    <tr>
                                        <td class="center">{{ \Carbon\Carbon::parse($entry->TgTrans)->format('d-m-Y') }}</td>
                                        <td>{{ $entry->Bukti }}</td>
                                        <td class="wrap">{{ $entry->KetMT }}</td>
                                        <td>{{ $names->get($entry->NoAkun, '-') }}</td>
                                        <td class="center">{{ $entry->NoAkun }}</td>
                                        <td>{{ $entry->KdBantu ? $helperNames->get($entry->KdBantu, '') : '' }}</td>
                                        <td class="center">{{ $entry->KdBantu }}</td>
                                        <td class="num">{{ (float) $entry->Debet ? number_format($entry->Debet, 0, ',', '.') : '' }}</td>
                                        <td class="num">{{ (float) $entry->Kredit ? number_format($entry->Kredit, 0, ',', '.') : '' }}</td>
                                        <td class="center indicator">{{ $transaction->balanced ? 'Balance' : 'Tidak Balance' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @empty
                            <tbody><tr><td colspan="10" class="center" style="padding:32px;color:#6b7280;">Belum ada jurnal pada periode ini.</td></tr></tbody>
                        @endforelse
                        <tfoot>
                            <tr>
                                <td colspan="7" class="num">Total</td>
                                <td class="num">{{ number_format($entries->sum('Debet'), 0, ',', '.') }}</td>
                                <td class="num">{{ number_format($entries->sum('Kredit'), 0, ',', '.') }}</td>
                                <td class="center">{{ abs($entries->sum('Debet') - $entries->sum('Kredit')) < 0.5 ? 'Balance' : 'Tidak Balance' }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
