<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Potongan Penjualan Akhir</h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <section class="rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900">Cari SO yang sudah masuk keuangan</h3>
                    <p class="mt-1 text-sm text-slate-500">Nilai SO awal tetap utuh. Potongan dicatat sebagai koreksi terpisah beserta jurnal dan penyelesaian piutang/refund.</p>
                </div>
                <form method="GET" class="grid items-end gap-3 px-5 py-4 md:grid-cols-[minmax(260px,1fr)_170px_170px_auto]">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Order atau customer
                        <input type="search" name="q" value="{{ $search }}" placeholder="No. SO atau nama customer" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </label>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Dari tanggal
                        <input type="date" name="dari" value="{{ $from }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </label>
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Sampai tanggal
                        <input type="date" name="sampai" value="{{ $to }}" class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </label>
                    <button class="h-[38px] rounded-md bg-slate-900 px-5 text-sm font-semibold text-white hover:bg-slate-800">Tampilkan</button>
                </form>
            </section>

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div><h3 class="text-base font-semibold text-slate-900">Daftar transaksi</h3><p class="mt-1 text-xs text-slate-500">{{ $orders->count() }} SO ditemukan pada periode ini.</p></div>
                    <a href="{{ route('report.sales-discounts') }}" class="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">Lihat Rekap Potongan Penjualan</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600">
                            <tr><th class="px-4 py-3 text-left">SO / Customer</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-right">Nilai Awal</th><th class="px-4 py-3 text-right">Potongan</th><th class="px-4 py-3 text-right">Nilai Berjalan</th><th class="px-4 py-3 text-right">Piutang</th><th class="px-4 py-3 text-center">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="px-4 py-3"><p class="font-semibold text-slate-900">{{ $order->number }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $order->customer }} · {{ $order->type_label }} · {{ $order->paid_at?->format('d-m-Y H:i') }}</p></td>
                                <td class="px-4 py-3"><span class="inline-flex rounded border border-slate-200 bg-slate-50 px-2 py-1 text-xs font-semibold text-slate-700">{{ strtoupper($order->payment_status) }}</span></td>
                                <td class="px-4 py-3 text-right tabular-nums">Rp {{ number_format($order->initial_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-red-700">Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums">Rp {{ number_format($order->final_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">Rp {{ number_format($order->receivable_amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @can('keuangan.pengaturan')
                                        <button type="button" onclick="document.getElementById('discount-form-{{ str_replace(':', '-', $order->key) }}').classList.toggle('hidden')" class="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">Input Potongan</button>
                                    @else
                                        <span class="text-xs text-slate-400">Lihat saja</span>
                                    @endcan
                                </td>
                            </tr>
                            @can('keuangan.pengaturan')
                                <tr id="discount-form-{{ str_replace(':', '-', $order->key) }}" class="hidden bg-slate-50">
                                    <td colspan="7" class="px-4 py-4">
                                        <form method="POST" action="{{ route('keuangan.final-sales-discounts.store') }}"
                                              x-data="{
                                                  amount: 0,
                                                  displayAmount: '',
                                                  receivable: {{ $order->receivable_amount }},
                                                  current: {{ $order->final_amount }},
                                                  formatAmount(event) {
                                                      const digits = event.target.value.replace(/\D/g, '');
                                                      this.amount = Number(digits || 0);
                                                      this.displayAmount = this.amount ? `Rp ${this.amount.toLocaleString('id-ID')}` : '';
                                                      event.target.value = this.displayAmount;
                                                  },
                                                  get refund(){ return Math.max(0, this.amount - this.receivable) }
                                              }"
                                              class="grid items-end gap-3 lg:grid-cols-[150px_170px_minmax(220px,1fr)_150px_180px_auto]">
                                            @csrf
                                            <input type="hidden" name="order_key" value="{{ $order->key }}">
                                            <label class="text-xs font-semibold text-slate-600">Tanggal koreksi<input type="date" name="transaction_date" value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required class="mt-1 block w-full rounded-md border-slate-300 text-sm"></label>
                                            <label class="text-xs font-semibold text-slate-600">Nominal potongan
                                                <input type="text" inputmode="numeric" autocomplete="off" :value="displayAmount" @input="formatAmount($event)" required class="mt-1 block w-full rounded-md border-slate-300 text-sm" placeholder="Rp 0">
                                                <input type="hidden" name="discount_amount" :value="amount">
                                            </label>
                                            <label class="text-xs font-semibold text-slate-600">Alasan<input type="text" name="reason" maxlength="255" required class="mt-1 block w-full rounded-md border-slate-300 text-sm" placeholder="Alasan potongan akhir"></label>
                                            <label class="text-xs font-semibold text-slate-600">Cara refund<select name="refund_method" class="mt-1 block w-full rounded-md border-slate-300 text-sm"><option value="">Tidak ada</option><option value="tunai">Tunai</option><option value="transfer">Transfer</option><option value="qris">QRIS</option></select></label>
                                            <label class="text-xs font-semibold text-slate-600">No. referensi<input type="text" name="reference_number" maxlength="50" class="mt-1 block w-full rounded-md border-slate-300 text-sm" placeholder="Untuk QRIS/transfer"></label>
                                            <button class="h-[38px] rounded-md bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700" onclick="return confirm('Catat potongan akhir dan buat jurnal koreksi?')">Simpan</button>
                                            <div class="lg:col-span-6 flex flex-wrap gap-x-5 gap-y-1 rounded border border-slate-200 bg-white px-3 py-2 text-xs text-slate-600">
                                                <span>Pengurang piutang: <strong x-text="`Rp ${Math.min(Number(amount || 0), receivable).toLocaleString('id-ID')}`"></strong></span>
                                                <span>Refund: <strong x-text="`Rp ${refund.toLocaleString('id-ID')}`"></strong></span>
                                                <span>Nilai akhir: <strong x-text="`Rp ${Math.max(0, current - Number(amount || 0)).toLocaleString('id-ID')}`"></strong></span>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endcan
                        @empty
                            <tr><td colspan="7" class="px-5 py-12 text-center text-slate-500">Tidak ada SO yang sudah masuk keuangan pada filter ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="text-base font-semibold text-slate-900">Riwayat potongan akhir</h3><p class="mt-1 text-xs text-slate-500">Jurnal koreksi dibuat saat buku besar terdampak. Potongan DP yang masih berjalan diakui saat pelunasan.</p></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-600"><tr><th class="px-4 py-3 text-left">Tanggal</th><th class="px-4 py-3 text-left">SO / Customer</th><th class="px-4 py-3 text-right">Nilai Sebelum</th><th class="px-4 py-3 text-right">Potongan</th><th class="px-4 py-3 text-right">Nilai Akhir</th><th class="px-4 py-3 text-left">Penyelesaian</th><th class="px-4 py-3 text-left">Jurnal</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($history as $row)
                            <tr><td class="whitespace-nowrap px-4 py-3">{{ $row->transaction_date->format('d-m-Y') }}</td><td class="px-4 py-3"><p class="font-semibold">{{ $row->order_number }}</p><p class="text-xs text-slate-500">{{ $row->customer?->NmCust ?? '-' }} · {{ $row->reason }} · {{ $row->user?->name ?? '-' }}</p></td><td class="px-4 py-3 text-right tabular-nums">Rp {{ number_format($row->initial_amount - $row->discount_before, 0, ',', '.') }}</td><td class="px-4 py-3 text-right font-semibold tabular-nums text-red-700">Rp {{ number_format($row->discount_amount, 0, ',', '.') }}</td><td class="px-4 py-3 text-right font-semibold tabular-nums">Rp {{ number_format($row->final_amount, 0, ',', '.') }}</td><td class="px-4 py-3 text-xs"><p>Piutang: Rp {{ number_format($row->receivable_offset, 0, ',', '.') }}</p><p>Refund: Rp {{ number_format($row->refund_amount, 0, ',', '.') }}{{ $row->refund_method ? ' · '.strtoupper($row->refund_method) : '' }}</p></td><td class="px-4 py-3 font-mono text-xs">{{ $row->journal_transaction_number ?: 'Saat pelunasan DP' }}</td></tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-slate-500">Belum ada potongan penjualan akhir pada periode ini.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
