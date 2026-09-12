<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Piutang per Customer</h2></x-slot>
    <style>
        .customer-receivable-detail { color:#111827; }
        .customer-receivable-detail table { width:100%; border-collapse:collapse; font-size:12px; }
        .customer-receivable-detail th,.customer-receivable-detail td { border:1px solid #64748b; padding:5px 7px; }
        .customer-receivable-detail th { background:#e2e8f0; text-align:center; }
        .customer-receivable-detail .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 portrait; margin:12mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .customer-receivable-detail section { box-shadow:none !important; padding:0 !important; }
            .customer-receivable-detail table { font-family:Arial,sans-serif; font-size:9pt; }
            .customer-receivable-detail th,.customer-receivable-detail td { padding:3px 5px; }
            .customer-receivable-detail thead { display:table-header-group; }
        }
    </style>
    <div class="customer-receivable-detail py-6"><div class="mx-auto max-w-5xl px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-sm text-gray-700">Customer<select name="customer" required class="mt-1 block min-w-64 rounded-md border-gray-300"><option value="">Pilih customer</option>@foreach($customers as $customer)<option value="{{ $customer->KdCust }}" @selected($customerCode === $customer->KdCust)>{{ $customer->NmCust }}</option>@endforeach</select></label>
                <label class="text-sm text-gray-700">Dari tanggal<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300"></label><label class="text-sm text-gray-700">Sampai tanggal<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300"></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <button type="button" onclick="window.print()" @disabled(!$selectedCustomer) class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Cetak</button>
        </div>
        <section class="bg-white p-5 shadow-sm">
            <div class="mb-4"><h1 class="text-base font-bold">REKAP PIUTANG PER CUSTOMER - SPEKTRUM</h1><p class="text-sm font-semibold">Dari Tanggal: {{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p><p class="text-sm font-semibold">Customer: {{ $selectedCustomer?->NmCust ?? 'Pilih customer terlebih dahulu' }}</p></div>
            <table><thead><tr><th style="width:18%;">Tanggal</th><th>No. Nota</th><th style="width:18%;">Piutang</th><th style="width:16%;">Discount</th><th style="width:16%;">Bayar</th><th style="width:19%;">Sisa Piutang</th></tr></thead>
                <tbody>@forelse($rows as $row)<tr><td style="text-align:center;">{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td><td>{{ $row->invoice }}</td><td class="number">{{ number_format($row->receivable,0,',','.') }}</td><td class="number">{{ number_format($row->discount,0,',','.') }}</td><td class="number">{{ number_format($row->paid,0,',','.') }}</td><td class="number">{{ number_format($row->remaining,0,',','.') }}</td></tr>@empty<tr><td colspan="6" style="padding:28px;text-align:center;">{{ $selectedCustomer ? 'Tidak ada piutang pada periode ini.' : 'Pilih customer untuk menampilkan laporan.' }}</td></tr>@endforelse</tbody>
                <tfoot><tr><td colspan="2" class="number"><strong>Total Piutang</strong></td><td class="number"><strong>{{ number_format($totals->receivable,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->discount,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->paid,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->remaining,0,',','.') }}</strong></td></tr></tfoot>
            </table>
        </section>
    </div></div>
</x-app-layout>
