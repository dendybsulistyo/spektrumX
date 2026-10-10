<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Laporan Order</h2></x-slot>
    <style>
        .outdoor-report { color:#111827; }
        .outdoor-report table { width:100%; min-width:1250px; border-collapse:collapse; table-layout:fixed; font-size:10px; line-height:1.3; }
        .outdoor-report th,.outdoor-report td { border:1px solid #64748b; padding:4px 5px; vertical-align:top; overflow-wrap:anywhere; }
        .outdoor-report th { background:#e2e8f0; text-align:center; white-space:nowrap; }
        .outdoor-report .number { text-align:right; white-space:nowrap; }
        .outdoor-report .code,.outdoor-report .date,.outdoor-report .status { white-space:nowrap; overflow-wrap:normal; }
        .outdoor-report .code { font-size:9px; letter-spacing:-.15px; }
        .outdoor-report tbody.font-bold td { border-top:2px solid #334155; }
        @media print {
            [x-data^="chatWidget"], [x-data^="statusWa"], [x-data^="cariCepat"] { display:none !important; }
            @page { size:A4 landscape; margin:7mm; }
            body { background:#fff !important; }
            header,nav,.no-print { display:none !important; }
            main { padding:0 !important; }
            .outdoor-report section { box-shadow:none !important; padding:0 !important; }
            .outdoor-report table { min-width:0; font-family:Arial,sans-serif; font-size:7pt; }
            .outdoor-report th,.outdoor-report td { padding:2px 3px; }
            .outdoor-report .code { font-size:6.5pt; }
            .outdoor-report thead { display:table-header-group; }
            .outdoor-report tr { break-inside:avoid; }
        }
    </style>
    <div class="outdoor-report py-6"><div class="mx-auto max-w-[1900px] px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-sm text-gray-700">Jenis Order
                    <select name="jenis" class="mt-1 block rounded-md border-gray-300">
                        <option value="indoor" @selected($selectedType === 'indoor')>Indoor</option>
                        <option value="outdoor" @selected($selectedType === 'outdoor')>Outdoor</option>
                    </select>
                </label>
                <label class="text-sm text-gray-700">Status
                    <select name="status" class="mt-1 block rounded-md border-gray-300">
                        @foreach ($statusOptions as $value => $text)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $text }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm text-gray-700">Dari tanggal<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300"></label>
                <label class="text-sm text-gray-700">Sampai tanggal<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300"></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <div class="flex flex-wrap gap-2">
                @if ($printAll)
                    <a href="{{ request()->fullUrlWithoutQuery(['semua_halaman']) }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700">&larr; Kembali per halaman</a>
                    <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak Landscape</button>
                @else
                    <button type="button" onclick="window.print()" class="rounded-md border border-slate-800 bg-white px-4 py-2 text-sm font-semibold text-slate-800">Cetak Halaman Ini</button>
                    <a href="{{ request()->fullUrlWithQuery(['semua_halaman' => 1, 'page' => null]) }}" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak Semua Halaman</a>
                @endif
            </div>
        </div>
        <section class="bg-white p-4 shadow-sm">
            <div class="mb-3 text-center"><h1 class="text-base font-bold">LAPORAN ORDER {{ strtoupper($selectedType) }}{{ $selectedStatus !== 'semua' ? ' · '.strtoupper($statusOptions[$selectedStatus]) : '' }}</h1>
                <p class="text-sm">Dari Tanggal: {{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p></div>
            @foreach($groups as $group)
            <div class="{{ $loop->first ? '' : 'mt-6' }}">
                <h2 class="mb-2 text-sm font-bold uppercase">Laporan Order {{ $group->label }}</h2>
            <div class="overflow-x-auto"><table>
                <colgroup>
                    <col style="width:7%"><col style="width:10%"><col style="width:10%"><col style="width:7%"><col style="width:9%">
                    <col style="width:10%"><col style="width:8%"><col style="width:6%"><col style="width:6%"><col style="width:4%">
                    <col style="width:8%"><col style="width:8%"><col style="width:7%">
                </colgroup>
                <thead><tr><th>Tanggal</th><th>No. Order</th><th>No. Nota</th><th>Operator</th><th>Customer</th><th>Produk</th><th>Judul</th><th>Pj.</th><th>Lebar</th><th>Qty</th><th>Total</th><th>Uang Muka</th><th>Status</th></tr></thead>
                <tbody>@forelse($group->rows as $row)<tr>
                    <td class="date">{{ $row->first ? \Carbon\Carbon::parse($row->date)->format('d-m-Y') : '' }}</td><td class="code">{{ $row->first ? $row->order : '' }}</td><td class="code">{{ $row->first ? $row->invoice : '' }}</td>
                    <td>{{ $row->first ? $row->operator : '' }}</td><td>{{ $row->first ? $row->customer : '' }}</td><td>{{ $row->product }}</td><td>{{ $row->title }}</td>
                    <td class="number">{{ number_format((float)$row->length,2,',','.') }}</td><td class="number">{{ number_format((float)$row->width,2,',','.') }}</td><td class="number">{{ number_format((float)$row->qty,0,',','.') }}</td>
                    <td class="number">{{ $row->first ? number_format($row->total,0,',','.') : '' }}</td><td class="number">{{ $row->first ? number_format($row->advance,0,',','.') : '' }}</td><td class="status">{{ $row->first ? $row->status : '' }}</td>
                </tr>@empty<tr><td colspan="13" style="padding:28px;text-align:center">Belum ada order {{ $group->label }} pada periode ini.</td></tr>@endforelse</tbody>
                {{-- Total ditaruh di tbody (bukan tfoot) agar tidak tercetak berulang di setiap halaman kertas. --}}
                <tbody class="font-bold">
                    @if (! $printAll && $group->paginator->lastPage() > 1)
                        <tr><td colspan="10" class="number">TOTAL HALAMAN {{ $group->paginator->currentPage() }} DARI {{ $group->paginator->lastPage() }}</td><td class="number">{{ number_format($group->grandTotal,0,',','.') }}</td><td class="number">{{ number_format($group->totalAdvance,0,',','.') }}</td><td></td></tr>
                    @endif
                    <tr style="background:#f1ece0"><td colspan="10" class="number">TOTAL PERIODE {{ strtoupper($group->label) }}{{ $selectedStatus !== 'semua' ? ' · '.strtoupper($statusOptions[$selectedStatus]) : '' }} ({{ number_format($group->periodCount,0,',','.') }} order)</td><td class="number">{{ number_format($group->periodTotal,0,',','.') }}</td><td class="number">{{ number_format($group->periodPaid,0,',','.') }}</td><td></td></tr>
                </tbody>
            </table></div>
                @if ($group->rows->contains('status', 'Batal (sisa)'))
                    <p class="mt-2 text-xs text-gray-600">* <b>Batal (sisa)</b>: order dibatalkan, tetapi sebagian uang tidak dikembalikan ke customer. Total = sisa uang yang tetap menjadi pendapatan.</p>
                @endif
                @if ($group->paginator)
                    <div class="no-print mt-4">{{ $group->paginator->links() }}</div>
                @endif
            </div>
            @endforeach
        </section>
    </div></div>
</x-app-layout>
