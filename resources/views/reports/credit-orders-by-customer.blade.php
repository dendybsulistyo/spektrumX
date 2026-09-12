<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Order Customer ber-Plafon (Piutang)</h2></x-slot>
    <style>
        .customer-credit-report { color:#111827; }
        .customer-credit-report table { width:100%; border-collapse:collapse; font-size:10px; }
        .customer-credit-report th,.customer-credit-report td { border:1px solid #64748b; padding:4px 5px; vertical-align:top; }
        .customer-credit-report th { background:#e2e8f0; text-align:center; white-space:nowrap; }
        .customer-credit-report .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 landscape; margin:7mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .customer-credit-report section { box-shadow:none !important; padding:0 !important; }
            .customer-credit-report table { font-family:Arial,sans-serif; font-size:7.5pt; }
            .customer-credit-report th,.customer-credit-report td { padding:2px 3px; }
            .customer-credit-report thead { display:table-header-group; } .customer-credit-report tr { break-inside:avoid; }
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
            <div class="mb-3 text-center"><h1 class="text-base font-bold">REKAP ORDER PER CUSTOMER - SPEKTRUM</h1>
                <p class="text-sm font-semibold">Customer: {{ $selectedCustomer?->NmCust ?? 'Pilih customer terlebih dahulu' }}</p>
                @if($selectedCustomer)<p class="text-xs">Plafon: Rp {{ number_format($selectedCustomer->limit->Batas,0,',','.') }} · Terpakai: Rp {{ number_format($selectedCustomer->limit->Total,0,',','.') }} · Sisa: Rp {{ number_format(max(0,$selectedCustomer->limit->Batas-$selectedCustomer->limit->Total),0,',','.') }}</p>@endif
                <p class="text-sm">{{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p></div>
            <div class="overflow-x-auto"><table><thead><tr><th>Tanggal</th><th>No. Nota</th><th>Nama Produk</th><th>Keterangan</th><th>Pj.</th><th>Leb.</th><th>Qty</th><th>Harga</th><th>Sub Total</th><th>Diskon</th><th>Total</th><th>Uang Muka</th><th>Bayar</th><th>Kredit</th></tr></thead>
                <tbody>@forelse($rows as $row)<tr><td>{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td><td>{{ $row->invoice }}</td><td>{{ $row->product }}</td><td>{{ $row->description }}</td>
                    <td class="number">{{ number_format((float)$row->length,2,',','.') }}</td><td class="number">{{ number_format((float)$row->width,2,',','.') }}</td><td class="number">{{ number_format((float)$row->qty,0,',','.') }}</td>
                    <td class="number">{{ $row->price !== null ? number_format($row->price,0,',','.') : '-' }}</td><td class="number">{{ number_format($row->subtotal,0,',','.') }}</td><td class="number">{{ number_format($row->discount,0,',','.') }}</td><td class="number">{{ number_format($row->total,0,',','.') }}</td><td class="number">{{ number_format($row->advance,0,',','.') }}</td><td class="number">0</td><td class="number">{{ number_format($row->credit,0,',','.') }}</td>
                </tr>@empty<tr><td colspan="14" style="padding:28px;text-align:center">{{ $selectedCustomer ? 'Tidak ada order piutang pada periode ini.' : 'Pilih customer ber-plafon untuk menampilkan laporan.' }}</td></tr>@endforelse</tbody>
                <tfoot class="font-bold"><tr><td colspan="8" class="number">Grand Total:</td><td class="number">{{ number_format($totals->subtotal,0,',','.') }}</td><td class="number">{{ number_format($totals->discount,0,',','.') }}</td><td class="number">{{ number_format($totals->total,0,',','.') }}</td><td class="number">{{ number_format($totals->advance,0,',','.') }}</td><td class="number">0</td><td class="number">{{ number_format($totals->credit,0,',','.') }}</td></tr></tfoot>
            </table></div>
        </section>
    </div></div>
</x-app-layout>
