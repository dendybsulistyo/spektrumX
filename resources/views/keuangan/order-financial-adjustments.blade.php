<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Penyesuaian Nota Order / DP</h2></x-slot>

    <div class="mx-auto max-w-7xl space-y-5">
        @if (session('status'))<div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        @if (session('error'))<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <h3 class="font-semibold text-slate-900">Aturan aman penyesuaian</h3>
            <p class="mt-1">Nota asli dan pembayaran lama tidak dihapus. Tambahan/refund DP dicatat sebagai mutasi baru, memperbarui saldo, dan membuat jurnal otomatis. Koreksi harga menggunakan potongan akhir atau nota pengganti agar rincian produk dan invoice tetap konsisten.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('keuangan.final-sales-discounts.index') }}" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white">Potongan / Pengurangan Nota</a>
                <a href="{{ route('keuangan.pembatalan-order') }}" class="rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold">Batalkan / Nota Pengganti</a>
            </div>
        </section>

        <section class="rounded-lg border bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div><h3 class="font-semibold text-gray-900">Tambah atau Refund DP</h3><p class="text-xs text-gray-500">Hanya order Indoor/Outdoor dengan DP aktif.</p></div>
                <form method="GET" class="flex flex-wrap items-end gap-2">
                    <label class="text-xs text-gray-600">Cari order/customer<input name="q" value="{{ $search }}" class="mt-1 block rounded-md border-gray-300 text-sm" placeholder="No. order atau customer"></label>
                    <label class="text-xs text-gray-600">Histori dari<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300 text-sm"></label>
                    <label class="text-xs text-gray-600">Sampai<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300 text-sm"></label>
                    <button class="rounded-md border px-3 py-2 text-sm font-semibold">Cari</button>
                </form>
            </div>

            @can('keuangan.pengaturan')
                <form method="POST" action="{{ route('keuangan.order-adjustments.store') }}" class="grid gap-4 lg:grid-cols-3">
                    @csrf
                    <label class="text-sm text-gray-700 lg:col-span-2">Order DP
                        <select name="order_key" required class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Pilih order</option>
                            @foreach($orders as $order)
                                <option value="{{ $order->order_type }}:{{ $order->id }}" @selected(old('order_key') === $order->order_type.':'.$order->id)>
                                    {{ $order->NoOrder }} · {{ $order->customer?->NmCust ?: '-' }} · DP Rp {{ number_format($order->jumlah_dibayar,0,',','.') }} · Sisa Rp {{ number_format($order->jumlah_piutang,0,',','.') }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm text-gray-700">Tanggal transaksi<input type="date" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300"></label>
                    <label class="text-sm text-gray-700">Jenis penyesuaian
                        <select name="adjustment_type" required class="mt-1 block w-full rounded-md border-gray-300"><option value="dp_tambah" @selected(old('adjustment_type')==='dp_tambah')>Tambah DP diterima</option><option value="dp_refund" @selected(old('adjustment_type')==='dp_refund')>Refund / kurangi DP</option></select>
                    </label>
                    <label class="text-sm text-gray-700">Nominal<input name="amount" inputmode="numeric" value="{{ old('amount') }}" required placeholder="Contoh: 500000" class="mt-1 block w-full rounded-md border-gray-300"></label>
                    <label class="text-sm text-gray-700">Metode uang
                        <select name="payment_method" required class="mt-1 block w-full rounded-md border-gray-300"><option value="tunai" @selected(old('payment_method')==='tunai')>Tunai</option><option value="qris" @selected(old('payment_method')==='qris')>QRIS</option><option value="transfer" @selected(old('payment_method')==='transfer')>Transfer</option></select>
                    </label>
                    <label class="text-sm text-gray-700">Nomor referensi<input name="reference_number" value="{{ old('reference_number') }}" maxlength="50" class="mt-1 block w-full rounded-md border-gray-300" placeholder="Wajib untuk QRIS/transfer"></label>
                    <label class="text-sm text-gray-700 lg:col-span-2">Alasan penyesuaian<input name="reason" value="{{ old('reason') }}" required maxlength="255" class="mt-1 block w-full rounded-md border-gray-300" placeholder="Contoh: nominal transfer DP kurang tercatat"></label>
                    <div class="flex items-end"><button class="w-full rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white" onclick="return confirm('Simpan penyesuaian dan posting jurnal?')">Simpan &amp; Posting Jurnal</button></div>
                </form>
            @else
                <p class="rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-800">Anda dapat melihat histori, tetapi pencatatan baru memerlukan hak akses Pengaturan Keuangan.</p>
            @endcan
        </section>

        <section class="overflow-hidden rounded-lg border bg-white shadow-sm">
            <div class="border-b px-5 py-4"><h3 class="font-semibold text-gray-900">Histori Penyesuaian DP</h3></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[1100px] text-left text-xs">
                <thead class="bg-gray-50 uppercase text-gray-500"><tr><th class="px-3 py-2">Tanggal</th><th class="px-3 py-2">Order</th><th class="px-3 py-2">Customer</th><th class="px-3 py-2">Jenis</th><th class="px-3 py-2 text-right">Nominal</th><th class="px-3 py-2 text-right">DP Sebelum → Sesudah</th><th class="px-3 py-2 text-right">Sisa Sebelum → Sesudah</th><th class="px-3 py-2">Metode</th><th class="px-3 py-2">Alasan / Jurnal</th><th class="px-3 py-2">User</th></tr></thead>
                <tbody class="divide-y">
                @forelse($history as $row)
                    <tr><td class="px-3 py-2 whitespace-nowrap">{{ $row->transaction_date->format('d/m/Y') }}</td><td class="px-3 py-2 font-semibold">{{ $row->order_number }}<div class="font-normal text-gray-500">{{ ucfirst($row->order_type) }}</div></td><td class="px-3 py-2">{{ $row->customer?->NmCust ?: '-' }}</td><td class="px-3 py-2">{{ $row->adjustment_type === 'dp_tambah' ? 'Tambah DP' : 'Refund DP' }}</td><td class="px-3 py-2 text-right font-semibold">{{ number_format($row->amount,0,',','.') }}</td><td class="px-3 py-2 text-right whitespace-nowrap">{{ number_format($row->paid_before,0,',','.') }} → {{ number_format($row->paid_after,0,',','.') }}</td><td class="px-3 py-2 text-right whitespace-nowrap">{{ number_format($row->receivable_before,0,',','.') }} → {{ number_format($row->receivable_after,0,',','.') }}</td><td class="px-3 py-2">{{ strtoupper($row->payment_method) }}<div class="text-gray-500">{{ $row->reference_number }}</div></td><td class="px-3 py-2">{{ $row->reason }}<div class="text-gray-500">Jurnal: {{ $row->journal_transaction_number }}</div></td><td class="px-3 py-2">{{ $row->user?->name ?: '-' }}</td></tr>
                @empty<tr><td colspan="10" class="px-4 py-8 text-center text-gray-500">Belum ada penyesuaian DP pada periode ini.</td></tr>@endforelse
                </tbody>
            </table></div>
        </section>
    </div>
</x-app-layout>
