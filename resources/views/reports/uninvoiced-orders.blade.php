<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Order Belum Di-Invoice</h2></x-slot>
    <style>
        .uninvoiced-report { color:#111827; font-size:10px; }
        .uninvoiced-report table { width:100%; border-collapse:collapse; font-size:8px; }
        .uninvoiced-report th,.uninvoiced-report td { border:1px solid #64748b; padding:2px 3px; vertical-align:top; line-height:1.25; }
        .uninvoiced-report th { background:#e2e8f0; text-align:center; white-space:nowrap; }
        .uninvoiced-report .number { text-align:right; white-space:nowrap; }
        .uninvoiced-report .document-number { min-width:112px; line-height:1.25; white-space:nowrap; }
        .uninvoiced-report .document-number span { color:#64748b; font-size:7px; font-weight:700; }
        .uninvoiced-report .filter-panel { margin-bottom:8px; padding:8px; gap:8px; border-radius:4px; }
        .uninvoiced-report .filter-panel form { gap:8px; }
        .uninvoiced-report .filter-panel label { font-size:10px; }
        .uninvoiced-report .filter-panel input { display:block; margin-top:2px; height:30px; padding:3px 7px; font-size:10px; border-radius:3px; }
        .uninvoiced-report .filter-panel button { min-height:30px; padding:4px 10px; font-size:10px; border-radius:3px; }
        .uninvoiced-report .report-sheet { padding:8px; }
        .uninvoiced-report .report-heading { margin-bottom:6px; }
        .uninvoiced-report .report-heading h1 { font-size:12px; line-height:1.3; }
        .uninvoiced-report .report-heading p { font-size:9px; line-height:1.3; }
        @media print {
            @page { size:A4 landscape; margin:7mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .uninvoiced-report section { box-shadow:none !important; padding:0 !important; }
            .uninvoiced-report table { font-family:Arial,sans-serif; font-size:6.75pt; }
            .uninvoiced-report th,.uninvoiced-report td { padding:2px 3px; }
            .uninvoiced-report .document-number span { font-size:6pt; }
            .uninvoiced-report thead { display:table-header-group; } .uninvoiced-report tr { break-inside:avoid; }
        }
    </style>
    <div class="uninvoiced-report py-3"><div class="mx-auto max-w-[1900px] px-2 sm:px-3">
        <div class="filter-panel no-print flex flex-wrap items-end justify-between border bg-white shadow-sm">
            <form method="GET" class="flex flex-wrap items-end">
                <label class="text-sm text-gray-700">Dari tanggal<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300"></label>
                <label class="text-sm text-gray-700">Sampai tanggal<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300"></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak Landscape</button>
        </div>
        <section class="report-sheet bg-white shadow-sm">
            <div class="report-heading text-center">
                <h1 class="text-base font-bold">REKAP ORDER YANG BELUM DI-INVOICE - SPEKTRUM</h1>
                <p class="text-sm">Bulan: {{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p>
            </div>
            <div class="overflow-x-auto"><table>
                <thead><tr><th>Tanggal</th><th>No. SO / DO / Invoice</th><th>Customer</th><th>Nama Produk</th><th>Keterangan</th><th>Penerima</th><th>Pj.</th><th>Leb.</th><th>Qty</th><th>Harga</th><th>Sub Total</th><th>Diskon</th><th>Total</th><th>Uang Muka</th></tr></thead>
                <tbody>@forelse($rows as $row)<tr>
                    <td>{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td><td class="document-number">
                        <div><span>SO</span> {{ $row->sales_order }}</div>
                        @foreach ($row->delivery_orders as $number)<div><span>DO</span> {{ $number }}</div>@endforeach
                        @forelse ($row->invoices as $number)<div><span>INV</span> {{ $number }}</div>@empty<div><span>INV</span> -</div>@endforelse
                    </td><td>{{ $row->customer }}</td><td>{{ $row->product }}</td><td>{{ $row->description }}</td><td>{{ $row->recipient }}</td>
                    <td class="number">{{ number_format((float)$row->length,2,',','.') }}</td><td class="number">{{ number_format((float)$row->width,2,',','.') }}</td><td class="number">{{ number_format((float)$row->qty,0,',','.') }}</td>
                    <td class="number">{{ $row->price !== null ? number_format($row->price,0,',','.') : '-' }}</td><td class="number">{{ number_format($row->subtotal,0,',','.') }}</td><td class="number">{{ number_format($row->discount,0,',','.') }}</td><td class="number">{{ number_format($row->total,0,',','.') }}</td><td class="number">{{ number_format($row->advance,0,',','.') }}</td>
                </tr>@empty<tr><td colspan="14" style="padding:28px;text-align:center">Tidak ada order yang belum di-invoice pada periode ini.</td></tr>@endforelse</tbody>
                <tfoot class="font-bold"><tr><td colspan="10" class="number">Grand Total:</td><td class="number">{{ number_format($totals->subtotal,0,',','.') }}</td><td class="number">{{ number_format($totals->discount,0,',','.') }}</td><td class="number">{{ number_format($totals->total,0,',','.') }}</td><td class="number">{{ number_format($totals->advance,0,',','.') }}</td></tr></tfoot>
            </table></div>
        </section>
    </div></div>
</x-app-layout>
