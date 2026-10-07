<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap - Pelunasan Spektrum</h2></x-slot>

    <style>
        .daily-report { color:#111827; }
        .daily-report table { width:100%; border-collapse:collapse; }
        .daily-report th,.daily-report td { border:1px solid #64748b; padding:2px 4px !important; vertical-align:top; font-size:11px !important; line-height:1.18 !important; }
        .daily-report th { background:#e2e8f0; text-align:center; white-space:nowrap; font-weight:700; }
        .daily-report .number { text-align:right; white-space:nowrap; }
        .daily-report .center { text-align:center; white-space:nowrap; }
        @media print {
            @page { size:A4 landscape; margin:8mm; }
            body { background:#fff !important; }
            header,nav,.no-print { display:none !important; }
            main { padding:0 !important; }
            .daily-report table { font-family:Arial,sans-serif; }
            .daily-report th,.daily-report td { font-size:7.5pt !important; line-height:1.12 !important; }
            .daily-report thead { display:table-header-group; }
            .daily-report tr { break-inside:avoid; }
        }
    </style>

    <div class="daily-report py-6">
        <div class="mx-auto max-w-[1800px] px-4 sm:px-6">
            <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
                <form method="GET" class="flex items-end gap-3">
                    <input type="hidden" name="jenis" value="pelunasan">
                    <label class="text-sm text-gray-700">Tanggal pelunasan
                        <input type="date" name="tanggal" value="{{ $date }}" class="mt-1 block rounded-md border-gray-300">
                    </label>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
                </form>
                <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak Landscape</button>
            </div>

            <section class="bg-white p-3 shadow-sm">
                <div class="mb-3 text-center">
                    <h1 class="text-base font-bold">REKAP - PELUNASAN SPEKTRUM</h1>
                    <p class="text-sm">Tanggal: {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table>
                        <thead><tr>
                            <th>No</th><th>Jam Bayar</th><th>No. Invoice / Nota</th><th>No. Order</th><th>Tgl Order</th><th>Customer</th>
                            <th>Jenis</th><th>Metode</th><th>No. Referensi</th><th>Total Nota</th><th>Dibayar</th><th>Sisa Tagihan</th>
                        </tr></thead>
                        <tbody>
                        @forelse ($settlementRows as $row)
                            <tr>
                                <td class="center">{{ $loop->iteration }}</td>
                                <td class="center">{{ $row->paid_at?->format('H:i') }}</td>
                                <td class="center">{{ $row->invoice }}</td>
                                <td class="center">{{ $row->order }}</td>
                                <td class="center">{{ $row->order_date ? \Carbon\Carbon::parse($row->order_date)->format('d-m-Y') : '-' }}</td>
                                <td>{{ $row->customer }}</td>
                                <td class="center">{{ $row->kind }}</td>
                                <td class="center">{{ $row->method }}</td>
                                <td>{{ $row->reference ?: '-' }}</td>
                                <td class="number">{{ number_format($row->total, 0, ',', '.') }}</td>
                                <td class="number">{{ number_format($row->amount, 0, ',', '.') }}</td>
                                <td class="number">{{ $row->remaining > 0 ? number_format($row->remaining, 0, ',', '.') : 'Lunas' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="center" style="padding:28px">Belum ada pelunasan pada tanggal ini.</td></tr>
                        @endforelse
                        </tbody>
                        <tfoot class="font-bold">
                            <tr>
                                <td colspan="10" class="number">Grand Total:</td>
                                <td class="number">{{ number_format($settlementTotal, 0, ',', '.') }}</td>
                                <td></td>
                            </tr>
                            @foreach ($byMethod as $method => $amount)
                                <tr>
                                    <td colspan="10" class="number" style="font-weight:400">Total {{ $method }}:</td>
                                    <td class="number" style="font-weight:400">{{ number_format($amount, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            @endforeach
                        </tfoot>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
