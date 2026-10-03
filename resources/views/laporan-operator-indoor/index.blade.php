<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Laporan Operator Indoor per Item</h2>
                <p class="mt-1 text-sm text-gray-500">Rekap produk dan omzet bulanan berdasarkan operator nota.</p>
            </div>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z" />
                </svg>
                Cetak / Simpan PDF
            </button>
        </div>
    </x-slot>

    <div class="space-y-5">
        <form method="GET" class="print-hidden flex flex-wrap items-end gap-3 border-b border-gray-200 pb-5">
            <div>
                <x-input-label for="bulan" value="Bulan laporan" />
                <input id="bulan" name="bulan" type="month" value="{{ $month->format('Y-m') }}"
                       class="mt-1 rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div class="w-64 max-w-full">
                <x-input-label for="operator" value="Operator" />
                <select id="operator" name="operator" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua operator</option>
                    @foreach ($operators as $operator)
                        <option value="{{ $operator->KdOpr }}" @selected($operatorCode === $operator->KdOpr)>{{ $operator->NmOpr }} ({{ $operator->KdOpr }})</option>
                    @endforeach
                </select>
            </div>
            <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tampilkan</button>
        </form>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 print-hidden">
            <div class="border-l-4 border-indigo-500 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-500">Operator</p>
                <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($summary->operators, 0, ',', '.') }}</p>
            </div>
            <div class="border-l-4 border-emerald-500 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-500">Baris produk</p>
                <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($summary->products, 0, ',', '.') }}</p>
            </div>
            <div class="border-l-4 border-amber-500 bg-white px-4 py-3 shadow-sm">
                <p class="text-xs font-semibold uppercase text-gray-500">Total</p>
                <p class="mt-1 text-xl font-bold text-gray-900">Rp {{ number_format($summary->grand_total, 0, ',', '.') }}</p>
            </div>
        </div>

        <div class="report-paper bg-white px-6 py-7 shadow-sm">
            <div class="mb-6 border-b-2 border-gray-900 pb-3">
                <h1 class="text-lg font-bold text-gray-950">REKAP OPERATOR SPEKTRUM</h1>
                <p class="mt-1 text-sm text-gray-700">Bulan: {{ $month->locale('id')->translatedFormat('F Y') }}</p>
            </div>

            @forelse ($report as $operator)
                <section class="operator-section mb-8">
                    <h2 class="mb-2 text-sm font-bold text-gray-900">Operator: {{ $operator->name }}</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-xs">
                            <thead>
                                <tr class="border-y border-gray-400 bg-gray-100 text-gray-700">
                                    <th class="px-2 py-2 text-left">Produk</th>
                                    <th class="px-2 py-2 text-right">Jumlah</th>
                                    <th class="px-2 py-2 text-right">Subtotal</th>
                                    <th class="px-2 py-2 text-right">Diskon</th>
                                    <th class="px-2 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($operator->items as $item)
                                    <tr class="border-b border-gray-200">
                                        <td class="px-2 py-1.5">{{ $item->product }}</td>
                                        <td class="whitespace-nowrap px-2 py-1.5 text-right">{{ number_format($item->quantity, $item->quantity == floor($item->quantity) ? 0 : 2, ',', '.') }} {{ $item->unit }}</td>
                                        <td class="whitespace-nowrap px-2 py-1.5 text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                        <td class="whitespace-nowrap px-2 py-1.5 text-right">{{ number_format($item->discount, 0, ',', '.') }}</td>
                                        <td class="whitespace-nowrap px-2 py-1.5 text-right font-medium">{{ number_format($item->total, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-gray-700">
                                    <td colspan="4" class="px-2 py-2 text-right font-bold">Grand Total</td>
                                    <td class="whitespace-nowrap px-2 py-2 text-right font-bold">{{ number_format($operator->grand_total, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            @empty
                <div class="py-16 text-center text-sm text-gray-500">Tidak ada transaksi indoor pada periode dan operator yang dipilih.</div>
            @endforelse
        </div>
    </div>

    <style>
        @media print {
            @page { size: A4 portrait; margin: 12mm; }
            body { background: white !important; }
            nav, header, .print-hidden { display: none !important; }
            main { padding: 0 !important; }
            .report-paper { box-shadow: none !important; padding: 0 !important; }
            .operator-section { break-inside: auto; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</x-app-layout>
