<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Antrian Finishing</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div x-data="{ tab: 'indoor' }">
            <div class="flex border-b border-gray-200 text-sm gap-1 p-1.5">
                <button @click="tab = 'indoor'" :class="tab === 'indoor' ? 'bg-amber-100 text-amber-800' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-700'"
                        class="px-4 py-2 rounded-md font-medium transition">
                    Indoor ({{ $indoorOrders->count() }})
                </button>
                <button @click="tab = 'outdoor'" :class="tab === 'outdoor' ? 'bg-teal-100 text-teal-800' : 'text-gray-500 hover:bg-teal-50 hover:text-teal-700'"
                        class="px-4 py-2 rounded-md font-medium transition">
                    Outdoor ({{ $outdoorOrders->count() }})
                </button>
                <button @click="tab = 'artwork'" :class="tab === 'artwork' ? 'bg-violet-100 text-violet-800' : 'text-gray-500 hover:bg-violet-50 hover:text-violet-700'"
                        class="px-4 py-2 rounded-md font-medium transition">
                    Artwork ({{ $artworkOrders->count() }})
                </button>
            </div>

            @foreach (['indoor' => $indoorOrders, 'outdoor' => $outdoorOrders, 'artwork' => $artworkOrders] as $tabKey => $orders)
                <div x-show="tab === '{{ $tabKey }}'" @if($tabKey!=='indoor') x-cloak @endif class="overflow-x-auto">
                    <table class="w-full text-[13px] min-w-[560px]">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-3 py-2 w-12">No</th>
                                <th class="px-3 py-2">No Order</th>
                                <th class="px-3 py-2">Tanggal</th>
                                <th class="px-3 py-2">Customer</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 font-semibold text-gray-900"><x-order-number :number="$order->NoOrder" /></td>
                                    <td class="px-3 py-2 text-gray-600">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if ($tabKey === 'outdoor')
                                                <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                     :comments="$outdoorComments->get($order->id, collect())"
                                                                     :unread="$outdoorUnread->get($order->id, 0)" />
                                            @endif
                                            <form method="POST" action="{{ route('order-finishing.update', [$tabKey, $order->id]) }}"
                                                  onsubmit="return confirm('Yakin ingin mengirim order {{ $order->NoOrder }} ke QC?')" class="inline">
                                                @csrf
                                                <input type="hidden" name="action" value="selesai">
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                                    Kirim QC
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Tidak ada order di antrian finishing.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
