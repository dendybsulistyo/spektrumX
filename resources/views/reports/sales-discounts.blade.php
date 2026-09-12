<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Laporan Potongan Penjualan</h2></x-slot>
    <style>
        .sales-discount-report { color:#111827; }
        .sales-discount-report table { width:100%; border-collapse:collapse; font-size:12px; }
        .sales-discount-report th,.sales-discount-report td { border:1px solid #64748b; padding:5px 7px; }
        .sales-discount-report th { background:#e2e8f0; text-align:center; }
        .sales-discount-report .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 portrait; margin:12mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .sales-discount-report section { box-shadow:none !important; padding:0 !important; }
            .sales-discount-report table { font-family:Arial,sans-serif; font-size:9pt; }
            .sales-discount-report th,.sales-discount-report td { padding:3px 5px; }
            .sales-discount-report thead { display:table-header-group; }
        }
    </style>
    <div class="sales-discount-report py-6"><div class="mx-auto max-w-5xl px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-sm text-gray-700">Dari tanggal<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300"></label>
                <label class="text-sm text-gray-700">Sampai tanggal<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300"></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak</button>
        </div>
        <section class="bg-white p-5 shadow-sm">
            <div class="mb-4"><h1 class="text-base font-bold">LAPORAN POTONGAN PENJUALAN - SPEKTRUM</h1><p class="text-sm font-semibold">Periode: {{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p></div>
            <table>
                <thead><tr><th style="width:20%;">Tanggal</th><th style="width:24%;">No. Order</th><th>Customer</th><th style="width:23%;">Discount</th></tr></thead>
                <tbody>@forelse($rows as $row)<tr><td style="text-align:center;">{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td><td>{{ $row->order }}</td><td>{{ $row->customer }}</td><td class="number">{{ number_format($row->discount,0,',','.') }}</td></tr>@empty<tr><td colspan="4" style="padding:28px;text-align:center;">Tidak ada potongan penjualan yang disetujui pada periode ini.</td></tr>@endforelse</tbody>
                <tfoot><tr><td colspan="3" class="number"><strong>Total Discount</strong></td><td class="number"><strong>{{ number_format($totalDiscount,0,',','.') }}</strong></td></tr></tfoot>
            </table>
        </section>
    </div></div>
</x-app-layout>
