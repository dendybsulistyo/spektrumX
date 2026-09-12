<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Total Tagihan Piutang</h2></x-slot>
    <style>
        .receivable-total { color:#111827; }
        .receivable-total table { width:100%; border-collapse:collapse; font-size:12px; }
        .receivable-total th,.receivable-total td { border:1px solid #64748b; padding:5px 7px; }
        .receivable-total th { background:#e2e8f0; text-align:center; }
        .receivable-total .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 portrait; margin:12mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .receivable-total section { box-shadow:none !important; padding:0 !important; }
            .receivable-total table { font-family:Arial,sans-serif; font-size:9pt; }
            .receivable-total th,.receivable-total td { padding:3px 5px; }
            .receivable-total thead { display:table-header-group; }
        }
    </style>
    <div class="receivable-total py-6"><div class="mx-auto max-w-5xl px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex items-end gap-3">
                <label class="text-sm text-gray-700">Posisi per tanggal<input type="date" name="tanggal" value="{{ $asOf }}" class="mt-1 block rounded-md border-gray-300"></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak</button>
        </div>
        <section class="bg-white p-5 shadow-sm">
            <div class="mb-4"><h1 class="text-base font-bold">REKAP TOTAL TAGIHAN SPEKTRUM</h1><p class="text-sm font-semibold">Tanggal: {{ \Carbon\Carbon::parse($asOf)->translatedFormat('d F Y') }}</p></div>
            <table>
                <thead><tr><th>NAMA</th><th style="width:20%;">TANGGAL</th><th style="width:23%;">PIUTANG</th><th style="width:25%;">TELPON</th></tr></thead>
                <tbody>@forelse($rows as $row)<tr><td>{{ $row['name'] }}</td><td style="text-align:center;">{{ \Carbon\Carbon::parse($row['date'])->translatedFormat('d M Y') }}</td><td class="number">{{ number_format($row['receivable'],0,',','.') }}</td><td>{{ $row['phone'] }}</td></tr>@empty<tr><td colspan="4" style="padding:28px;text-align:center;">Tidak ada tagihan piutang sampai tanggal ini.</td></tr>@endforelse</tbody>
                <tfoot><tr><td colspan="2" class="number"><strong>Total Tagihan Piutang</strong></td><td class="number"><strong>{{ number_format($grandTotal,0,',','.') }}</strong></td><td></td></tr></tfoot>
            </table>
        </section>
    </div></div>
</x-app-layout>
