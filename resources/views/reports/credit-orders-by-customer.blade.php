<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Order Customer ber-Plafon (Piutang)</h2></x-slot>
    <style>
        .customer-credit-report { color:#111827; }
        .customer-credit-report table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:9px; line-height:1.25; }
        .customer-credit-report th,.customer-credit-report td { border:1px solid #94a3b8; padding:2px 3px; vertical-align:top; }
        .customer-credit-report thead th { background:#e2e8f0; text-align:center; white-space:nowrap; font-weight:700; }
        .customer-credit-report td { overflow-wrap:anywhere; }
        .customer-credit-report .number { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
        .customer-credit-report tbody tr:nth-child(even) td { background:#f8fafc; }
        /* Not .overflow-x-auto: app.css widens every 14-column table inside
           it (purchase report) to 1500px with large padding. */
        .customer-credit-report .report-scroll { overflow-x:auto; }
        .customer-credit-report .print-title { display:none; }
        @media print {
            @page { size:A4 landscape; margin:8mm 7mm 10mm; @bottom-right { content:"Hal. " counter(page) " / " counter(pages); font:7pt Arial,sans-serif; color:#475569; } }
            html, body, body > div, main { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            /* Floating widgets (chat button, toasts) must not land on paper. */
            body .fixed { display:none !important; }
            .customer-credit-report { padding:0 !important; }
            .customer-credit-report > div { max-width:none !important; padding:0 !important; }
            .customer-credit-report section { box-shadow:none !important; padding:0 !important; }
            .customer-credit-report .screen-title { display:none; }
            /* The title lives in thead so every printed page names the
               customer and period, not only the first one. */
            .customer-credit-report .print-title { display:table-row; }
            .customer-credit-report .print-title th { background:#fff !important; border:0; padding:0 0 4px; text-align:center; white-space:normal; font-weight:400; }
            .customer-credit-report .report-scroll { overflow:visible; }
            .customer-credit-report table { font-family:Arial,sans-serif; font-size:7pt; }
            .customer-credit-report th,.customer-credit-report td { padding:1.5px 2.5px; }
            .customer-credit-report thead th { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .customer-credit-report tbody tr:nth-child(even) td { background:#fff; }
            .customer-credit-report thead { display:table-header-group; }
            /* Chrome repeats tfoot on every page by default; the grand total
               must only appear once, after the last row. */
            .customer-credit-report tfoot { display:table-row-group; }
            .customer-credit-report tr { break-inside:avoid; page-break-inside:avoid; }
            .customer-credit-report tfoot tr { break-before:avoid; page-break-before:avoid; }
        }
    </style>
    <div class="customer-credit-report py-6"><div class="mx-auto max-w-[1900px] px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-sm text-gray-700">Customer ber-plafon<select name="customer" required class="mt-1 block min-w-64 rounded-md border-gray-300"><option value="">Pilih customer</option>@foreach($customers as $customer)<option value="{{ $customer->KdCust }}" @selected($customerCode === $customer->KdCust)>{{ $customer->NmCust }} — {{ $customer->KdCust }}</option>@endforeach</select></label>
                <label class="text-sm text-gray-700">Dari tanggal<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300"></label>
                <label class="text-sm text-gray-700">Sampai tanggal<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300"></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <button type="button" onclick="window.print()" @disabled(!$selectedCustomer) class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Cetak Landscape</button>
        </div>
        <section class="bg-white p-4 shadow-sm">
            <div class="screen-title mb-3 text-center"><h1 class="text-base font-bold">REKAP ORDER PER CUSTOMER - SPEKTRUM</h1>
                <p class="text-sm font-semibold">Customer: {{ $selectedCustomer?->NmCust ?? 'Pilih customer terlebih dahulu' }}</p>
                @if($selectedCustomer)<p class="text-xs">Plafon: Rp {{ number_format($selectedCustomer->limit->Batas,0,',','.') }} · Terpakai: Rp {{ number_format($selectedCustomer->limit->Total,0,',','.') }} · Sisa: Rp {{ number_format(max(0,$selectedCustomer->limit->Batas-$selectedCustomer->limit->Total),0,',','.') }}</p>@endif
                <p class="text-sm">{{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p></div>
            <div class="report-scroll"><table>
                <colgroup><col style="width:5%"><col style="width:9.5%"><col style="width:15%"><col><col style="width:3.6%"><col style="width:3.6%"><col style="width:2.6%"><col style="width:5.2%"><col style="width:6.4%"><col style="width:4.6%"><col style="width:6.4%"><col style="width:5.4%"><col style="width:4%"><col style="width:6.4%"></colgroup>
                <thead><tr class="print-title"><th colspan="14"><div style="font-size:9pt;font-weight:700;">REKAP ORDER PER CUSTOMER - SPEKTRUM</div><div style="font-size:8pt;font-weight:700;">{{ $selectedCustomer?->NmCust }}</div><div style="font-size:7pt;">@if($selectedCustomer)Plafon Rp {{ number_format($selectedCustomer->limit->Batas,0,',','.') }} · Terpakai Rp {{ number_format($selectedCustomer->limit->Total,0,',','.') }} · Sisa Rp {{ number_format(max(0,$selectedCustomer->limit->Batas-$selectedCustomer->limit->Total),0,',','.') }} · @endif{{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</div></th></tr><tr><th>Tanggal</th><th>No. Nota</th><th>Nama Produk</th><th>Keterangan</th><th>Pj.</th><th>Leb.</th><th>Qty</th><th>Harga</th><th>Sub Total</th><th>Diskon</th><th>Total</th><th>DP</th><th>Bayar</th><th>Kredit</th></tr></thead>
                <tbody>@forelse($rows as $row)<tr><td>{{ \Carbon\Carbon::parse($row->date)->format('d-m-y') }}</td><td>{{ $row->invoice }}</td><td>{{ $row->product }}</td><td>{{ $row->description }}</td>
                    <td class="number">{{ number_format((float)$row->length,2,',','.') }}</td><td class="number">{{ number_format((float)$row->width,2,',','.') }}</td><td class="number">{{ number_format((float)$row->qty,0,',','.') }}</td>
                    <td class="number">{{ $row->price !== null ? number_format($row->price,0,',','.') : '-' }}</td><td class="number">{{ number_format($row->subtotal,0,',','.') }}</td><td class="number">{{ number_format($row->discount,0,',','.') }}</td><td class="number">{{ number_format($row->total,0,',','.') }}</td><td class="number">{{ number_format($row->advance,0,',','.') }}</td><td class="number">{{ number_format($row->payment,0,',','.') }}</td><td class="number">{{ number_format($row->credit,0,',','.') }}</td>
                </tr>@empty<tr><td colspan="14" style="padding:28px;text-align:center">{{ $selectedCustomer ? 'Tidak ada order piutang pada periode ini.' : 'Pilih customer ber-plafon untuk menampilkan laporan.' }}</td></tr>@endforelse</tbody>
                <tfoot class="font-bold"><tr><td colspan="8" class="number">Grand Total:</td><td class="number">{{ number_format($totals->subtotal,0,',','.') }}</td><td class="number">{{ number_format($totals->discount,0,',','.') }}</td><td class="number">{{ number_format($totals->total,0,',','.') }}</td><td class="number">{{ number_format($totals->advance,0,',','.') }}</td><td class="number">{{ number_format($totals->payment,0,',','.') }}</td><td class="number">{{ number_format($totals->credit,0,',','.') }}</td></tr></tfoot>
            </table></div>
        </section>
    </div></div>
</x-app-layout>
