<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Laporan Omzet CTP Spektrum</h2></x-slot>
    <style>
        .ctp-report { color:#111827; }
        .ctp-report table { width:100%; border-collapse:collapse; font-size:13px; }
        .ctp-report th,.ctp-report td { border:1px solid #64748b; padding:7px 9px; }
        .ctp-report th { background:#e2e8f0; text-align:left; }
        .ctp-report .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 portrait; margin:12mm; }
            body { background:#fff !important; }
            header,nav,.no-print { display:none !important; }
            main { padding:0 !important; }
            .ctp-report section { box-shadow:none !important; padding:0 !important; }
            .ctp-report table { font-family:Arial,sans-serif; font-size:10pt; }
            .ctp-report thead { display:table-header-group; }
            .ctp-report tr { break-inside:avoid; }
        }
    </style>
    <div class="ctp-report py-6"><div class="mx-auto max-w-5xl px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-sm text-gray-700">Dari tanggal<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300"></label>
                <label class="text-sm text-gray-700">Sampai tanggal<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300"></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak Portrait</button>
        </div>
        <section class="bg-white p-5 shadow-sm">
            <div class="mb-4 text-center"><h1 class="text-base font-bold">LAPORAN OMZET CTP SPEKTRUM</h1>
                <p class="text-sm">Dari Tanggal: {{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p></div>
            <table><thead><tr><th>TANGGAL</th><th>NAMA PRODUK</th><th class="number">JUMLAH ORDER</th><th class="number">WARNA</th><th class="number">PLAT</th></tr></thead>
                <tbody>@forelse($rows as $row)<tr>
                    <td>{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td><td>{{ $row->product }}</td>
                    <td class="number">{{ number_format($row->orders,0,',','.') }}</td><td class="number">{{ $row->colors }}</td><td class="number">{{ number_format($row->plates,0,',','.') }}</td>
                </tr>@empty<tr><td colspan="5" style="padding:28px;text-align:center">Belum ada order produk dengan keterangan jumlah warna pada periode ini.</td></tr>@endforelse</tbody>
                <tfoot class="font-bold"><tr><td colspan="2">TOTAL</td><td class="number">{{ number_format($totalOrders,0,',','.') }}</td><td></td><td class="number">{{ number_format($totalPlates,0,',','.') }}</td></tr></tfoot>
            </table>
        </section>
    </div></div>
</x-app-layout>
