<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Piutang Customer Global</h2></x-slot>
    <style>
        .global-receivable-report { color:#111827; }
        .global-receivable-report table { width:100%; border-collapse:collapse; font-size:12px; }
        .global-receivable-report th,.global-receivable-report td { border:1px solid #64748b; padding:5px 7px; }
        .global-receivable-report th { background:#e2e8f0; text-align:center; }
        .global-receivable-report .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 portrait; margin:12mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .global-receivable-report section { box-shadow:none !important; padding:0 !important; }
            .global-receivable-report table { font-family:Arial,sans-serif; font-size:9pt; }
            .global-receivable-report th,.global-receivable-report td { padding:3px 5px; }
            .global-receivable-report thead { display:table-header-group; }
        }
    </style>
    <div class="global-receivable-report py-6"><div class="mx-auto max-w-5xl px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex items-end gap-3"><label class="text-sm text-gray-700">Sampai tanggal<input type="date" name="tanggal" value="{{ $asOf }}" class="mt-1 block rounded-md border-gray-300"></label><button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button></form>
            <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak</button>
        </div>
        <section class="bg-white p-5 shadow-sm">
            <div class="mb-4"><h1 class="text-base font-bold">REKAP PIUTANG CUSTOMER SPEKTRUM</h1><p class="text-sm font-semibold">Sampai Tanggal: {{ \Carbon\Carbon::parse($asOf)->translatedFormat('d F Y') }}</p></div>
            <table>
                <thead><tr><th>Customer</th><th style="width:19%;">Piutang</th><th style="width:17%;">Discount</th><th style="width:17%;">Bayar</th><th style="width:19%;">Sisa Piutang</th></tr></thead>
                <tbody>@forelse($rows as $row)<tr><td>@if($row['code'])<a class="text-indigo-700 hover:underline" href="{{ route('keuangan.customer-receivable-details', ['customer'=>$row['code'],'dari'=>'2000-01-01','sampai'=>$asOf]) }}">{{ $row['customer'] }}</a>@else{{ $row['customer'] }}@endif</td><td class="number">{{ number_format($row['receivable'],0,',','.') }}</td><td class="number">{{ number_format($row['discount'],0,',','.') }}</td><td class="number">{{ number_format($row['paid'],0,',','.') }}</td><td class="number">{{ number_format($row['remaining'],0,',','.') }}</td></tr>@empty<tr><td colspan="5" style="padding:28px;text-align:center;">Tidak ada piutang customer sampai tanggal ini.</td></tr>@endforelse</tbody>
                <tfoot><tr><td class="number"><strong>Total Piutang</strong></td><td class="number"><strong>{{ number_format($totals->receivable,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->discount,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->paid,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->remaining,0,',','.') }}</strong></td></tr></tfoot>
            </table>
        </section>
    </div></div>
</x-app-layout>
