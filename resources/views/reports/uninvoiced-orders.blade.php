<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Order Belum Di-Invoice</h2></x-slot>
    <style>
        .uninvoiced-report { color:#111827; font-size:10px; }
        .uninvoiced-report table { width:100%; border-collapse:collapse; table-layout:auto; }
        .uninvoiced-report th,.uninvoiced-report td { border:1px solid #64748b; padding:2px 3px !important; vertical-align:top; font-size:11px !important; line-height:1.18 !important; }
        .uninvoiced-report th { background:#e2e8f0; text-align:center; white-space:nowrap; font-size:11px !important; font-weight:700; }
        .uninvoiced-report .number { text-align:right; white-space:nowrap; }
        .uninvoiced-report .document-number { line-height:1.18 !important; white-space:nowrap; }
        /* Muat di layar tanpa scroll: lebar kolom tetap & teks panjang terlipat.
           (Sengaja tanpa .overflow-x-auto agar aturan tabel lebar di app.css tidak berlaku.) */
        .uninvoiced-report table.rekap-table { table-layout:fixed; width:100%; }
        .uninvoiced-report table.rekap-table th { white-space:normal; vertical-align:middle; }
        .uninvoiced-report table.rekap-table td { overflow-wrap:anywhere; }
        .uninvoiced-report table.rekap-table td.number, .uninvoiced-report table.rekap-table td.document-number { overflow-wrap:normal; }
        .uninvoiced-report .nota-group td.nota-cell { background:#fff; }
        .uninvoiced-report .nota-group { break-inside:avoid; }
        .uninvoiced-report .nota-group tr + tr td.line { border-top-style:dotted; }
        .uninvoiced-report .filter-panel { margin-bottom:8px; padding:8px; gap:8px; border-radius:4px; }
        .uninvoiced-report .filter-panel form { gap:8px; }
        .uninvoiced-report .filter-panel label { font-size:13px; font-weight:600; }
        .uninvoiced-report .filter-panel input { display:block; margin-top:3px; height:34px; padding:4px 8px; font-size:13px; border-radius:3px; }
        .uninvoiced-report .filter-panel button { min-height:34px; padding:5px 12px; font-size:12px; border-radius:3px; }
        .uninvoiced-report .report-sheet { padding:8px; }
        .uninvoiced-report .report-heading { margin-bottom:6px; }
        .uninvoiced-report .report-heading h1 { font-size:16px; line-height:1.35; }
        .uninvoiced-report .report-heading p { font-size:11px; line-height:1.35; }
        @media print {
            @page { size:A4 landscape; margin:7mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .uninvoiced-report section { box-shadow:none !important; padding:0 !important; }
            .uninvoiced-report table { font-family:Arial,sans-serif; }
            .uninvoiced-report th,.uninvoiced-report td { padding:2px 3px !important; font-size:7.5pt !important; line-height:1.12 !important; }
            .uninvoiced-report thead { display:table-header-group; } .uninvoiced-report tr { break-inside:avoid; }
        }
    </style>
    <div class="uninvoiced-report py-3"><div class="mx-auto max-w-[1900px] px-2 sm:px-3">
        <div class="filter-panel no-print flex flex-wrap items-end justify-between border bg-white shadow-sm">
            <form method="GET" class="flex flex-wrap items-end">
                <label class="text-sm text-gray-700">Bulan<input type="month" name="bulan" value="{{ \Carbon\Carbon::parse($from)->format('Y-m') }}" max="{{ now()->format('Y-m') }}" class="mt-1 block rounded-md border-gray-300"></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak Landscape</button>
        </div>
        <section class="report-sheet bg-white shadow-sm">
            <div class="report-heading text-center">
                <h1 class="text-base font-bold">REKAP ORDER YANG BELUM DI-INVOICE - SPEKTRUM</h1>
                <p class="text-sm">Bulan: {{ \Carbon\Carbon::parse($from)->translatedFormat('F Y') }}</p>
            </div>
            <div class="rekap-wrap"><table class="rekap-table">
                <colgroup><col style="width:3%"><col style="width:6%"><col style="width:9.5%"><col style="width:9.5%"><col style="width:6%"><col style="width:10%"><col style="width:9.5%"><col style="width:4.5%"><col style="width:4.5%"><col style="width:3.5%"><col style="width:5.5%"><col style="width:7%"><col style="width:6%"><col style="width:7%"><col style="width:8.5%"></colgroup>
                <thead><tr><th>No</th><th>Tanggal</th><th>No. Sales Order</th><th>Customer</th><th>Penerima</th><th>Nama Produk</th><th>Keterangan</th><th>Pj.</th><th>Leb.</th><th>Qty</th><th>Harga</th><th>Sub Total</th><th>Diskon</th><th>Total</th><th>Uang Muka</th></tr></thead>
                {{-- Dikelompokkan per nota: kolom nota & total digabung (rowspan), item tampil per baris. --}}
                @forelse($rows->groupBy('sales_order') as $items)@php($nota = $items->first())@php($span = $items->count())<tbody class="nota-group">@foreach($items as $item)<tr>
                    @if($loop->first)<td class="number nota-cell" style="text-align:center" rowspan="{{ $span }}">{{ $loop->parent->iteration }}</td><td class="nota-cell" rowspan="{{ $span }}">{{ \Carbon\Carbon::parse($nota->date)->format('d-m-Y') }}</td><td class="document-number nota-cell" rowspan="{{ $span }}"><div>{{ $nota->sales_order }}</div></td><td class="nota-cell" rowspan="{{ $span }}">{{ $nota->customer }}</td><td class="nota-cell" rowspan="{{ $span }}">{{ $nota->recipient }}</td>@endif
                    <td class="line">{{ $item->product }}</td><td class="line">{{ $item->description }}</td>
                    <td class="number line">{{ number_format((float)$item->length,2,',','.') }}</td><td class="number line">{{ number_format((float)$item->width,2,',','.') }}</td><td class="number line">{{ number_format((float)$item->qty,0,',','.') }}</td>
                    <td class="number line">{{ $item->price !== null ? number_format($item->price,0,',','.') : '-' }}</td><td class="number line">{{ number_format($item->subtotal,0,',','.') }}</td>
                    @if($loop->first)<td class="number nota-cell" rowspan="{{ $span }}">{{ number_format($items->sum('discount'),0,',','.') }}</td><td class="number nota-cell" rowspan="{{ $span }}">{{ number_format($items->sum('total'),0,',','.') }}</td><td class="number nota-cell" rowspan="{{ $span }}">{{ number_format($items->sum('advance'),0,',','.') }}</td>@endif
                </tr>@endforeach</tbody>@empty<tbody><tr><td colspan="15" style="padding:28px;text-align:center">Tidak ada order yang belum di-invoice pada periode ini.</td></tr></tbody>@endforelse
                <tfoot class="font-bold"><tr><td colspan="11" class="number">Grand Total:</td><td class="number">{{ number_format($totals->subtotal,0,',','.') }}</td><td class="number">{{ number_format($totals->discount,0,',','.') }}</td><td class="number">{{ number_format($totals->total,0,',','.') }}</td><td class="number">{{ number_format($totals->advance,0,',','.') }}</td></tr></tfoot>
            </table></div>
        </section>
    </div></div>
</x-app-layout>
