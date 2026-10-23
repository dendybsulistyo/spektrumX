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
        .nota-items summary { cursor:pointer; color:#1d4ed8; white-space:nowrap; }
        .nota-items ol { margin:3px 0 0; padding-left:16px; list-style:decimal; }
        .nota-items li { margin-bottom:3px; }
        .nota-items li span { display:block; color:#475569; }
        .nota-items .nota-item-calc { white-space:nowrap; }
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
                <colgroup><col style="width:4%"><col style="width:8%"><col style="width:12%"><col style="width:14%"><col style="width:9%"><col style="width:23%"><col style="width:8%"><col style="width:7%"><col style="width:8%"><col style="width:7%"></colgroup>
                <thead><tr><th>No</th><th>Tanggal</th><th>No. Sales Order</th><th>Customer</th><th>Penerima</th><th>Item</th><th>Sub Total</th><th>Diskon</th><th>Total</th><th>Uang Muka</th></tr></thead>
                <tbody>@forelse($rows->groupBy('sales_order') as $items)@php($nota = $items->first())<tr>
                    <td class="number" style="text-align:center">{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($nota->date)->format('d-m-Y') }}</td><td class="document-number"><div>{{ $nota->sales_order }}</div></td>
                    <td>{{ $nota->customer }}</td><td>{{ $nota->recipient }}</td>
                    <td>@include('reports.partials.nota-items', ['items' => $items])</td>
                    <td class="number">{{ number_format($items->sum('subtotal'),0,',','.') }}</td><td class="number">{{ number_format($items->sum('discount'),0,',','.') }}</td><td class="number">{{ number_format($items->sum('total'),0,',','.') }}</td><td class="number">{{ number_format($items->sum('advance'),0,',','.') }}</td>
                </tr>@empty<tr><td colspan="10" style="padding:28px;text-align:center">Tidak ada order yang belum di-invoice pada periode ini.</td></tr>@endforelse</tbody>
                <tfoot class="font-bold"><tr><td colspan="6" class="number">Grand Total:</td><td class="number">{{ number_format($totals->subtotal,0,',','.') }}</td><td class="number">{{ number_format($totals->discount,0,',','.') }}</td><td class="number">{{ number_format($totals->total,0,',','.') }}</td><td class="number">{{ number_format($totals->advance,0,',','.') }}</td></tr></tfoot>
            </table></div>
        </section>
    </div></div>
</x-app-layout>
