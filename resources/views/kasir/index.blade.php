<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Operator Kasir </h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden"
         x-data="{
            tab: '{{ $initialTab }}',
            onlyCs: false,
            cancelModalOpen: false, cancelType: '', cancelId: null, cancelNoOrder: '',
            batalOrderModalOpen: false, batalOrderType: '', batalOrderId: null, batalOrderNoOrder: '',
            setTab(key) {
                this.tab = key;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', key);
                window.history.replaceState({}, '', url);
            },
         }">
        <div class="flex border-b border-gray-200 text-sm gap-1 p-1.5 flex-wrap">
            <button @click="setTab('indoor')" :class="tab === 'indoor' ? 'bg-amber-100 text-amber-800' : 'text-gray-500 hover:bg-amber-50 hover:text-amber-700'"
                    class="px-4 py-2 rounded-md font-medium transition">
                Indoor ({{ $indoorOrders->count() }})
            </button>
            <button @click="setTab('outdoor')" :class="tab === 'outdoor' ? 'bg-teal-100 text-teal-800' : 'text-gray-500 hover:bg-teal-50 hover:text-teal-700'"
                    class="px-4 py-2 rounded-md font-medium transition">
                Outdoor ({{ $outdoorOrders->count() }})
            </button>
            @can('kasir.replacement.manage')
                <button @click="setTab('replacement')" :class="tab === 'replacement' ? 'bg-rose-100 text-rose-800' : 'text-gray-500 hover:bg-rose-50 hover:text-rose-700'"
                        class="px-4 py-2 rounded-md font-medium transition">
                    Nota Pengganti ({{ $replacementOrders->count() }})
                </button>
            @endcan
            <button @click="setTab('lunas')" :class="tab === 'lunas' ? 'bg-green-100 text-green-800' : 'text-gray-500 hover:bg-green-50 hover:text-green-700'"
                    class="px-4 py-2 rounded-md font-medium transition">
                Sudah Bayar ({{ $lunasOrders->count() }})
            </button>
            <button @click="setTab('batal')" :class="tab === 'batal' ? 'bg-red-100 text-red-800' : 'text-gray-500 hover:bg-red-50 hover:text-red-700'"
                    class="px-4 py-2 rounded-md font-medium transition">
                Batal ( Approved ) ({{ $cancelledOrders->count() }})
            </button>
            <button @click="setTab('otorisasi')" :class="tab === 'otorisasi' ? 'bg-indigo-100 text-indigo-800' : 'text-gray-500 hover:bg-indigo-50 hover:text-indigo-700'"
                    class="px-4 py-2 rounded-md font-medium transition">
                Otorisasi ({{ $authorizations->count() }})
            </button>
            {{-- Saring order Indoor/Outdoor yang diteruskan dari Customer Service. --}}
            <label x-show="tab === 'indoor' || tab === 'outdoor'" class="ml-auto inline-flex items-center gap-2 px-3 text-sm text-gray-600 cursor-pointer select-none">
                <input type="checkbox" x-model="onlyCs" class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                Hanya <span class="rounded bg-violet-100 px-2 py-0.5 text-[11px] font-semibold text-violet-700">Dari CS</span>
                ({{ $indoorOrders->whereNotNull('cs_processed_at')->count() + $outdoorOrders->whereNotNull('cs_processed_at')->count() }})
            </label>
        </div>

        <div x-show="tab === 'indoor'" class="overflow-x-auto">
            <table class="w-full text-[13px] min-w-[640px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2 w-12">No</th>
                        <th class="px-3 py-2">No Order</th>
                        <th class="px-3 py-2">Tanggal</th>
                        <th class="px-3 py-2">Customer</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($indoorOrders as $order)
                        <tr x-show="!onlyCs || {{ $order->cs_processed_at ? 'true' : 'false' }}">
                            <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-900">
                                {{ $order->NoOrder }}
                                @if ($order->cs_processed_at)
                                    <span class="ml-1 rounded bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-700">Dari CS · {{ $order->customerService?->name ? ucwords(mb_strtolower($order->customerService->name)) : '-' }}</span>
                                @endif
                                @if ($order->diskonStatus() === 'pending')
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Diskon pending</span>
                                @elseif ($order->diskonStatus() === 'approved')
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Diskon {{ $order->diskonApprovedLabel() }}</span>
                                @endif
                                @if ($order->customer?->isVip)
                                    @if ($order->withinHutangPlafon())
                                        <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700" title="VIP dalam plafon hutang">VIP</span>
                                    @else
                                        <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700" title="VIP melebihi plafon hutang">VIP</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $order->TglOrder?->format('Y-m-d') }} <span class="text-gray-400">{{ $order->created_at?->format('H:i') }}</span></td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                <x-order-discussion type="indoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                     :comments="$orderComments->get('indoor-'.$order->id, collect())"
                                                     :unread="$orderUnread->get('indoor-'.$order->id, 0)" />
                                <a href="{{ route('kasir.show', ['type' => 'indoor', 'id' => $order->id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                    Bayar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Tidak ada order indoor yang menunggu pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'outdoor'" x-cloak class="overflow-x-auto">
            <table class="w-full text-[13px] min-w-[640px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2 w-12">No</th>
                        <th class="px-3 py-2">No Order</th>
                        <th class="px-3 py-2">Tanggal</th>
                        <th class="px-3 py-2">Customer</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($outdoorOrders as $order)
                        <tr x-show="!onlyCs || {{ $order->cs_processed_at ? 'true' : 'false' }}">
                            <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-900">
                                {{ $order->NoOrder }}
                                @if ($order->cs_processed_at)
                                    <span class="ml-1 rounded bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-700">Dari CS · {{ $order->customerService?->name ? ucwords(mb_strtolower($order->customerService->name)) : '-' }}</span>
                                @endif
                                @if ($order->diskonStatus() === 'pending')
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Diskon pending</span>
                                @elseif ($order->diskonStatus() === 'approved')
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Diskon {{ $order->diskonApprovedLabel() }}</span>
                                @endif
                                @if ($order->customer?->isVip)
                                    @if ($order->withinHutangPlafon())
                                        <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700" title="VIP dalam plafon hutang">VIP</span>
                                    @else
                                        <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700" title="VIP melebihi plafon hutang">VIP</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $order->TglOrder?->format('Y-m-d') }} <span class="text-gray-400">{{ $order->created_at?->format('H:i') }}</span></td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                     :comments="$orderComments->get('outdoor-'.$order->id, collect())"
                                                     :unread="$orderUnread->get('outdoor-'.$order->id, 0)" />
                                <a href="{{ route('kasir.show', ['type' => 'outdoor', 'id' => $order->id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                    Bayar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Tidak ada order outdoor yang menunggu pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @can('kasir.replacement.manage')
            <div x-show="tab === 'replacement'" x-cloak class="overflow-x-auto">
                <table class="w-full text-[13px] min-w-[720px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr>
                        <th class="px-3 py-2">Nota Hangus</th><th class="px-3 py-2">Tipe</th><th class="px-3 py-2">Customer</th><th class="px-3 py-2 text-right">Dana Dibayar Lama</th><th class="px-3 py-2 text-right">Aksi</th>
                    </tr></thead>
                    <tbody class="divide-y">
                        @forelse ($replacementOrders as $order)
                            @php
                                $replacementRoute = $order->order_type === 'outdoor'
                                    ? route('kasir.replacement.create', $order)
                                    : route('kasir.replacement.create.' . $order->order_type, $order);
                            @endphp
                            <tr>
                                <td class="px-3 py-2"><span class="font-semibold text-gray-900">{{ $order->NoOrder }}</span><span class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Hangus</span><p class="mt-1 text-xs text-gray-500">{{ $order->cancel_reason }}</p></td>
                                <td class="px-3 py-2 text-gray-600">{{ ucfirst($order->order_type) }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                                <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->jumlah_dibayar ?? 0, 0, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right"><a href="{{ $replacementRoute }}" class="inline-flex items-center rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">Buat Nota Pengganti</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">Tidak ada nota hangus yang menunggu penggantian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endcan

        <div x-show="tab === 'batal'" x-cloak class="overflow-x-auto">
            <p class="px-3 pt-3 text-xs text-gray-500">Order batal yang sudah disetujui dalam 30 hari terakhir.</p>
            <table class="w-full text-[13px] min-w-[900px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr>
                    <th class="px-3 py-2 text-center">No</th><th class="px-3 py-2">Disetujui</th><th class="px-3 py-2">No. Order</th><th class="px-3 py-2">Tipe</th>
                    <th class="px-3 py-2">Customer</th><th class="px-3 py-2 text-right">Total</th><th class="px-3 py-2 text-right">Sudah Dibayar</th>
                    <th class="px-3 py-2">Alasan</th><th class="px-3 py-2">Diajukan / Disetujui</th>
                </tr></thead>
                <tbody class="divide-y">
                    @forelse ($cancelledOrders as $order)
                        <tr>
                            <td class="px-3 py-2 text-center text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-600">{{ $order->cancel_approved_at?->format('d-m-Y H:i') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap"><span class="font-semibold text-gray-900">{{ $order->NoOrder }}</span><span class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Batal</span></td>
                            <td class="px-3 py-2 text-gray-600">{{ ucfirst($order->order_type) }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap text-gray-900">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap text-gray-900">Rp {{ number_format((float) ($order->jumlah_dibayar ?? 0), 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->cancel_reason ?: '-' }}</td>
                            <td class="px-3 py-2 text-xs text-gray-600">
                                <div>Diajukan: {{ $order->cancelRequestedBy?->name ?? '-' }}</div>
                                <div>Disetujui: {{ $order->cancelApprovedBy?->name ?? '-' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-6 text-center text-gray-400">Tidak ada order batal yang disetujui dalam 30 hari terakhir.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'otorisasi'" x-cloak class="overflow-x-auto">
            <p class="px-3 pt-3 text-xs text-gray-500">Hasil otorisasi diskon nota dan hutang VIP dalam 30 hari terakhir, disetujui maupun ditolak.</p>
            <table class="w-full text-[13px] min-w-[1000px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr>
                    <th class="px-3 py-2 text-center">No</th><th class="px-3 py-2">Diputuskan</th><th class="px-3 py-2">No. Order</th><th class="px-3 py-2">Customer</th>
                    <th class="px-3 py-2">Otorisasi</th><th class="px-3 py-2 text-right">Nilai</th><th class="px-3 py-2">Hasil</th>
                    <th class="px-3 py-2">Catatan</th><th class="px-3 py-2">Diajukan / Diputuskan</th>
                </tr></thead>
                <tbody class="divide-y">
                    @forelse ($authorizations as $auth)
                        <tr>
                            <td class="px-3 py-2 text-center text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-600">{{ $auth->decided_at->format('d-m-Y H:i') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap"><span class="font-semibold text-gray-900">{{ $auth->order->NoOrder }}</span> <span class="text-xs text-gray-400">{{ ucfirst($auth->type) }}</span></td>
                            <td class="px-3 py-2 text-gray-600">
                                {{ $auth->order->customer?->NmCust ? ucwords(mb_strtolower($auth->order->customer->NmCust)) : '-' }}
                                <span class="ml-1 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $auth->vip ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600' }}">{{ $auth->vip ? 'VIP' : 'Reguler' }}</span>
                            </td>
                            <td class="px-3 py-2 text-gray-700">{{ $auth->kind }}</td>
                            <td class="px-3 py-2 text-right whitespace-nowrap text-gray-900">{{ $auth->value }}</td>
                            <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $auth->approved ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $auth->approved ? 'Disetujui' : 'Ditolak' }}</span></td>
                            <td class="px-3 py-2 text-gray-600">{{ $auth->note ?: '-' }}</td>
                            <td class="px-3 py-2 text-xs text-gray-600">
                                <div>Diajukan: {{ $auth->requested_by ?? '-' }}</div>
                                <div>{{ $auth->approved ? 'Disetujui' : 'Ditolak' }}: {{ $auth->decided_by ?? '-' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-6 text-center text-gray-400">Belum ada hasil otorisasi dalam 30 hari terakhir.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'lunas'" x-cloak class="overflow-x-auto">
            @php
                $stageLabels = [
                    'baru' => 'Baru', 'dibayar' => 'Dibayar', 'desain' => 'Desain', 'cetak' => 'Cetak',
                    'finishing' => 'Finishing', 'qc' => 'QC', 'bungkus' => 'Bungkus', 'siap_diambil' => 'Siap Diambil',
                ];
            @endphp
            <table class="w-full text-[13px] min-w-[760px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2 w-12">No</th>
                        <th class="px-3 py-2">No Order</th>
                        <th class="px-3 py-2">Tipe</th>
                        <th class="px-3 py-2">Tanggal</th>
                        <th class="px-3 py-2">Customer</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($lunasOrders as $order)
                        <tr>
                            <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-900">
                                <a href="{{ route('kasir.show', ['type' => $order->order_type, 'id' => $order->id]) }}" class="hover:underline">
                                    {{ $order->NoOrder }}
                                </a>
                                @if ($order->cancel_requested_at)
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Menunggu batal</span>
                                @elseif ($pendingRework->has($order->order_type.'-'.$order->id))
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Menunggu ulang/batal</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ ucfirst($order->order_type) }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $stageLabels[$order->status] ?? ucfirst($order->status) }}</td>
                            <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                <x-order-discussion :type="$order->order_type" :order-id="$order->id" :no-order="$order->NoOrder"
                                                     :comments="$orderComments->get($order->order_type.'-'.$order->id, collect())"
                                                     :unread="$orderUnread->get($order->order_type.'-'.$order->id, 0)" />
                                @can('kasir.manage')
                                    @if (! $order->cancel_requested_at && ! $pendingRework->has($order->order_type.'-'.$order->id) && $order->status === 'desain')
                                        <div class="inline-flex gap-1.5">
                                            <button type="button"
                                                    @click="cancelModalOpen = true; cancelType = '{{ $order->order_type }}'; cancelId = {{ $order->id }}; cancelNoOrder = '{{ $order->NoOrder }}'"
                                                    class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700">
                                                Batal &amp; IB
                                            </button>
                                            <button type="button"
                                                    @click="batalOrderModalOpen = true; batalOrderType = '{{ $order->order_type }}'; batalOrderId = {{ $order->id }}; batalOrderNoOrder = '{{ $order->NoOrder }}'"
                                                    class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 border border-red-300 text-xs font-semibold rounded-md hover:bg-red-100">
                                                Batalkan Order
                                            </button>
                                        </div>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">Tidak ada order yang sudah bayar dan masih dalam produksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="cancelModalOpen" x-cloak @keydown.escape.window="cancelModalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="cancelModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                <form method="POST" :action="`/order-${cancelType}/${cancelId}/request-cancel`" class="p-5 space-y-4">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Ajukan pembatalan — <span x-text="cancelNoOrder"></span></h3>
                    <p class="text-xs text-gray-500">Order akan ditandai menunggu persetujuan. Perlu disetujui Admin/Admin Kasir dari Antrian Desain sebelum benar-benar dibatalkan.</p>

                    <div>
                        <x-input-label for="index_cancel_reason" value="Alasan pembatalan" />
                        <textarea id="index_cancel_reason" name="cancel_reason" rows="3" required maxlength="255"
                                  placeholder="misal: customer minta batal, salah spesifikasi, dll"
                                  class="mt-1 block w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="cancelModalOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">Ajukan pembatalan</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="batalOrderModalOpen" x-cloak @keydown.escape.window="batalOrderModalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="batalOrderModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                <form method="POST" :action="`/order-rework/${batalOrderType}/${batalOrderId}`" class="p-5 space-y-4">
                    @csrf
                    <input type="hidden" name="action" value="batal">
                    <h3 class="font-semibold text-gray-900">Batalkan Order — <span x-text="batalOrderNoOrder"></span></h3>
                    <p class="text-xs text-gray-500">Order akan dibatalkan total dan uang yang sudah dibayar dikembalikan ke customer. Perlu disetujui dulu di menu Approval.</p>

                    <div>
                        <x-input-label for="index_batal_order_reason" value="Alasan" />
                        <textarea id="index_batal_order_reason" name="reason" rows="3" required maxlength="255"
                                  placeholder="Jelaskan alasan pembatalan..."
                                  class="mt-1 block w-full rounded-md border-gray-300 text-sm"></textarea>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="batalOrderModalOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">Ajukan pembatalan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
