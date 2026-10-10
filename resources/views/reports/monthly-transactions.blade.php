@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $dec = fn ($n) => number_format((float) $n, 2, ',', '.');
    $monthLabel = $month->locale('id')->translatedFormat('F Y');
@endphp
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Transaksi Bulanan</h2></x-slot>

    <style>
        .monthly-report { color: #111827; }
        .monthly-report table { width: 100%; border-collapse: collapse; }
        .monthly-report .mt-table { table-layout: fixed; min-width: 1180px; }
        .monthly-report .mt-table td { overflow-wrap: anywhere; }
        .monthly-report .mt-table td.number, .monthly-report .mt-table td.center, .monthly-report .mt-table td.doc { overflow-wrap: normal; }
        .monthly-report th, .monthly-report td { border: 1px solid #64748b; padding: 2px 4px !important; vertical-align: top; font-size: 11px !important; line-height: 1.2 !important; }
        .monthly-report th { background: #e2e8f0; text-align: center; white-space: nowrap; font-weight: 700; }
        .monthly-report .number { text-align: right; white-space: nowrap; }
        .monthly-report .center { text-align: center; white-space: nowrap; }
        .monthly-report tbody.note td { border-top-width: 1px; }
        .monthly-report tbody.note tr + tr td.line { border-top-style: dotted; border-top-color: #cbd5e1; }
        .monthly-report td.note-cell { border-bottom-width: 1px; }
        .monthly-report .doc { white-space: nowrap; }
        .monthly-report .summary { display: flex; flex-wrap: wrap; gap: 18px; margin: 4px 0 10px; font-size: 12.5px; }
        .monthly-report .summary b { font-variant-numeric: tabular-nums; }
        @media print {
            @page { size: A4 landscape; margin: 10mm 8mm 9mm; @bottom-right { content: "Halaman - " counter(page); font: 8pt Arial, sans-serif; } }
            body { background: #fff !important; }
            header, nav, .no-print { display: none !important; }
            main { padding: 0 !important; }
            .monthly-report section { box-shadow: none !important; padding: 0 !important; }
            .monthly-report table { font-family: Arial, sans-serif; }
            .monthly-report .mt-table { min-width: 0; }
            .monthly-report th, .monthly-report td { font-size: 7.5pt !important; line-height: 1.15 !important; }
            .monthly-report thead { display: table-header-group; }
            .monthly-report tbody.note { break-inside: avoid; }
        }
    </style>

    <div class="monthly-report py-6">
        <div class="mx-auto max-w-[1800px] px-4 sm:px-6">
            <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
                <form method="GET" class="flex items-end gap-3">
                    <label class="text-sm text-gray-700">Bulan
                        <input type="month" name="bulan" value="{{ $month->format('Y-m') }}" class="mt-1 block rounded-md border-gray-300">
                    </label>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
                </form>
                <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak Landscape</button>
            </div>

            <section class="bg-white p-3 shadow-sm">
                <div class="mb-2">
                    <h1 class="text-base font-bold">REKAP TRANSAKSI BULANAN SPEKTRUM</h1>
                    <p class="text-sm font-semibold">Bulan : {{ $monthLabel }}</p>
                </div>
                <div class="summary no-print">
                    <span>Nota: <b>{{ $fmt($notes->count()) }}</b></span>
                    <span>Total: <b>Rp {{ $fmt($totals->total) }}</b></span>
                    <span>Tunai: <b>Rp {{ $fmt($totals->cash) }}</b></span>
                    <span>Kredit: <b>Rp {{ $fmt($totals->credit) }}</b></span>
                </div>
                {{-- Pembungkus sendiri (bukan .overflow-x-auto) agar tidak terkena aturan lebar kolom global di app.css. --}}
                <div class="mt-scroll" style="overflow-x:auto">
                    <table class="mt-table">
                        <colgroup>
                            <col style="width:5.5%"><col style="width:9%"><col style="width:11%"><col style="width:15%"><col style="width:12%">
                            <col style="width:4%"><col style="width:4%"><col style="width:3%"><col style="width:5.5%"><col style="width:6.5%">
                            <col style="width:4.5%"><col style="width:6.5%"><col style="width:6.5%"><col style="width:6.5%">
                        </colgroup>
                        <thead><tr>
                            <th>Tanggal</th><th>No. Nota</th><th>Customer</th><th>Produk</th><th>Keterangan</th>
                            <th>Pj.</th><th>Leb.</th><th>Qty</th><th>Harga</th><th>Sub Total</th>
                            <th>Diskon</th><th>Total</th><th>Tunai</th><th>Kredit</th>
                        </tr></thead>
                        @forelse ($notes as $note)
                            @php $span = max(1, $note->lines->count()); @endphp
                            <tbody class="note">
                                @foreach ($note->lines->isEmpty() ? collect([null]) : $note->lines as $line)
                                    <tr>
                                        @if ($loop->first)
                                            <td class="center note-cell" rowspan="{{ $span }}">{{ \Carbon\Carbon::parse($note->date)->format('d-m-Y') }}</td>
                                            <td class="doc note-cell" rowspan="{{ $span }}">@foreach ($note->invoices as $number)<div>{{ $number }}</div>@endforeach</td>
                                            <td class="note-cell" rowspan="{{ $span }}">{{ $note->customer }}</td>
                                        @endif
                                        <td class="line">{{ $line?->product ?? '-' }}</td>
                                        <td class="line">{{ $line?->description ?? '' }}</td>
                                        <td class="number line">{{ $line ? $dec($line->length) : '' }}</td>
                                        <td class="number line">{{ $line ? $dec($line->width) : '' }}</td>
                                        <td class="number line">{{ $line ? $fmt($line->qty) : '' }}</td>
                                        <td class="number line">{{ $line && $line->price !== null ? $fmt($line->price) : '' }}</td>
                                        <td class="number line">{{ $line ? $fmt($line->subtotal) : '' }}</td>
                                        @if ($loop->first)
                                            <td class="number note-cell" rowspan="{{ $span }}">{{ $note->discount > 0 ? $fmt($note->discount) : '' }}</td>
                                            <td class="number note-cell" rowspan="{{ $span }}">{{ $fmt($note->total) }}</td>
                                            <td class="number note-cell" rowspan="{{ $span }}">{{ $fmt($note->cash) }}</td>
                                            <td class="number note-cell" rowspan="{{ $span }}">{{ $fmt($note->credit) }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        @empty
                            <tbody><tr><td colspan="14" class="center" style="padding:28px">Belum ada nota lunas atau tagihan customer VIP pada bulan ini.</td></tr></tbody>
                        @endforelse
                        <tfoot class="font-bold"><tr>
                            <td colspan="9" class="number">Grand Total :</td>
                            <td class="number">{{ $fmt($totals->subtotal) }}</td>
                            <td class="number">{{ $fmt($totals->discount) }}</td>
                            <td class="number">{{ $fmt($totals->total) }}</td>
                            <td class="number">{{ $fmt($totals->cash) }}</td>
                            <td class="number">{{ $fmt($totals->credit) }}</td>
                        </tr></tfoot>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
