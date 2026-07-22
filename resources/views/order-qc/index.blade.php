<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Antrian QC</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden"
         x-data="{ modalOpen: false, type: '', id: null, noOrder: '' }">
        <div x-data="{ tab: 'indoor' }">
            <div class="flex border-b border-gray-200 text-sm">
                <button @click="tab = 'indoor'" :class="tab === 'indoor' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-3 border-b-2 font-medium">
                    Indoor ({{ $indoorOrders->count() }})
                </button>
                <button @click="tab = 'outdoor'" :class="tab === 'outdoor' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-3 border-b-2 font-medium">
                    Outdoor ({{ $outdoorOrders->count() }})
                </button>
                <button @click="tab = 'artwork'" :class="tab === 'artwork' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                        class="px-4 py-3 border-b-2 font-medium">
                    Artwork ({{ $artworkOrders->count() }})
                </button>
            </div>

            @foreach (['indoor' => $indoorOrders, 'outdoor' => $outdoorOrders, 'artwork' => $artworkOrders] as $tabKey => $orders)
                <div x-show="tab === '{{ $tabKey }}'" @if($tabKey!=='indoor') x-cloak @endif class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[560px]">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3 w-12">No</th>
                                <th class="px-4 py-3">No Order</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Customer</th>
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
                                    <td class="px-4 py-3 text-right">
                                        <button type="button"
                                                @click="modalOpen = true; type = '{{ $tabKey }}'; id = {{ $order->id }}; noOrder = '{{ $order->NoOrder }}'"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                            Update Status
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Tidak ada order di antrian QC.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>

        <div x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="modalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                <form method="POST" :action="`/order-qc/${type}/${id}`" class="p-5 space-y-4">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Update Status — <span x-text="noOrder"></span></h3>

                    <div>
                        <x-input-label value="Status" />
                        <select name="action" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="selesai">Selesai</option>
                            <option value="lanjut">Lanjut</option>
                        </select>
                    </div>

                    <div>
                        <x-input-label value="Catatan (opsional)" />
                        <textarea name="catatan" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="modalOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
