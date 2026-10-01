<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Kinerja Printer Outdoor</h2></x-slot>
    @php
        $money = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
        $number = fn ($value, $decimals = 0) => number_format((float) $value, $decimals, ',', '.');
        $maxRevenue = max(1, (float) $rows->max('net_revenue'));
    @endphp

    <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Dari tanggal<input type="date" name="from" value="{{ $from }}" class="mt-1 block rounded-md border-slate-300 text-sm"></label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Sampai tanggal<input type="date" name="to" value="{{ $to }}" class="mt-1 block rounded-md border-slate-300 text-sm"></label>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Printer<select name="printer" class="mt-1 block min-w-52 rounded-md border-slate-300 text-sm"><option value="">Semua Printer</option>@foreach($printers as $printer)<option value="{{ $printer->KdPrn }}" @selected($printerCode === $printer->KdPrn)>{{ $printer->NmPrn }}</option>@endforeach</select></label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Tampilkan</button>
                <a href="{{ route('printer-performance.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
            </form>
            <p class="mt-3 text-xs text-slate-500">Periode dihitung dari waktu order selesai diproses pada tahap Cetak. Omzet bersih dialokasikan per item setelah potongan order.</p>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Printer Aktif</p><p class="mt-2 text-2xl font-bold text-blue-900">{{ $summary->printers }}</p><p class="text-xs text-blue-700">{{ $summary->orders }} order · {{ $summary->items }} item</p></div>
            <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Produksi</p><p class="mt-2 text-2xl font-bold text-cyan-900">{{ $number($summary->area, 2) }} m²</p><p class="text-xs text-cyan-700">{{ $number($summary->qty) }} total qty</p></div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Omzet Bersih</p><p class="mt-2 text-2xl font-bold text-emerald-900">{{ $money($summary->net_revenue) }}</p><p class="text-xs text-emerald-700">Setelah alokasi potongan</p></div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Omzet Bruto</p><p class="mt-2 text-2xl font-bold text-amber-900">{{ $money($summary->gross_revenue) }}</p><p class="text-xs text-amber-700">Sebelum potongan</p></div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-900">Performa per Printer</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3 text-left">Printer</th><th class="px-4 py-3 text-right">Order</th><th class="px-4 py-3 text-right">Item</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3 text-right">Luas</th><th class="px-4 py-3 text-right">Omzet Bersih</th><th class="px-4 py-3 text-left">Operator Terbanyak</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($rows as $row)
                            <tr class="align-top"><td class="px-4 py-3"><p class="font-semibold text-slate-900">{{ $row->name }}</p><p class="text-xs text-slate-400">{{ $row->code }}</p><div class="mt-2 h-1.5 overflow-hidden rounded bg-slate-100"><div class="h-full rounded bg-indigo-500" style="width:{{ round(($row->net_revenue / $maxRevenue) * 100, 1) }}%"></div></div></td><td class="px-4 py-3 text-right tabular-nums">{{ $number($row->orders) }}</td><td class="px-4 py-3 text-right tabular-nums">{{ $number($row->items) }}</td><td class="px-4 py-3 text-right tabular-nums">{{ $number($row->qty) }}</td><td class="px-4 py-3 text-right tabular-nums">{{ $number($row->area, 2) }} m²</td><td class="px-4 py-3 text-right font-semibold tabular-nums text-emerald-700">{{ $money($row->net_revenue) }}</td><td class="px-4 py-3">{{ $row->top_operator?->name ?? '-' }}@if($row->top_operator)<p class="text-xs text-slate-500">{{ $row->top_operator->items }} item · {{ $number($row->top_operator->area, 2) }} m²</p>@endif</td></tr>
                            @if($row->operators->count() > 1)<tr class="bg-slate-50/70"><td class="px-4 py-2 text-xs font-semibold text-slate-500">Rincian operator</td><td colspan="6" class="px-4 py-2 text-xs text-slate-600">@foreach($row->operators as $operator)<span class="mr-4 inline-block"><strong>{{ $operator->name }}</strong>: {{ $operator->items }} item / {{ $number($operator->area, 2) }} m² / {{ $money($operator->revenue) }}</span>@endforeach</td></tr>@endif
                        @empty<tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Belum ada aktivitas printer pada periode ini.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-semibold text-slate-900">Detail Pemakaian Printer</h3><p class="mt-1 text-xs text-slate-500">Satu baris mewakili satu item order yang dicetak.</p></div>
            <div class="max-h-[620px] overflow-auto"><table class="min-w-full divide-y divide-slate-200 text-xs"><thead class="sticky top-0 bg-slate-50 uppercase tracking-wide text-slate-600"><tr><th class="px-3 py-3 text-left">Waktu Cetak</th><th class="px-3 py-3 text-left">No. Order / Customer</th><th class="px-3 py-3 text-left">Printer / Item</th><th class="px-3 py-3 text-left">Operator</th><th class="px-3 py-3 text-right">Qty</th><th class="px-3 py-3 text-right">Luas</th><th class="px-3 py-3 text-right">Omzet Bersih</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($details as $detail)<tr><td class="whitespace-nowrap px-3 py-3">{{ $detail->printed_at?->format('d/m/Y H:i') }}</td><td class="px-3 py-3"><strong>{{ $detail->order_number }}</strong><p class="text-slate-500">{{ $detail->customer }}</p></td><td class="px-3 py-3"><strong>{{ $detail->printer }}</strong><p class="text-slate-500">{{ $detail->item }}</p></td><td class="px-3 py-3">{{ $detail->operator }}</td><td class="px-3 py-3 text-right tabular-nums">{{ $number($detail->qty) }}</td><td class="px-3 py-3 text-right tabular-nums">{{ $number($detail->area, 2) }} m²</td><td class="px-3 py-3 text-right font-semibold tabular-nums">{{ $money($detail->net_revenue) }}</td></tr>@empty<tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Tidak ada detail pemakaian.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>
</x-app-layout>
