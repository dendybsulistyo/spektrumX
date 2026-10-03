<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Rekap Penerimaan</h2>
                <p class="mt-1 text-sm text-gray-500">Ringkasan penerimaan per kelompok produk.</p>
            </div>
            <button type="button" onclick="window.print()" class="print:hidden rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Cetak</button>
        </div>
    </x-slot>

    <style>
        @media print {
            @page { size: A4 portrait; margin: 14mm 13mm; }
            body { background: white !important; }
            nav, header, .print\:hidden { display: none !important; }
            main, main > div { margin: 0 !important; padding: 0 !important; max-width: none !important; }
            .report-sheet { border: 0 !important; box-shadow: none !important; padding: 0 !important; }
            .report-title { display: block !important; }
            .report-group { break-inside: avoid; }
        }
    </style>

    <div class="py-6">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <form method="GET" class="print:hidden mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <label class="text-sm font-medium text-gray-700">Dari tanggal
                    <input type="date" name="dari" value="{{ $from->toDateString() }}" class="mt-1 block rounded-lg border-gray-300 text-sm">
                </label>
                <label class="text-sm font-medium text-gray-700">Sampai tanggal
                    <input type="date" name="sampai" value="{{ $to->toDateString() }}" class="mt-1 block rounded-lg border-gray-300 text-sm">
                </label>
                <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Tampilkan</button>
            </form>

            <section class="report-sheet overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="report-title mb-7">
                    <h1 class="text-lg font-bold text-gray-950">Rekap Penerimaan SPEKTRUM</h1>
                    <p class="text-sm font-semibold text-gray-900">Dari Tanggal: {{ $from->locale('id')->translatedFormat('d F Y') }} s/d {{ $to->locale('id')->translatedFormat('d F Y') }}</p>
                </div>

                <div class="grid grid-cols-[1fr_10rem_10rem] border-b-2 border-gray-950 pb-2 text-sm font-bold text-gray-950">
                    <div>JENIS PENERIMAAN</div><div class="text-right">SUB TOTAL</div><div class="text-right">TOTAL</div>
                </div>

                @foreach ($groups as $group)
                    <div class="report-group grid grid-cols-[1fr_10rem_10rem] pt-4 text-sm text-gray-950">
                        <div class="col-span-3 font-bold">{{ $group->label }}</div>
                        @foreach ($group->items as $item)
                            <div class="ml-6 border-b border-gray-400 py-1">{{ $item->label }}</div>
                            <div class="border-b border-gray-400 py-1 text-right tabular-nums">{{ number_format($item->amount, 0, ',', '.') }}</div>
                            <div></div>
                        @endforeach
                        <div class="col-start-3 pt-2 text-right font-medium tabular-nums">{{ number_format($group->total, 0, ',', '.') }}</div>
                    </div>
                @endforeach

                <div class="mt-7 grid grid-cols-[1fr_10rem] border-t-2 border-gray-950 pt-3 text-base font-bold text-gray-950">
                    <div class="text-right">JUMLAH</div>
                    <div class="text-right tabular-nums">{{ number_format($grandTotal, 0, ',', '.') }}</div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
