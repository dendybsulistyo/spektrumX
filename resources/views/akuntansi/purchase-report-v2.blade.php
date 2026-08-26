<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Laporan Pembelian</h2></x-slot>
    @php($money = fn ($value) => number_format((float) $value, 0, ',', '.'))
    @php($quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ','))
    @php($label = ['bahan_baku' => 'Bahan Baku', 'bahan_penolong' => 'Bahan Penolong', 'aset' => 'Aset / Disusutkan', 'biaya' => 'Biaya'])

    <div class="py-8"><div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
        <section class="rounded-xl border bg-white p-5 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-sm text-gray-700">Dari<input type="date" name="from" value="{{ $from }}" class="mt-1 block rounded-lg border-gray-300 text-sm"></label>
                <label class="text-sm text-gray-700">Sampai<input type="date" name="to" value="{{ $to }}" class="mt-1 block rounded-lg border-gray-300 text-sm"></label>
                <label class="text-sm text-gray-700">Klasifikasi<select name="klasifikasi" class="mt-1 block rounded-lg border-gray-300 text-sm"><option value="">Semua</option>@foreach($label as $key => $value)<option value="{{ $key }}" @selected($classification === $key)>{{ $value }}</option>@endforeach</select></label>
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <p class="mt-3 text-sm text-gray-500">Format lengkap pembelian: DPP, PPN, total, kelompok laporan, termin, penerimaan invoice, dan metode pembayaran.</p>
        </section>

        <section class="grid gap-3 sm:grid-cols-4">@foreach($label as $key => $value)<div class="rounded-xl border bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $value }}</p><p class="mt-2 text-lg font-bold text-gray-900">{{ $money($totals[$key] ?? 0) }}</p></div>@endforeach</section>

        <section class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <div class="border-b px-5 py-4"><h3 class="font-semibold text-gray-900">Rincian Pembelian</h3></div>
            <div class="overflow-x-auto">
                <table class="min-w-max text-sm" style="min-width: 1500px">
                    <thead class="bg-indigo-50 text-xs uppercase tracking-wide text-indigo-700"><tr><th class="whitespace-nowrap px-4 py-3 text-left">Tanggal</th><th class="whitespace-nowrap px-4 py-3 text-left">Supplier</th><th class="whitespace-nowrap px-4 py-3 text-left">No. SJ / Faktur</th><th class="whitespace-nowrap px-4 py-3 text-left">Bahan / Barang</th><th class="whitespace-nowrap px-4 py-3 text-left">Kelompok</th><th class="whitespace-nowrap px-4 py-3 text-right">Qty</th><th class="whitespace-nowrap px-4 py-3 text-left">Unit</th><th class="whitespace-nowrap px-4 py-3 text-right">Harga / Unit</th><th class="whitespace-nowrap px-4 py-3 text-right">DPP</th><th class="whitespace-nowrap px-4 py-3 text-right">PPN</th><th class="whitespace-nowrap px-4 py-3 text-right">Total</th><th class="whitespace-nowrap px-4 py-3 text-center">Termin</th><th class="whitespace-nowrap px-4 py-3 text-left">Terima Invoice</th><th class="whitespace-nowrap px-4 py-3 text-left">Pembayaran</th></tr></thead>
                    <tbody class="divide-y">@forelse($lines as $line)@php($payment = $line->purchase->status === 'hutang' || $line->purchase->status === 'lunas' ? 'Kredit' : ucfirst($line->purchase->cara_bayar ?: 'Tunai'))<tr><td class="whitespace-nowrap px-3 py-3">{{ $line->purchase->tanggal->format('d-m-Y') }}</td><td class="px-3 py-3">{{ $line->purchase->supplier->nama }}</td><td class="px-3 py-3 font-medium text-indigo-700">{{ $line->purchase->nomor_bukti }}</td><td class="px-3 py-3">{{ $line->deskripsi }}</td><td class="px-3 py-3">{{ $label[$line->klasifikasi] }}</td><td class="px-3 py-3 text-right">{{ $quantity($line->qty) }}</td><td class="px-3 py-3">{{ $line->satuan }}</td><td class="px-3 py-3 text-right">{{ $money($line->harga_satuan) }}</td><td class="px-3 py-3 text-right">{{ $money($line->subtotal) }}</td><td class="px-3 py-3 text-right">{{ $money($line->ppn_laporan) }}</td><td class="px-3 py-3 text-right font-semibold">{{ $money($line->total_laporan) }}</td><td class="px-3 py-3 text-center">{{ $line->purchase->termin_hari !== null ? $line->purchase->termin_hari.' hari' : '—' }}</td><td class="whitespace-nowrap px-3 py-3">{{ $line->purchase->tanggal_terima_invoice?->format('d-m-Y') ?: '—' }}</td><td class="px-3 py-3">{{ $payment }}</td></tr>@empty<tr><td colspan="14" class="px-5 py-10 text-center text-gray-500">Belum ada pembelian dengan rincian item pada periode ini.</td></tr>@endforelse</tbody>
                    <tfoot class="bg-gray-50 font-semibold"><tr><td colspan="8" class="px-3 py-3 text-right">Total</td><td class="px-3 py-3 text-right">{{ $money($summary->dpp) }}</td><td class="px-3 py-3 text-right">{{ $money($summary->ppn) }}</td><td class="px-3 py-3 text-right">{{ $money($summary->total) }}</td><td colspan="3"></td></tr></tfoot>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
