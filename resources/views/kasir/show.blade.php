<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Bayar Order {{ $order->NoOrder }}</h2>
    </x-slot>

    <div x-data="{ invoiceModalOpen: false, autoPrintPending: {{ session('autoPrintInvoice') ? 'true' : 'false' }} }"
         x-init="if (autoPrintPending) { invoiceModalOpen = true }"
         @keydown.escape.window="invoiceModalOpen = false">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <p class="text-sm text-gray-500">Customer</p>
                <p class="font-semibold text-gray-900">{{ $order->customer?->NmCust ?? '-' }}</p>
                @if ($order->customer?->isVip)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 mt-1">VIP</span>
                    <p class="text-xs text-gray-500 mt-1">
                        Limit: Rp {{ number_format($order->customer->limit->Batas, 0, ',', '.') }} —
                        Piutang berjalan: Rp {{ number_format($order->customer->limit->Total, 0, ',', '.') }} —
                        Sisa: Rp {{ number_format($order->customer->limit->Batas - $order->customer->limit->Total, 0, ',', '.') }}
                    </p>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[480px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3 text-right">Panjang</th>
                            <th class="px-4 py-3 text-right">Lebar</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($items as $item)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $item->Judul ?? $item->NmFile }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $item->Panjang }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $item->Lebar }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $item->Qty }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-200 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700">Total</span>
                <span class="text-lg font-bold text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-4"
             x-data="{
                metode: '{{ old('metode_bayar', 'tunai') }}',
                jumlahDp: '{{ old('jumlah_dp') }}',
                dpMin: {{ (int) ceil(($order->total ?? 0) * 0.5) }},
                dpMax: {{ max((int) ($order->total ?? 0) - 1, 0) }},
                get dpError() {
                    if (this.metode !== 'dp' || this.jumlahDp === '') return '';
                    const val = Number(this.jumlahDp);
                    if (val < this.dpMin) return `Jumlah DP minimal Rp ${this.dpMin.toLocaleString('id-ID')}.`;
                    if (val > this.dpMax) return `Jumlah DP tidak boleh melebihi Rp ${this.dpMax.toLocaleString('id-ID')}.`;
                    return '';
                }
             }">
            <form method="POST" action="{{ route('kasir.bayar', ['type' => $type, 'id' => $order->id]) }}"
                  class="space-y-4" novalidate
                  @submit="if (dpError) { $event.preventDefault(); }">
                @csrf

                <div>
                    <x-input-label value="Metode Pembayaran" />
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="metode_bayar" value="tunai" x-model="metode" class="text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700">Tunai (Lunas)</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="metode_bayar" value="hutang" x-model="metode"
                                   {{ $order->customer?->isVip ? '' : 'disabled' }}
                                   class="text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700 {{ $order->customer?->isVip ? '' : 'text-gray-400' }}">
                                Hutang (khusus VIP){{ $order->customer?->isVip ? '' : ' — customer bukan VIP' }}
                            </span>
                        </label>
                        @if ($type === 'outdoor')
                            <label class="flex items-center gap-2">
                                <input type="radio" name="metode_bayar" value="dp" x-model="metode" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">DP (minimal 50%)</span>
                            </label>
                        @endif
                    </div>
                    <x-input-error :messages="$errors->get('metode_bayar')" class="mt-1" />
                </div>

                @if ($type === 'outdoor')
                    <div x-show="metode === 'dp'" x-cloak>
                        <x-input-label for="jumlah_dp" value="Jumlah DP" />
                        <x-text-input id="jumlah_dp" name="jumlah_dp" type="number" step="100"
                                      x-model="jumlahDp"
                                      class="mt-1 block w-full" />
                        <p class="text-xs text-gray-500 mt-1">
                            Minimal Rp {{ number_format(($order->total ?? 0) * 0.5, 0, ',', '.') }} (50% dari total Rp {{ number_format($order->total ?? 0, 0, ',', '.') }})
                        </p>
                        <x-input-error :messages="$errors->get('jumlah_dp')" class="mt-1" />
                    </div>
                @endif

                <p x-show="dpError" x-cloak x-text="dpError" class="text-sm text-red-600"></p>

                <div>
                    <x-input-label for="catatan" value="Catatan (opsional)" />
                    <textarea id="catatan" name="catatan" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <button type="submit"
                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Proses Pembayaran
                </button>

                <button type="button" @click="invoiceModalOpen = true"
                        class="block w-full text-center text-sm text-gray-500 hover:underline">
                    Lihat / Cetak Invoice
                </button>
            </form>
        </div>
    </div>

    <div x-show="invoiceModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="invoiceModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div class="relative bg-white rounded-lg shadow-lg flex flex-col" style="width:95vw; max-width:1100px; height:94vh;">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-gray-900">Invoice {{ $order->NoOrder }}</h3>
                <div class="flex items-center gap-2">
                    <button type="button" @click="$refs.invoiceFrame.contentWindow.focus(); $refs.invoiceFrame.contentWindow.print()"
                            class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                        Cetak
                    </button>
                    <button type="button" @click="$refs.invoiceFrame.contentDocument.title = '{{ $order->NoOrder }}'; $refs.invoiceFrame.contentWindow.focus(); $refs.invoiceFrame.contentWindow.print()"
                            class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-semibold rounded-md hover:bg-gray-200">
                        PDF
                    </button>
                    <button type="button" @click="invoiceModalOpen = false"
                            class="inline-flex items-center px-2 py-1.5 text-gray-400 hover:text-gray-600 text-lg leading-none">
                        &times;
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-hidden">
                <iframe x-ref="invoiceFrame"
                        x-show="invoiceModalOpen"
                        :src="invoiceModalOpen ? '{{ route('invoice.show', ['type' => $type, 'id' => $order->id]) }}' : ''"
                        @load="if (autoPrintPending) { autoPrintPending = false; $refs.invoiceFrame.contentWindow.focus(); $refs.invoiceFrame.contentWindow.print(); }"
                        class="w-full h-full border-0"></iframe>
            </div>
        </div>
    </div>
    </div>
</x-app-layout>
