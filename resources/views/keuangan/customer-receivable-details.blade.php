<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Piutang per Customer</h2></x-slot>
    <style>
        .customer-receivable-detail { color:#111827; }
        .customer-receivable-detail table { width:100%; border-collapse:collapse; font-size:12px; }
        .customer-receivable-detail th,.customer-receivable-detail td { border:1px solid #64748b; padding:7px; }
        .customer-receivable-detail th { background:#e2e8f0; text-align:center; }
        .customer-receivable-detail .number { text-align:right; white-space:nowrap; }
    </style>

    <div class="customer-receivable-detail py-6"
         x-data="{
             modalOpen: false, mode: 'lunasi', type: '', id: null, invoice: '', remaining: 0,
             method: 'tunai', amount: '', reference: '', error: '',
             open(mode, type, id, invoice, remaining) {
                 this.mode = mode; this.type = type; this.id = id; this.invoice = invoice;
                 this.remaining = Number(remaining); this.amount = mode === 'lunasi' ? String(remaining) : '';
                 this.method = 'tunai'; this.reference = ''; this.error = ''; this.modalOpen = true;
             },
             submit(event) {
                 const amount = Number(this.amount || 0); this.error = '';
                 if (amount < 100) this.error = 'Nominal minimal Rp 100.';
                 else if (this.mode === 'cicil' && amount >= this.remaining) this.error = 'Nominal cicilan harus lebih kecil dari sisa piutang.';
                 else if (this.method !== 'tunai' && !this.reference.trim()) this.error = 'No. referensi wajib untuk QRIS/Transfer.';
                 if (this.error) event.preventDefault();
             }
         }">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            @if (session('status'))<div class="mb-4 rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif

            <div class="mb-4 rounded-lg border bg-white p-4 shadow-sm">
                <form method="GET" class="flex flex-wrap items-end gap-3">
                    <label class="text-sm text-gray-700">Customer
                        <select name="customer" required onchange="this.form.submit()" class="mt-1 block min-w-72 rounded-md border-gray-300">
                            <option value="">Pilih customer</option>
                            @foreach($customers as $customer)<option value="{{ $customer->KdCust }}" @selected($customerCode === $customer->KdCust)>{{ $customer->NmCust }}</option>@endforeach
                        </select>
                    </label>
                    <noscript><button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button></noscript>
                </form>
            </div>

            <section class="bg-white p-5 shadow-sm">
                <div class="mb-4"><h1 class="text-base font-bold">REKAP PIUTANG PER CUSTOMER - SPEKTRUM</h1><p class="text-sm font-semibold">Customer: {{ $selectedCustomer?->NmCust ?? 'Pilih customer terlebih dahulu' }}</p></div>
                <div class="overflow-x-auto">
                    <table style="min-width:980px;">
                        <thead><tr><th>Tanggal</th><th>No. Nota</th><th>Piutang</th><th>Discount</th><th>Bayar</th><th>Sisa Piutang</th><th style="width:145px;">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td style="text-align:center;">{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td><td>{{ $row->invoice }}</td>
                                    <td class="number">{{ number_format($row->receivable,0,',','.') }}</td><td class="number">{{ number_format($row->discount,0,',','.') }}</td><td class="number">{{ number_format($row->paid,0,',','.') }}</td><td class="number font-semibold">{{ number_format($row->remaining,0,',','.') }}</td>
                                    <td class="whitespace-nowrap text-center">
                                        @can('kasir.manage')
                                            <button type="button" @click="open('lunasi', '{{ $row->type }}', {{ $row->id }}, @js($row->invoice), {{ $row->remaining }})" class="rounded bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">Lunasi</button>
                                            <button type="button" @click="open('cicil', '{{ $row->type }}', {{ $row->id }}, @js($row->invoice), {{ $row->remaining }})" class="rounded bg-amber-500 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-amber-600">Cicil</button>
                                        @else<span class="text-xs text-slate-400">Lihat saja</span>@endcan
                                    </td>
                                </tr>
                            @empty<tr><td colspan="7" class="py-10 text-center">{{ $selectedCustomer ? 'Customer ini tidak memiliki piutang aktif.' : 'Pilih customer untuk menampilkan seluruh piutang aktif.' }}</td></tr>@endforelse
                        </tbody>
                        <tfoot><tr><td colspan="2" class="number"><strong>Total Piutang</strong></td><td class="number"><strong>{{ number_format($totals->receivable,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->discount,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->paid,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totals->remaining,0,',','.') }}</strong></td><td></td></tr></tfoot>
                    </table>
                </div>
            </section>
        </div>

        <div x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="modalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>
            <div class="relative w-full max-w-md rounded-lg bg-white shadow-xl">
                <form method="POST" :action="`/kasir/${type}/${id}/${mode === 'lunasi' ? 'lunasi-hutang' : 'cicil-hutang'}`" @submit="submit($event)" class="space-y-4 p-5">
                    @csrf
                    <input type="hidden" name="return_to" value="customer_receivables">
                    <div><h3 class="font-semibold text-gray-900" x-text="mode === 'lunasi' ? 'Pelunasan Piutang' : 'Cicilan Piutang'"></h3><p class="mt-1 text-sm text-gray-500"><span x-text="invoice"></span> · Sisa Rp <span x-text="remaining.toLocaleString('id-ID')"></span></p></div>
                    <label class="block text-sm font-medium text-gray-700">Cara Bayar<select name="rincian[0][cara_bayar]" x-model="method" class="mt-1 block w-full rounded-md border-gray-300"><option value="tunai">Tunai</option><option value="qris">QRIS</option><option value="transfer">Transfer</option></select></label>
                    <label class="block text-sm font-medium text-gray-700">Nominal
                        <input type="text" inputmode="numeric" :readonly="mode === 'lunasi'" :value="amount ? Number(amount).toLocaleString('id-ID') : ''" @input="amount = $event.target.value.replace(/\D/g, '')" class="mt-1 block w-full rounded-md border-gray-300 read-only:bg-gray-100">
                        <input type="hidden" name="rincian[0][jumlah]" :value="amount">
                    </label>
                    <label x-show="method !== 'tunai'" x-cloak class="block text-sm font-medium text-gray-700">No. Referensi<input type="text" name="rincian[0][no_referensi]" x-model="reference" maxlength="50" class="mt-1 block w-full rounded-md border-gray-300"></label>
                    <p x-show="error" x-text="error" class="text-sm font-semibold text-red-600"></p>
                    <div class="flex justify-end gap-2"><button type="button" @click="modalOpen = false" class="px-3 py-2 text-sm text-gray-500">Batal</button><button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white" x-text="mode === 'lunasi' ? 'Konfirmasi Lunasi' : 'Simpan Cicilan'"></button></div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
