<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Pengambilan Barang</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden" x-data="{ tab: 'indoor' }">
        <div class="flex border-b border-gray-200 text-sm">
            <button @click="tab = 'indoor'" :class="tab === 'indoor' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                    class="px-4 py-3 border-b-2 font-medium">
                Indoor ({{ $indoorOrders->count() }})
            </button>
            <button @click="tab = 'outdoor'" :class="tab === 'outdoor' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                    class="px-4 py-3 border-b-2 font-medium">
                Outdoor ({{ $outdoorOrders->count() }})
            </button>
        </div>

        @foreach (['indoor' => $indoorOrders, 'outdoor' => $outdoorOrders] as $tabKey => $orders)
            <div x-show="tab === '{{ $tabKey }}'" @if($tabKey==='outdoor') x-cloak @endif class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 w-12">No</th>
                            <th class="px-4 py-3">No Order</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Pembayaran</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-4 py-3 text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $order->NoOrder }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $order->customer?->NmCust ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if ($order->status_bayar === 'lunas')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Lunas</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            Hutang Rp {{ number_format($order->jumlah_piutang, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('pengambilan.serahkan', ['type' => $tabKey, 'id' => $order->id]) }}"
                                          onsubmit="return confirm('Konfirmasi barang order {{ $order->NoOrder }} sudah diserahkan ke customer?')">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                            Serahkan Barang
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada barang yang siap diambil.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
</x-app-layout>
