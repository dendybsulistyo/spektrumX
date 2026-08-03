<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Antrian Cetak</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden"
         x-data="{ modalOpen: false, type: '', id: null, noOrder: '' }">
        <div x-data="{ tab: '{{ in_array(request('tab'), ['indoor', 'outdoor', 'artwork']) ? request('tab') : 'indoor' }}' }">
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

            @foreach (['indoor' => $indoorOrders, 'artwork' => $artworkOrders] as $tabKey => $orders)
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
                                            <x-order-rework :type="$tabKey" :order-id="$order->id" :no-order="$order->NoOrder"
                                                             current-stage="cetak"
                                                             :pending="$pendingRework->get($tabKey.'-'.$order->id)"
                                                             :can-approve="$canApproveRework" />
                                            @if (! $pendingRework->has($tabKey.'-'.$order->id))
                                                @can('order-cetak.manage')
                                                    <button type="button"
                                                            @click="modalOpen = true; type = '{{ $tabKey }}'; id = {{ $order->id }}; noOrder = '{{ $order->NoOrder }}'"
                                                            class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                                        Kirim Finishing
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Tidak ada order di antrian cetak.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach

            {{-- Outdoor: dikerjakan bertahap per item, qty diakumulasi sampai sesuai pesanan --}}
            <div x-show="tab === 'outdoor'" x-cloak class="overflow-x-auto">
                <table class="w-full text-[13px] min-w-[680px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-3 py-2 w-12">No</th>
                            <th class="px-3 py-2">No Order</th>
                            <th class="px-3 py-2">Printer</th>
                            <th class="px-3 py-2">Gabungan</th>
                            <th class="px-3 py-2">Qty</th>
                            <th class="px-3 py-2">Progress</th>
                            <th class="px-3 py-2">Customer</th>
                            <th class="px-3 py-2 text-right">Diskusi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @php $rowNum = 0; @endphp
                        @forelse ($outdoorOrders as $order)
                            @if ($order->cancel_requested_at)
                                @php $rowNum++; @endphp
                                <tr>
                                    <td class="px-3 py-2 text-gray-400">{{ $rowNum }}</td>
                                    <td class="px-3 py-2 font-semibold text-gray-900"><x-order-number :number="$order->NoOrder" /></td>
                                    <td class="px-3 py-2" colspan="4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800" title="{{ $order->cancel_reason }}">
                                            Menunggu Persetujuan Batal
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                             :comments="$outdoorComments->get($order->id, collect())"
                                                             :unread="$outdoorUnread->get($order->id, 0)" />
                                    </td>
                                </tr>
                            @else
                                @foreach ($order->items as $item)
                                    @php
                                        $rowNum++;
                                        $isLastPending = $order->items->where('id', '!=', $item->id)->every->isSelesai();
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 text-gray-400">{{ $rowNum }}</td>
                                        <td class="px-3 py-2 font-semibold text-gray-900"><x-order-number :number="$order->NoOrder" /></td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <x-printer-badge :code="$item->printerCode()" :name="$printerNames[$item->printerCode()] ?? null" />
                                        </td>
                                        <td class="px-3 py-2 text-gray-700 truncate" style="max-width: 140px;" title="{{ $item->gabungan }}">{{ $item->gabungan ?: '-' }}</td>
                                        <td class="px-3 py-2 text-gray-500 whitespace-nowrap">{{ $item->qty_diproses }}/{{ $item->Qty }}</td>
                                        <td class="px-3 py-2">
                                            @if ($item->isSelesai())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Selesai</span>
                                            @elsecan('order-cetak.manage')
                                                <form method="POST" action="{{ route('order-cetak.progress', $item) }}" class="flex items-center gap-1"
                                                      x-data="{ qty: '' }"
                                                      @submit="if ({{ $isLastPending ? 'true' : 'false' }} && Number(qty) >= {{ $item->sisaQty() }} && !confirm('Order {{ $order->NoOrder }} akan tuntas dan pindah ke antrian Finishing. Yakin?')) { $event.preventDefault(); }">
                                                    @csrf
                                                    <input type="number" name="qty" x-model="qty" min="1" max="{{ $item->sisaQty() }}" placeholder="qty" required
                                                           oninput="this.setCustomValidity('')"
                                                           oninvalid="this.setCustomValidity(this.validity.valueMissing ? 'Isi jumlah qty dulu.' : (this.validity.rangeOverflow ? 'Maksimal {{ $item->sisaQty() }} (sisa qty pesanan).' : (this.validity.rangeUnderflow ? 'Qty minimal 1.' : 'Qty tidak valid.')))"
                                                           class="w-16 rounded-md border-gray-300 text-xs py-1 no-spinner">
                                                    <button type="submit" class="px-2 py-1 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                                        Tambah
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-gray-400">Belum selesai</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                 :comments="$outdoorComments->get($order->id, collect())"
                                                                 :unread="$outdoorUnread->get($order->id, 0)" />
                                            <x-order-rework type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                             current-stage="cetak"
                                                             :pending="$pendingRework->get('outdoor-'.$order->id)"
                                                             :can-approve="$canApproveRework" />
                                        </td>
                                    </tr>
                                @endforeach

                                @can('order-cetak.manage')
                                @if ($order->items->every->isSelesai() && ! $pendingRework->has('outdoor-'.$order->id))
                                    <tr>
                                        <td class="px-3 py-2" colspan="5"></td>
                                        <td class="px-3 py-2" colspan="2">
                                            <form method="POST" action="{{ route('order-cetak.finish-outdoor', $order) }}"
                                                  onsubmit="return confirm('Order {{ $order->NoOrder }} sudah tuntas, pindahkan ke antrian Finishing?')">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                                    Kirim Finishing
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
                                @endcan
                            @endif
                        @empty
                            <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">Tidak ada order di antrian cetak.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
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

    </div>
</x-app-layout>
