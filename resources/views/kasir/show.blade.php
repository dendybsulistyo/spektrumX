<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Bayar Order {{ $order->NoOrder }}</h2>
    </x-slot>

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

        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <form method="POST" action="{{ route('kasir.bayar', ['type' => $type, 'id' => $order->id]) }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label value="Metode Pembayaran" />
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="metode_bayar" value="tunai" checked class="text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700">Tunai (Lunas)</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="metode_bayar" value="hutang"
                                   {{ $order->customer?->isVip ? '' : 'disabled' }}
                                   class="text-gray-900 focus:ring-gray-900">
                            <span class="text-sm text-gray-700 {{ $order->customer?->isVip ? '' : 'text-gray-400' }}">
                                Hutang (khusus VIP){{ $order->customer?->isVip ? '' : ' — customer bukan VIP' }}
                            </span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('metode_bayar')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="catatan" value="Catatan (opsional)" />
                    <textarea id="catatan" name="catatan" rows="2"
                              class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>

                <button type="submit"
                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                    Proses Pembayaran
                </button>

                <a href="{{ route('invoice.show', ['type' => $type, 'id' => $order->id]) }}" target="_blank"
                   class="block text-center text-sm text-gray-500 hover:underline">
                    Lihat / Cetak Invoice
                </a>
            </form>
        </div>
    </div>
</x-app-layout>
