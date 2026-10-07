<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Penyesuaian Nota Order / DP</h2></x-slot>

    <div class="mx-auto max-w-7xl space-y-5">
        @if (session('status'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="rounded-lg border bg-white p-5 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900">Koreksi Metode Pembayaran</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <form method="GET" class="flex items-end gap-3 rounded-md border p-3">
                    <label class="flex-1 text-sm text-gray-700">Nama customer / No. nota
                        <input name="q" value="{{ $search }}" required class="mt-1 block w-full rounded-md border-gray-300" placeholder="Contoh: INV.1.26092100003 atau nama customer">
                    </label>
                    <button class="rounded-md bg-indigo-600 px-5 py-2 font-semibold text-white hover:bg-indigo-700">Cari</button>
                </form>
                <form method="GET" class="flex items-end gap-3 rounded-md border p-3">
                    <label class="flex-1 text-sm text-gray-700">Tanggal pembayaran
                        <input type="date" name="tanggal" value="{{ $date }}" required max="{{ now()->format('Y-m-d') }}" class="mt-1 block w-full rounded-md border-gray-300">
                    </label>
                    <button class="rounded-md bg-indigo-600 px-5 py-2 font-semibold text-white hover:bg-indigo-700">Cari</button>
                </form>
            </div>
        </section>

        @if ($search !== '' || $date)
            <section class="overflow-hidden rounded-lg border bg-white shadow-sm">
                <div class="flex items-center justify-between border-b px-5 py-3">
                    <h3 class="font-semibold text-gray-900">
                        Hasil pencarian
                        @if ($search !== '') “{{ $search }}” @endif
                        @if ($date) tanggal {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }} @endif
                    </h3>
                    <span class="text-sm text-gray-500">{{ $rows->count() }} pembayaran</span>
                </div>

                <form method="POST" action="{{ route('keuangan.order-adjustments.store') }}"
                      onsubmit="return confirm('Simpan semua koreksi metode pembayaran yang diubah?')">
                    @csrf
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="tanggal" value="{{ $date }}">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[800px] text-left text-sm">
                            <thead class="border-b bg-gray-50 text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="w-12 px-4 py-3">No</th>
                                    <th class="px-4 py-3">Nama Customer</th>
                                    <th class="px-4 py-3">No. SO DP / Invoice</th>
                                    <th class="px-4 py-3 text-right">Nilai Transaksi</th>
                                    <th class="px-4 py-3">Metode Kasir</th>
                                    <th class="px-4 py-3">Pengganti</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($rows as $payment)
                                    @php($method = old("payments.{$payment->id}.method", ''))
                                    <tr x-data="{ method: @js($method) }" :class="method && 'bg-amber-50'">
                                        <td class="px-4 py-2 text-gray-500">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-2 font-medium">{{ $payment->customer_name }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span class="font-semibold">{{ $payment->document_number }}</span>
                                            <div class="text-xs text-gray-500">{{ \App\Models\OrderPayment::JENIS_LABELS[$payment->jenis] ?? ucfirst($payment->jenis) }} · {{ $payment->created_at?->format('d/m/Y H:i') }}</div>
                                        </td>
                                        <td class="px-4 py-2 text-right font-semibold whitespace-nowrap">Rp {{ number_format($payment->jumlah, 0, ',', '.') }}</td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold">{{ \App\Models\OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? strtoupper($payment->cara_bayar) }}</span>
                                            @if ($payment->no_referensi)<div class="mt-1 text-xs text-gray-500">{{ $payment->no_referensi }}</div>@endif
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center gap-2">
                                                <select name="payments[{{ $payment->id }}][method]" x-model="method" class="w-36 rounded-md border-gray-300 text-sm">
                                                    <option value="">— Tidak diganti —</option>
                                                    <option value="debit">Debit/Card</option>
                                                    <option value="qris">QRIS</option>
                                                    <option value="transfer">Transfer</option>
                                                </select>
                                                <input name="payments[{{ $payment->id }}][reference]" x-show="method" maxlength="50"
                                                       value="{{ old("payments.{$payment->id}.reference") }}"
                                                       class="w-40 rounded-md border-gray-300 text-sm" :placeholder="method === 'debit' ? 'No. referensi (opsional)' : 'No. referensi'">
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">Pembayaran nota DP atau Lunas tidak ditemukan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($rows->isNotEmpty())
                        <div class="flex items-center justify-between border-t bg-gray-50 px-5 py-3">
                            <span class="text-xs text-gray-500">Baris yang diberi metode pengganti ditandai kuning. Baris tanpa pengganti tidak ikut disimpan.</span>
                            <button class="rounded-md bg-indigo-600 px-6 py-2 font-semibold text-white hover:bg-indigo-700">Update</button>
                        </div>
                    @endif
                </form>
            </section>
        @endif

        <section class="overflow-hidden rounded-lg border bg-white shadow-sm">
            <div class="flex flex-wrap items-end justify-between gap-3 border-b px-5 py-4">
                <h3 class="font-semibold text-gray-900">Histori Koreksi Metode Pembayaran</h3>
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    @if ($search !== '')<input type="hidden" name="q" value="{{ $search }}">@endif
                    @if ($date)<input type="hidden" name="tanggal" value="{{ $date }}">@endif
                    <label class="text-xs text-gray-600">Dari<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300 text-sm"></label>
                    <label class="text-xs text-gray-600">Sampai<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300 text-sm"></label>
                    <button class="rounded-md border px-3 py-2 text-sm font-semibold">Tampilkan</button>
                </form>
            </div>
            <div class="overflow-x-auto"><table class="w-full min-w-[1100px] text-left text-xs">
                <thead class="bg-gray-50 uppercase text-gray-500"><tr><th class="px-3 py-2">Tanggal</th><th class="px-3 py-2">Nota / Order</th><th class="px-3 py-2 text-right">Nominal</th><th class="px-3 py-2">Metode Lama</th><th class="px-3 py-2">Metode Baru</th><th class="px-3 py-2">Referensi Baru</th><th class="px-3 py-2">Catatan</th><th class="px-3 py-2">Jurnal</th><th class="px-3 py-2">User</th></tr></thead>
                <tbody class="divide-y">
                    @forelse ($history as $row)
                        <tr><td class="px-3 py-2 whitespace-nowrap">{{ $row->correction_date->format('d/m/Y') }}</td><td class="px-3 py-2 font-semibold">{{ $row->invoice_number ?: $row->order_number }}<div class="font-normal text-gray-500">{{ $row->order_number }}</div></td><td class="px-3 py-2 text-right font-semibold">{{ number_format($row->amount, 0, ',', '.') }}</td><td class="px-3 py-2">{{ \App\Models\OrderPayment::CARA_BAYAR_LABELS[$row->old_method] ?? strtoupper($row->old_method) }}<div class="text-gray-500">{{ $row->old_reference }}</div></td><td class="px-3 py-2">{{ \App\Models\OrderPayment::CARA_BAYAR_LABELS[$row->new_method] ?? strtoupper($row->new_method) }}</td><td class="px-3 py-2">{{ $row->new_reference ?: '-' }}</td><td class="px-3 py-2">{{ $row->reason }}</td><td class="px-3 py-2">{{ $row->journal_transaction_number ?: 'Tidak perlu' }}</td><td class="px-3 py-2">{{ $row->user?->name ?: '-' }}</td></tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">Belum ada koreksi metode pembayaran pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>
    </div>
</x-app-layout>
