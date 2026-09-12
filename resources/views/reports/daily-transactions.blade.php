<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Transaksi Harian Spektrum</h2></x-slot>

    <style>
        .daily-report { color:#111827; }
        .daily-report table { width:100%; border-collapse:collapse; font-size:11px; }
        .daily-report th,.daily-report td { border:1px solid #64748b; padding:5px 6px; vertical-align:top; }
        .daily-report th { background:#e2e8f0; text-align:center; white-space:nowrap; }
        .daily-report .number { text-align:right; white-space:nowrap; }
        .daily-report .center { text-align:center; }
        @media print {
            @page { size:A4 landscape; margin:8mm; }
            body { background:#fff !important; }
            header,nav,.no-print { display:none !important; }
            main { padding:0 !important; }
            .daily-report { width:100%; margin:0; }
            .daily-report table { font-family:Arial,sans-serif; font-size:8pt; }
            .daily-report th,.daily-report td { padding:2.5px 3px; }
            .daily-report thead { display:table-header-group; }
            .daily-report tr { break-inside:avoid; }
        }
    </style>

    <div class="daily-report py-6">
        <div class="mx-auto max-w-[1800px] px-4 sm:px-6">
            <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
                <form method="GET" class="flex items-end gap-3">
                    <label class="text-sm text-gray-700">Tanggal
                        <input type="date" name="tanggal" value="{{ $date }}" class="mt-1 block rounded-md border-gray-300">
                    </label>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
                </form>
                <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak Landscape</button>
            </div>

            <section class="bg-white p-3 shadow-sm">
                <div class="mb-3 text-center">
                    <h1 class="text-base font-bold">REKAP TRANSAKSI HARIAN SPEKTRUM</h1>
                    <p class="text-sm">Tanggal: {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table>
                        <thead><tr>
                            <th>Tanggal</th><th>No. Nota</th><th>Customer</th><th>Produk</th><th>Keterangan</th>
                            <th>Pj.</th><th>Leb.</th><th>Qty</th><th>Harga</th><th>Sub Total</th>
                            <th>Diskon</th><th>Total</th><th>Tunai</th><th>Kredit</th>
                        </tr></thead>
                        <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="center">{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td>
                                <td>{{ $row->number }}</td><td>{{ $row->customer }}</td><td>{{ $row->product }}</td><td>{{ $row->description }}</td>
                                <td class="number">{{ number_format((float) $row->length, 2, ',', '.') }}</td>
                                <td class="number">{{ number_format((float) $row->width, 2, ',', '.') }}</td>
                                <td class="number">{{ number_format((float) $row->qty, 0, ',', '.') }}</td>
                                <td class="number">{{ $row->price !== null ? number_format($row->price, 0, ',', '.') : '-' }}</td>
                                <td class="number">{{ number_format($row->subtotal, 0, ',', '.') }}</td>
                                <td class="number">{{ $row->discount > 0 ? number_format($row->discount, 0, ',', '.') : '-' }}</td>
                                <td class="number">{{ number_format($row->total, 0, ',', '.') }}</td>
                                <td class="number">{{ $row->cash > 0 ? number_format($row->cash, 0, ',', '.') : '-' }}</td>
                                <td class="number">{{ $row->credit > 0 ? number_format($row->credit, 0, ',', '.') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="14" class="center" style="padding:28px">Belum ada transaksi pada tanggal ini.</td></tr>
                        @endforelse
                        </tbody>
                        <tfoot class="font-bold"><tr>
                            <td colspan="9" class="number">Grand Total:</td>
                            <td class="number">{{ number_format($totals->subtotal, 0, ',', '.') }}</td>
                            <td class="number">{{ number_format($totals->discount, 0, ',', '.') }}</td>
                            <td class="number">{{ number_format($totals->total, 0, ',', '.') }}</td>
                            <td class="number">{{ number_format($totals->cash, 0, ',', '.') }}</td>
                            <td class="number">{{ number_format($totals->credit, 0, ',', '.') }}</td>
                        </tr></tfoot>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
