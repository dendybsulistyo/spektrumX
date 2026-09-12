<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Order Outdoor</h2></x-slot>
    <style>
        .outdoor-report { color:#111827; }
        .outdoor-report table { width:100%; border-collapse:collapse; font-size:10px; }
        .outdoor-report th,.outdoor-report td { border:1px solid #64748b; padding:4px 5px; vertical-align:top; }
        .outdoor-report th { background:#e2e8f0; text-align:center; white-space:nowrap; }
        .outdoor-report .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 landscape; margin:7mm; }
            body { background:#fff !important; }
            header,nav,.no-print { display:none !important; }
            main { padding:0 !important; }
            .outdoor-report section { box-shadow:none !important; padding:0 !important; }
            .outdoor-report table { font-family:Arial,sans-serif; font-size:7.5pt; }
            .outdoor-report th,.outdoor-report td { padding:2px 3px; }
            .outdoor-report thead { display:table-header-group; }
            .outdoor-report tr { break-inside:avoid; }
        }
    </style>
    <div class="outdoor-report py-6"><div class="mx-auto max-w-[1900px] px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-sm text-gray-700">Dari tanggal<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300"></label>
                <label class="text-sm text-gray-700">Sampai tanggal<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300"></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak Landscape</button>
        </div>
        <section class="bg-white p-4 shadow-sm">
            <div class="mb-3 text-center"><h1 class="text-base font-bold">REKAP ORDER OUTDOOR</h1>
                <p class="text-sm">Dari Tanggal: {{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p></div>
            <div class="overflow-x-auto"><table>
                <thead><tr><th>Tanggal</th><th>No. Order</th><th>No. Nota</th><th>Operator</th><th>Customer</th><th>Produk</th><th>Judul</th><th>Pj.</th><th>Lebar</th><th>Qty</th><th>Total</th><th>Uang Muka</th><th>Status</th></tr></thead>
                <tbody>@forelse($rows as $row)<tr>
                    <td>{{ $row->first ? \Carbon\Carbon::parse($row->date)->format('d-m-Y') : '' }}</td><td>{{ $row->first ? $row->order : '' }}</td><td>{{ $row->first ? $row->invoice : '' }}</td>
                    <td>{{ $row->first ? $row->operator : '' }}</td><td>{{ $row->first ? $row->customer : '' }}</td><td>{{ $row->product }}</td><td>{{ $row->title }}</td>
                    <td class="number">{{ number_format((float)$row->length,2,',','.') }}</td><td class="number">{{ number_format((float)$row->width,2,',','.') }}</td><td class="number">{{ number_format((float)$row->qty,0,',','.') }}</td>
                    <td class="number">{{ $row->first ? number_format($row->total,0,',','.') : '' }}</td><td class="number">{{ $row->first ? number_format($row->advance,0,',','.') : '' }}</td><td>{{ $row->first ? $row->status : '' }}</td>
                </tr>@empty<tr><td colspan="13" style="padding:28px;text-align:center">Belum ada order outdoor pada periode ini.</td></tr>@endforelse</tbody>
                <tfoot class="font-bold"><tr><td colspan="10" class="number">TOTAL</td><td class="number">{{ number_format($grandTotal,0,',','.') }}</td><td class="number">{{ number_format($totalAdvance,0,',','.') }}</td><td></td></tr></tfoot>
            </table></div>
        </section>
    </div></div>
</x-app-layout>
