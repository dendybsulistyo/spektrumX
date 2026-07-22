<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Antrian Cetak</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden"
         x-data="{ modalOpen: false, type: '', id: null, noOrder: '', cancelModalOpen: false, cancelId: null, cancelNoOrder: '' }">
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
                                        @if ($tabKey === 'outdoor' && $order->cancel_requested_at)
                                            <div class="inline-flex flex-col items-end gap-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800" title="{{ $order->cancel_reason }}">
                                                    Menunggu Persetujuan Pembatalan
                                                </span>
                                                <span class="text-xs text-gray-400">
                                                    diajukan {{ $order->cancelRequestedBy?->name ?? '-' }}
                                                </span>
                                                @can('order-outdoor.approve-cancel')
                                                    <div class="flex gap-1 mt-1">
                                                        <form method="POST" action="{{ route('order-outdoor.approve-cancel', $order) }}"
                                                              onsubmit="return confirm('Setujui pembatalan order {{ $order->NoOrder }}? Order akan ditandai batal.')">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700">
                                                                Setujui
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('order-outdoor.reject-cancel', $order) }}"
                                                              onsubmit="return confirm('Tolak pengajuan pembatalan order {{ $order->NoOrder }}?')">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-gray-200 text-gray-700 text-xs font-semibold rounded-md hover:bg-gray-300">
                                                                Tolak
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endcan
                                            </div>
                                        @else
                                            <button type="button"
                                                    @click="modalOpen = true; type = '{{ $tabKey }}'; id = {{ $order->id }}; noOrder = '{{ $order->NoOrder }}'"
                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                                Update Status
                                            </button>
                                            @if ($tabKey === 'outdoor')
                                                @can('order-cetak.manage')
                                                    <button type="button"
                                                            @click="cancelModalOpen = true; cancelId = {{ $order->id }}; cancelNoOrder = '{{ $order->NoOrder }}'"
                                                            class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-xs font-semibold rounded-md hover:bg-red-100 ml-1">
                                                        Ajukan Pembatalan
                                                    </button>
                                                @endcan
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Tidak ada order di antrian cetak.</td></tr>
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
                <form method="POST" :action="`/order-cetak/${type}/${id}`" class="p-5 space-y-4">
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

        <div x-show="cancelModalOpen" x-cloak @keydown.escape.window="cancelModalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="cancelModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                <form method="POST" :action="`/order-outdoor/${cancelId}/request-cancel`" class="p-5 space-y-4">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Ajukan Pembatalan — <span x-text="cancelNoOrder"></span></h3>
                    <p class="text-xs text-gray-500">Order akan ditandai menunggu persetujuan. Perlu disetujui Admin/Admin Kasir sebelum benar-benar dibatalkan.</p>

                    <div>
                        <x-input-label value="Alasan Pembatalan" />
                        <textarea name="cancel_reason" rows="3" required maxlength="255"
                                  class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                  placeholder="misal: customer minta batal, salah spesifikasi, dll"></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="cancelModalOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">Ajukan Pembatalan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
