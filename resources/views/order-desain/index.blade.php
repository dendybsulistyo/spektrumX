<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Antrian Desain</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden"
         x-data="{ modalOpen: false, type: '', id: null, noOrder: '', cancelModalOpen: false, cancelId: null, cancelNoOrder: '' }">
        <div x-data="{ tab: '{{ in_array(request('tab'), ['indoor', 'outdoor', 'artwork']) ? request('tab') : 'indoor' }}' }">
            <div class="flex border-b border-gray-200 text-sm gap-1 p-1.5">
                <button @click="tab = 'indoor'" :class="tab === 'indoor' ? 'bg-amber-100 text-amber-800' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-700'"
                        class="px-4 py-2 rounded-md font-medium transition">
                    Indoor ({{ $indoorOrders->count() }})
                </button>
                <button @click="tab = 'outdoor'" :class="tab === 'outdoor' ? 'bg-teal-100 text-teal-800' : 'text-gray-500 hover:bg-teal-50 hover:text-teal-700'"
                        class="px-4 py-2 rounded-md font-medium transition">
                    Outdoor ({{ $outdoorOrders->count() + $outdoorNeedsReply->count() }})
                </button>
                <button @click="tab = 'artwork'" :class="tab === 'artwork' ? 'bg-violet-100 text-violet-800' : 'text-gray-500 hover:bg-violet-50 hover:text-violet-700'"
                        class="px-4 py-2 rounded-md font-medium transition">
                    Artwork ({{ $artworkOrders->count() }})
                </button>
            </div>

            {{-- Indoor & Artwork: pecah per unit qty --}}
            @foreach (['indoor' => $indoorRows, 'artwork' => $artworkRows] as $tabKey => $rows)
                <div x-show="tab === '{{ $tabKey }}'" @if($tabKey!=='indoor') x-cloak @endif class="overflow-x-auto">
                    <table class="w-full text-[13px] min-w-[640px]">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-3 py-2 w-12">No</th>
                                <th class="px-3 py-2">No Order</th>
                                <th class="px-3 py-2">Item</th>
                                <th class="px-3 py-2 w-24">Qty</th>
                                <th class="px-3 py-2">Tanggal</th>
                                <th class="px-3 py-2">Customer</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 font-semibold text-gray-900"><x-order-number :number="$row->order->NoOrder" /></td>
                                    <td class="px-3 py-2 text-gray-600">
                                        {{ $row->itemName }}
                                        @if ($row->size)
                                            <span class="text-xs text-gray-400">({{ $row->size }})</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-500">
                                        @if ($row->unitIndex === null)
                                            Qty {{ $row->unitTotal }}
                                        @else
                                            {{ $row->unitIndex }}/{{ $row->unitTotal }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-gray-600">{{ is_string($row->order->TglOrder) ? $row->order->TglOrder : $row->order->TglOrder?->format('Y-m-d') }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $row->order->customer?->NmCust ?? '-' }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <button type="button"
                                                @click="modalOpen = true; type = '{{ $tabKey }}'; id = {{ $row->order->id }}; noOrder = '{{ $row->order->NoOrder }}'"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                            Update Status
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">Tidak ada order di antrian desain.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endforeach

            {{-- Outdoor: tampilan biasa 1 baris per order --}}
            <div x-show="tab === 'outdoor'" x-cloak class="overflow-x-auto">
                <table class="w-full text-[13px] min-w-[560px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-2 py-1.5 w-10">No</th>
                            <th class="px-2 py-1.5">No Order</th>
                            <th class="px-2 py-1.5">Customer</th>
                            <th class="px-2 py-1.5">Penerima</th>
                            <th class="px-2 py-1.5">Printer</th>
                            <th class="px-2 py-1.5">Bahan</th>
                            <th class="px-2 py-1.5">Ukuran</th>
                            <th class="px-2 py-1.5">Qty</th>
                            <th class="px-2 py-1.5">File</th>
                            <th class="px-2 py-1.5">Gabungan</th>
                            <th class="px-2 py-1.5">TGL</th>
                            <th class="px-2 py-1.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @php $rowNum = 0; @endphp
                        @forelse ($outdoorOrders as $order)
                            @foreach ($order->items as $item)
                                @php $rowNum++; @endphp
                                <tr>
                                    <td class="px-2 py-1.5 text-gray-400">{{ $rowNum }}</td>
                                    <td class="px-2 py-1.5 font-semibold text-gray-900"><x-order-number :number="$order->NoOrder" /></td>
                                    <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 90px;" title="{{ $order->customer?->NmCust }}">{{ $order->customer?->NmCust ?? '-' }}</td>
                                    <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 80px;" title="{{ $order->createdBy?->name }}">{{ $order->createdBy?->name ?? '-' }}</td>
                                    <td class="px-2 py-1.5 text-gray-500 whitespace-nowrap">{{ $printerNames[$item->printerCode()] ?? '-' }}</td>
                                    <td class="px-2 py-1.5 text-gray-500 whitespace-nowrap">{{ $bahanNames[$item->bahanCode()] ?? '-' }}</td>
                                    <td class="px-2 py-1.5 text-gray-500 whitespace-nowrap">
                                        @if ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                                            {{ rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.') }} x {{ rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-2 py-1.5 text-gray-500">{{ $item->Qty }}</td>
                                    <td class="px-2 py-1.5 text-gray-500 whitespace-nowrap">{{ $item->NmFile ?: '-' }}</td>
                                    <td class="px-2 py-1.5 text-gray-500">
                                        @can('order-desain.manage')
                                            <form method="POST" action="{{ route('order-desain.gabungan', $item) }}">
                                                @csrf
                                                <input type="text" name="gabungan" value="{{ $item->gabungan }}" maxlength="255"
                                                       placeholder="-" onchange="this.form.submit()"
                                                       class="w-20 rounded-md border-gray-300 text-xs py-1 px-1.5">
                                            </form>
                                        @else
                                            <span class="whitespace-nowrap">{{ $item->gabungan ?: '-' }}</span>
                                        @endcan
                                    </td>
                                    <td class="px-2 py-1.5 text-gray-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($order->TglOrder)->format('d-m-y') }}</td>
                                    <td class="px-2 py-1.5 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                 :comments="$outdoorComments->get($order->id, collect())"
                                                                 :unread="$outdoorUnread->get($order->id, 0)" />
                                            @if ($order->cancel_requested_at)
                                                <div class="inline-flex flex-col items-end gap-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800" title="{{ $order->cancel_reason }}">
                                                        Menunggu Persetujuan Pembatalan
                                                    </span>
                                                    <span class="text-xs text-gray-400">
                                                        diajukan {{ $order->cancelRequestedBy?->name ?? '-' }}
                                                    </span>
                                                    @can('order-outdoor.approve-cancel')
                                                        <div class="flex flex-col items-end gap-1 mt-1">
                                                            <form method="POST" action="{{ route('order-outdoor.approve-cancel', $order) }}"
                                                                  onsubmit="return confirm('Setujui pembatalan order {{ $order->NoOrder }} dengan nota pengganti? Nota lama akan dihanguskan.')">
                                                                @csrf
                                                                <input type="hidden" name="resolution" value="nota_pengganti">
                                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700">
                                                                    Setujui + Nota Pengganti
                                                                </button>
                                                            </form>
                                                            <form method="POST" action="{{ route('order-outdoor.approve-cancel', $order) }}"
                                                                  onsubmit="return confirm('Setujui pembatalan TOTAL order {{ $order->NoOrder }}? Tidak akan ada nota pengganti.')">
                                                                @csrf
                                                                <input type="hidden" name="resolution" value="batal_total">
                                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700">
                                                                    Setujui Batal Total
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
                                                @can('order-desain.manage')
                                                    <form method="POST" action="{{ route('order-desain.update', ['outdoor', $order->id]) }}"
                                                          onsubmit="return confirm('Yakin ingin mengirim order {{ $order->NoOrder }} ke Cetak?')" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="action" value="selesai">
                                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                                            Kirim Cetak
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            @click="cancelModalOpen = true; cancelId = {{ $order->id }}; cancelNoOrder = '{{ $order->NoOrder }}'"
                                                            class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700">
                                                        Batal
                                                    </button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                        @endforelse

                        {{-- Order yang sudah lewat tahap desain, tapi Status Cetak baru saja
                             membalas diskusinya — muncul lagi supaya desain bisa membalas. --}}
                        @foreach ($outdoorNeedsReply as $order)
                            @foreach ($order->items as $item)
                                @php $rowNum++; @endphp
                                <tr class="bg-amber-50/60">
                                    <td class="px-2 py-1.5 text-gray-400">{{ $rowNum }}</td>
                                    <td class="px-2 py-1.5 font-semibold text-gray-900"><x-order-number :number="$order->NoOrder" /></td>
                                    <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 90px;" title="{{ $order->customer?->NmCust }}">{{ $order->customer?->NmCust ?? '-' }}</td>
                                    <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 80px;" title="{{ $order->createdBy?->name }}">{{ $order->createdBy?->name ?? '-' }}</td>
                                    <td class="px-2 py-1.5 text-gray-500 whitespace-nowrap">{{ $printerNames[$item->printerCode()] ?? '-' }}</td>
                                    <td class="px-2 py-1.5 text-gray-500 whitespace-nowrap">{{ $bahanNames[$item->bahanCode()] ?? '-' }}</td>
                                    <td class="px-2 py-1.5 text-gray-500 whitespace-nowrap">
                                        @if ((float) $item->Panjang > 0 && (float) $item->Lebar > 0)
                                            {{ rtrim(rtrim(number_format((float) $item->Panjang, 2), '0'), '.') }} x {{ rtrim(rtrim(number_format((float) $item->Lebar, 2), '0'), '.') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-2 py-1.5 text-gray-500">{{ $item->Qty }}</td>
                                    <td class="px-2 py-1.5 text-gray-500 whitespace-nowrap">{{ $item->NmFile ?: '-' }}</td>
                                    <td class="px-2 py-1.5 text-gray-500">
                                        @can('order-desain.manage')
                                            <form method="POST" action="{{ route('order-desain.gabungan', $item) }}">
                                                @csrf
                                                <input type="text" name="gabungan" value="{{ $item->gabungan }}" maxlength="255"
                                                       placeholder="-" onchange="this.form.submit()"
                                                       class="w-20 rounded-md border-gray-300 text-xs py-1 px-1.5">
                                            </form>
                                        @else
                                            <span class="whitespace-nowrap">{{ $item->gabungan ?: '-' }}</span>
                                        @endcan
                                    </td>
                                    <td class="px-2 py-1.5 text-gray-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($order->TglOrder)->format('d-m-y') }}</td>
                                    <td class="px-2 py-1.5 text-right">
                                        <div class="inline-flex flex-col items-end gap-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                Balasan baru &middot; status: {{ ucfirst(str_replace('_', ' ', $order->status ?? '-')) }}
                                            </span>
                                            <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                                 :comments="$outdoorComments->get($order->id, collect())"
                                                                 :unread="$outdoorUnread->get($order->id, 0)" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach

                        @if ($outdoorOrders->isEmpty() && $outdoorNeedsReply->isEmpty())
                            <tr><td colspan="12" class="px-4 py-6 text-center text-gray-400">Tidak ada order di antrian desain.</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="modalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                <form method="POST" :action="`/order-desain/${type}/${id}`" class="p-5 space-y-4">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Update Status — <span x-text="noOrder"></span></h3>

                    <div>
                        <x-input-label value="Status" />
                        <select name="action" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="selesai">Selesai</option>
                            <option value="lanjut">Lanjut</option>
                        </select>
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
