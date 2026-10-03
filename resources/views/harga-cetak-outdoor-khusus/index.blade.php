<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Harga Khusus Customer VIP</h2>
    </x-slot>

    <div class="space-y-4 max-w-2xl">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <p class="text-xs text-gray-500 mb-3">
                Hanya customer VIP yang sudah mempunyai harga khusus yang ditampilkan. Kosongkan harga untuk kembali pakai Standar Harga Outdoor.
            </p>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div class="w-64">
                    <x-input-label value="Customer" />
                    <select name="KdCust" onchange="this.form.submit()" required
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih customer VIP --</option>
                        @foreach ($vipCustomers as $c)
                            <option value="{{ $c->KdCust }}" @selected($selectedKdCust === $c->KdCust)>{{ $c->NmCust }} ({{ $c->KdCust }})</option>
                        @endforeach
                    </select>
                </div>
                @if ($vipCustomers->isEmpty())
                    <p class="text-xs text-gray-400 pb-2">Belum ada customer VIP yang mempunyai harga khusus.</p>
                @endif
                <div class="w-56">
                    <x-input-label value="Printer" />
                    <select name="KdPrn" onchange="this.form.submit()" required
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">-- Pilih printer --</option>
                        @foreach ($printers as $p)
                            <option value="{{ $p->KdPrn }}" @selected($selectedKdPrn === $p->KdPrn)>{{ $p->NmPrn }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        @if ($selectedCustomer && $selectedKdPrn)
            @php $printer = $printers->firstWhere('KdPrn', $selectedKdPrn); @endphp
            @can('harga-cetak-outdoor-khusus.manage')
                <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-indigo-900">Salin Harga Customer VIP</h3>
                        <p class="mt-1 text-xs text-indigo-700">Harga tujuan pada cakupan yang dipilih akan dibuat sama persis dengan customer sumber.</p>
                    </div>
                    <form method="POST" action="{{ route('harga-cetak-outdoor-khusus.copy') }}"
                          class="flex flex-wrap items-end gap-3"
                          onsubmit="return confirm('Salin harga khusus dan mengganti harga customer tujuan pada cakupan yang dipilih?')">
                        @csrf
                        <input type="hidden" name="target_kd_cust" value="{{ $selectedKdCust }}">
                        <input type="hidden" name="KdPrn" value="{{ $selectedKdPrn }}">
                        <div class="min-w-64 flex-1">
                            <x-input-label for="source_kd_cust" value="Salin dari customer" />
                            <select id="source_kd_cust" name="source_kd_cust" required
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Pilih customer sumber --</option>
                                @foreach ($vipCustomers->where('KdCust', '!=', $selectedKdCust) as $c)
                                    <option value="{{ $c->KdCust }}" @selected(old('source_kd_cust') === $c->KdCust)>{{ $c->NmCust }} ({{ $c->KdCust }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-48">
                            <x-input-label for="copy_scope" value="Harga yang disalin" />
                            <select id="copy_scope" name="scope" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="printer" @selected(old('scope') !== 'all')>Printer ini saja</option>
                                <option value="all" @selected(old('scope') === 'all')>Semua printer</option>
                            </select>
                        </div>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            Salin Harga
                        </button>
                    </form>
                </div>
            @endcan

            <form method="POST" action="{{ route('harga-cetak-outdoor-khusus.update-matrix') }}" novalidate>
                @csrf
                <input type="hidden" name="KdCust" value="{{ $selectedKdCust }}">
                <input type="hidden" name="KdPrn" value="{{ $selectedKdPrn }}">

                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto" style="max-height: 70vh; overflow-y: auto;">
                        <div class="px-4 h-11 flex items-center border-b border-gray-200 bg-gray-50 sticky top-0 z-30">
                            <p class="text-sm text-gray-700 truncate">
                                Customer: <span class="font-semibold">{{ $selectedCustomer->NmCust }} ({{ $selectedCustomer->KdCust }})</span>
                                &middot; Printer: <span class="font-semibold">{{ $printer?->NmPrn }}</span>
                            </p>
                        </div>
                        <table class="w-full text-[13px] border-collapse">
                            <thead class="text-white text-xs uppercase sticky top-11 z-20">
                                <tr class="bg-gray-900">
                                    <th class="px-3 py-2 text-left">Bahan</th>
                                    <th class="px-2 py-2 text-center w-16">Kode</th>
                                    <th class="px-2 py-2 text-center w-40">Harga Standar</th>
                                    <th class="px-2 py-2 text-center w-40">Harga Khusus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse ($bahanList as $bahan)
                                    @php
                                        $kdCtk = $selectedKdPrn.$bahan->NoCetak;
                                        $standar = $standardPrices->get($kdCtk);
                                        $khusus = $khususPrices->get($kdCtk);
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 bg-red-600 text-white font-medium whitespace-nowrap">{{ $bahan->NmBhn }}</td>
                                        <td class="px-2 py-2 text-center bg-red-600 text-white font-semibold">{{ $bahan->NoCetak }}</td>
                                        <td class="px-2 py-2 text-center text-gray-500">
                                            {{ $standar ? 'Rp '.number_format($standar->HargaStd, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="px-1 py-1.5">
                                            <input type="text" inputmode="numeric" data-rupiah
                                                   name="harga[{{ $bahan->NoCetak }}]"
                                                   value="{{ old('harga.'.$bahan->NoCetak, $khusus ? number_format($khusus->HargaStd, 0, ',', '.') : '') }}"
                                                   placeholder="{{ $standar ? number_format($standar->HargaStd, 0, ',', '.') : '-' }}"
                                                   class="w-32 text-right rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 mx-auto block">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">Belum ada data bahan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 border-t border-gray-200 flex items-center justify-between">
                        <p class="text-xs text-gray-400">Kosongkan kolom Harga Khusus untuk kembali pakai Harga Standar.</p>
                        <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                            Simpan Harga Khusus
                        </button>
                    </div>
                </div>
            </form>
        @else
            <div class="bg-white rounded-lg border border-gray-200 p-10 text-center text-gray-400 text-sm">
                Pilih customer VIP dan printer dulu untuk mengatur harga khusus.
            </div>
        @endif
    </div>
</x-app-layout>
