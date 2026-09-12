<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Penerimaan Piutang Harian</h2></x-slot>
    <style>
        .receivable-collection { color:#111827; }
        .receivable-collection table { width:100%; border-collapse:collapse; font-size:11px; }
        .receivable-collection th,.receivable-collection td { border:1px solid #64748b; padding:4px 6px; }
        .receivable-collection th { background:#e2e8f0; text-align:center; }
        .receivable-collection .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 portrait; margin:10mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .receivable-collection section { box-shadow:none !important; padding:0 !important; }
            .receivable-collection .customer-group { break-inside:avoid; }
            .receivable-collection table { font-family:Arial,sans-serif; font-size:8pt; }
            .receivable-collection th,.receivable-collection td { padding:2px 4px; }
            .receivable-collection thead { display:table-header-group; }
        }
    </style>
    <div class="receivable-collection py-6"><div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex items-end gap-3"><label class="text-sm text-gray-700">Tanggal laporan<input type="date" name="tanggal" value="{{ $date }}" class="mt-1 block rounded-md border-gray-300"></label><button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button></form>
            <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak</button>
        </div>
        <section class="bg-white p-5 shadow-sm">
            <div class="mb-4"><h1 class="text-base font-bold">PENERIMAAN PIUTANG SPEKTRUM</h1><p class="text-sm font-semibold">Tanggal: {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</p></div>
            @forelse($groups as $group)
                <div class="customer-group mb-5"><p class="mb-1 text-sm font-bold">Customer: {{ $group->customer }}</p><table>
                    <thead><tr><th style="width:15%;">Tanggal</th><th style="width:18%;">No. Order</th><th>Piutang</th><th>Dibayar</th><th>Discount</th><th>Sisa Piutang</th><th style="width:20%;">Keterangan</th></tr></thead>
                    <tbody>@foreach($group->rows as $row)<tr><td style="text-align:center;">{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td><td>{{ $row->order }}</td><td class="number">{{ number_format($row->receivable,0,',','.') }}</td><td class="number">{{ number_format($row->paid,0,',','.') }}</td><td class="number">{{ number_format($row->discount,0,',','.') }}</td><td class="number">{{ number_format($row->remaining,0,',','.') }}</td><td>{{ $row->notes }}</td></tr>@endforeach</tbody>
                    <tfoot><tr><td colspan="3" class="number"><strong>Total Penerimaan Piutang</strong></td><td class="number"><strong>{{ number_format($group->totalPaid,0,',','.') }}</strong></td><td colspan="3"></td></tr></tfoot>
                </table></div>
            @empty<p style="padding:28px;text-align:center;">Tidak ada piutang berjalan atau penerimaan piutang pada tanggal ini.</p>@endforelse
            @if($groups->isNotEmpty())<div class="number mt-3 text-sm font-bold">Total Penerimaan Semua Customer: {{ number_format($grandTotalPaid,0,',','.') }}</div>@endif
        </section>
    </div></div>
</x-app-layout>
