<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Order per Customer</h2></x-slot>
    <style>
        .customer-paid-report { color:#111827; }
        .customer-paid-report table { width:100%; border-collapse:collapse; font-size:10px; }
        .customer-paid-report th,.customer-paid-report td { border:1px solid #64748b; padding:4px 5px; vertical-align:top; }
        .customer-paid-report th { background:#e2e8f0; text-align:center; white-space:nowrap; }
        .customer-paid-report .number { text-align:right; white-space:nowrap; }
        /* Muat di layar tanpa scroll: lebar kolom tetap, huruf & jarak sel dirapatkan.
           (Sengaja tidak memakai .overflow-x-auto agar aturan tabel 14 kolom di app.css tidak berlaku.) */
        .customer-paid-report .rekap-wrap { width:100%; }
        .customer-paid-report table.rekap-table { table-layout:fixed; width:100%; font-size:11.5px; line-height:1.35; }
        .customer-paid-report table.rekap-table th { white-space:normal; padding:5px 4px; font-size:11px; line-height:1.2; vertical-align:middle; }
        .customer-paid-report table.rekap-table td { padding:4px 5px; overflow-wrap:anywhere; }
        .customer-paid-report table.rekap-table td.number { overflow-wrap:normal; }
        .customer-paid-report table.rekap-table tfoot td { padding:5px; }
        .customer-paid-report .nota-group td.nota-cell { background:#fff; }
        .customer-paid-report .nota-group { break-inside:avoid; }
        .customer-paid-report .nota-group tr + tr td.line { border-top-style:dotted; }
        @media print {
            @page { size:A4 landscape; margin:7mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .customer-paid-report section { box-shadow:none !important; padding:0 !important; }
            .customer-paid-report table, .customer-paid-report table.rekap-table { font-family:Arial,sans-serif; font-size:7.5pt; }
            .customer-paid-report table.rekap-table th { font-size:7pt; }
            .customer-paid-report th,.customer-paid-report td { padding:2px 3px; }
            .customer-paid-report thead { display:table-header-group; } .customer-paid-report tr { break-inside:avoid; }
        }
    </style>
    <div class="customer-paid-report py-6"><div class="mx-auto max-w-[1900px] px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div class="relative text-sm text-gray-700"
                     x-data="{
                        code: @js($selectedCustomer?->KdCust ?? ''),
                        text: @js($selectedCustomer ? $selectedCustomer->NmCust.' — '.$selectedCustomer->KdCust : ''),
                        results: [], open: false, active: 0, loading: false,
                        search() {
                            this.code = '';
                            const q = this.text.trim();
                            if (q.length < 2) { this.results = []; this.open = false; return; }
                            this.loading = true;
                            fetch(@js(route('customers.search')) + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(r => r.ok ? r.json() : [])
                                .then(rows => { this.results = rows; this.active = 0; this.open = true; })
                                .finally(() => { this.loading = false; });
                        },
                        pick(c) { if (!c) return; this.code = c.KdCust; this.text = c.NmCust + ' — ' + c.KdCust; this.open = false; },
                     }"
                     @click.outside="open = false">
                    <label for="customer-search">Customer</label>
                    <input id="customer-search" type="text" x-model="text" @input.debounce.300ms="search()" autocomplete="off"
                           @keydown.arrow-down.prevent="active = Math.min(active + 1, results.length - 1)"
                           @keydown.arrow-up.prevent="active = Math.max(active - 1, 0)"
                           @keydown.enter="if (open && results.length) { $event.preventDefault(); pick(results[active]); }"
                           @keydown.escape="open = false"
                           placeholder="Ketik nama atau kode customer…" class="mt-1 block w-80 rounded-md border-gray-300">
                    <input type="hidden" name="customer" :value="code">
                    <div x-show="open" x-cloak class="absolute z-20 mt-1 max-h-72 w-80 overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg">
                        <template x-for="(c, i) in results" :key="c.KdCust">
                            <button type="button" @click="pick(c)" @mouseenter="active = i"
                                    :class="i === active ? 'bg-slate-100' : ''" class="block w-full px-3 py-2 text-left">
                                <span class="font-medium text-gray-900" x-text="c.NmCust"></span>
                                <span class="ml-1 text-xs text-gray-500" x-text="c.KdCust"></span>
                            </button>
                        </template>
                        <p x-show="!loading && results.length === 0" class="px-3 py-2 text-xs text-gray-500">Customer tidak ditemukan.</p>
                    </div>
                    <p x-show="text && !code && !open" x-cloak class="absolute left-0 top-full mt-1 whitespace-nowrap text-xs text-amber-600">Pilih customer dari daftar.</p>
                </div>
                <label class="text-sm text-gray-700">Dari tanggal<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300"></label>
                <label class="text-sm text-gray-700">Sampai tanggal<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300"></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <button type="button" onclick="window.print()" @disabled(!$selectedCustomer) class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Cetak Landscape</button>
        </div>
        <section class="bg-white p-4 shadow-sm">
            <div class="mb-3 text-center"><h1 class="text-base font-bold">REKAP ORDER PER CUSTOMER - SPEKTRUM</h1>
                <p class="text-sm font-semibold">Customer: {{ $selectedCustomer?->NmCust ?? 'Pilih customer terlebih dahulu' }}</p>
                <p class="text-sm">{{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p></div>
            <div class="rekap-wrap"><table class="rekap-table">
                <colgroup><col style="width:3%"><col style="width:6.5%"><col style="width:10%"><col style="width:11%"><col style="width:10%"><col style="width:4.5%"><col style="width:4.5%"><col style="width:3.5%"><col style="width:6%"><col style="width:7%"><col style="width:6%"><col style="width:7%"><col style="width:7%"><col style="width:7%"><col style="width:7%"></colgroup>
                <thead><tr><th>No</th><th>Tanggal</th><th>No. INV</th><th>Nama Produk</th><th>Keterangan</th><th>Pj.</th><th>Leb.</th><th>Qty</th><th>Harga</th><th>Sub Total</th><th>Diskon</th><th>Total</th><th>Uang Muka</th><th>Bayar</th><th>Kredit</th></tr></thead>
                {{-- Dikelompokkan per nota: kolom nota & total digabung (rowspan), item tampil per baris. --}}
                @forelse($rows->groupBy('invoice') as $items)@php($nota = $items->first())@php($span = $items->count())<tbody class="nota-group">@foreach($items as $item)<tr>
                    @if($loop->first)<td class="number nota-cell" style="text-align:center" rowspan="{{ $span }}">{{ $loop->parent->iteration }}</td><td class="nota-cell" rowspan="{{ $span }}">{{ \Carbon\Carbon::parse($nota->date)->format('d-m-Y') }}</td><td class="nota-cell" rowspan="{{ $span }}">{{ $nota->invoice }}</td>@endif
                    <td class="line">{{ $item->product }}</td><td class="line">{{ $item->description }}</td>
                    <td class="number line">{{ number_format((float)$item->length,2,',','.') }}</td><td class="number line">{{ number_format((float)$item->width,2,',','.') }}</td><td class="number line">{{ number_format((float)$item->qty,0,',','.') }}</td>
                    <td class="number line">{{ $item->price !== null ? number_format($item->price,0,',','.') : '-' }}</td><td class="number line">{{ number_format($item->subtotal,0,',','.') }}</td>
                    {{-- SO batal (format aplikasi lama): Total/Uang Muka/Kredit kosong, kolom Bayar bertulisan BATAL. --}}
                    @if($loop->first && $nota->cancelled)<td class="number nota-cell" rowspan="{{ $span }}">{{ number_format($items->sum('discount'),0,',','.') }}</td><td class="nota-cell" rowspan="{{ $span }}"></td><td class="nota-cell" rowspan="{{ $span }}"></td><td class="nota-cell" rowspan="{{ $span }}" style="text-align:center;font-weight:700;letter-spacing:.3em">BATAL</td><td class="nota-cell" rowspan="{{ $span }}"></td>
                    @elseif($loop->first)<td class="number nota-cell" rowspan="{{ $span }}">{{ number_format($items->sum('discount'),0,',','.') }}</td><td class="number nota-cell" rowspan="{{ $span }}">{{ number_format($items->sum('total'),0,',','.') }}</td><td class="number nota-cell" rowspan="{{ $span }}">{{ number_format($items->sum('advance'),0,',','.') }}</td><td class="number nota-cell" rowspan="{{ $span }}">{{ number_format($items->sum('payment'),0,',','.') }}</td><td class="number nota-cell" rowspan="{{ $span }}">{{ number_format($items->sum('credit'),0,',','.') }}</td>@endif
                </tr>@endforeach</tbody>@empty<tbody><tr><td colspan="15" style="padding:28px;text-align:center">{{ $selectedCustomer ? 'Tidak ada order ber-invoice pada periode ini.' : 'Pilih customer untuk menampilkan laporan.' }}</td></tr></tbody>@endforelse
                <tfoot class="font-bold"><tr><td colspan="9" class="number">Grand Total:</td><td class="number">{{ number_format($totals->subtotal,0,',','.') }}</td><td class="number">{{ number_format($totals->discount,0,',','.') }}</td><td class="number">{{ number_format($totals->total,0,',','.') }}</td><td class="number">{{ number_format($totals->advance,0,',','.') }}</td><td class="number">{{ number_format($totals->payment,0,',','.') }}</td><td class="number">{{ number_format($totals->credit,0,',','.') }}</td></tr></tfoot>
            </table></div>
        </section>
    </div></div>
</x-app-layout>
