<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Kasir — Antrian Pembayaran</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden"
         x-data="{
            tab: '{{ $initialTab }}',
            lunasiModalOpen: false, lunasiId: null, lunasiNoOrder: '', lunasiSisa: '', lunasiSisaRaw: 0,
            lunasiRincian: [{ cara_bayar: 'tunai', jumlah: '', no_referensi: '' }],
            get lunasiRincianTotal() { return this.lunasiRincian.reduce((sum, r) => sum + Number(r.jumlah || 0), 0); },
            get lunasiRincianDiff() { return Math.round((this.lunasiSisaRaw - this.lunasiRincianTotal) * 100) / 100; },
            get lunasiRincianError() {
                if (this.lunasiRincianDiff === 0) return '';
                return this.lunasiRincianDiff > 0
                    ? `Kurang Rp ${this.lunasiRincianDiff.toLocaleString('id-ID')}.`
                    : `Lebih Rp ${Math.abs(this.lunasiRincianDiff).toLocaleString('id-ID')}.`;
            },
            addLunasiRincian() { this.lunasiRincian.push({ cara_bayar: 'tunai', jumlah: '', no_referensi: '' }); },
            removeLunasiRincian(i) { this.lunasiRincian.splice(i, 1); },
            cancelModalOpen: false, cancelType: '', cancelId: null, cancelNoOrder: '',
            batalOrderModalOpen: false, batalOrderType: '', batalOrderId: null, batalOrderNoOrder: '',
         }">
        <div class="flex border-b border-gray-200 text-sm gap-1 p-1.5 flex-wrap">
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
            @can('kasir.replacement.manage')
                <button @click="tab = 'replacement'" :class="tab === 'replacement' ? 'bg-rose-100 text-rose-800' : 'text-gray-500 hover:bg-rose-50 hover:text-rose-700'"
                        class="px-4 py-2 rounded-md font-medium transition">
                    Nota Pengganti ({{ $replacementOrders->count() }})
                </button>
            @endcan
            <button @click="tab = 'dp'" :class="tab === 'dp' ? 'bg-sky-100 text-sky-800' : 'text-gray-500 hover:bg-sky-50 hover:text-sky-700'"
                    class="px-4 py-2 rounded-md font-medium transition">
                DP Belum Lunas ({{ $dpOrders->count() }})
            </button>
            <button @click="tab = 'lunas'" :class="tab === 'lunas' ? 'bg-green-100 text-green-800' : 'text-gray-500 hover:bg-green-50 hover:text-green-700'"
                    class="px-4 py-2 rounded-md font-medium transition">
                Sudah Bayar ({{ $lunasOrders->count() }})
            </button>
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
                        <tr>
                            <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-900">
                                {{ $order->NoOrder }}
                                @if ($order->diskonStatus() === 'pending')
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Diskon pending</span>
                                @elseif ($order->diskonStatus() === 'approved')
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Diskon {{ $order->diskonApprovedLabel() }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->TglOrder }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
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
                        <tr>
                            <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-900">
                                {{ $order->NoOrder }}
                                @if ($order->diskonStatus() === 'pending')
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Diskon pending</span>
                                @elseif ($order->diskonStatus() === 'approved')
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Diskon {{ $order->diskonApprovedLabel() }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->TglOrder?->format('Y-m-d') }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
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

        <div x-show="tab === 'artwork'" x-cloak class="overflow-x-auto">
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
                    @forelse ($artworkOrders as $order)
                        <tr>
                            <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-900">
                                {{ $order->NoOrder }}
                                @if ($order->diskonStatus() === 'pending')
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Diskon pending</span>
                                @elseif ($order->diskonStatus() === 'approved')
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Diskon {{ $order->diskonApprovedLabel() }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('kasir.show', ['type' => 'artwork', 'id' => $order->id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                    Bayar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Tidak ada order artwork yang menunggu pembayaran.</td></tr>
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

        <div x-show="tab === 'dp'" x-cloak class="overflow-x-auto">
            <table class="w-full text-[13px] min-w-[720px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2 w-12">No</th>
                        <th class="px-3 py-2">No Order</th>
                        <th class="px-3 py-2">Tanggal</th>
                        <th class="px-3 py-2">Customer</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Sudah Dibayar</th>
                        <th class="px-3 py-2 text-right">Sisa Piutang</th>
                        <th class="px-3 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($dpOrders as $order)
                        <tr>
                            <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-900">
                                {{ $order->NoOrder }}
                                @if ($order->diskonStatus() === 'pending')
                                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Diskon pending</span>
                                @elseif ($order->diskonStatus() === 'approved')
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Diskon {{ $order->diskonApprovedLabel() }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-green-700">Rp {{ number_format($order->jumlah_dibayar ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-amber-700 font-semibold">Rp {{ number_format($order->jumlah_piutang ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                <button type="button"
                                        @click="lunasiModalOpen = true; lunasiId = {{ $order->id }}; lunasiNoOrder = '{{ $order->NoOrder }}'; lunasiSisa = '{{ number_format($order->jumlah_piutang ?? 0, 0, ',', '.') }}'; lunasiSisaRaw = {{ (float) ($order->jumlah_piutang ?? 0) }}; lunasiRincian = [{ cara_bayar: 'tunai', jumlah: '', no_referensi: '' }]"
                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-md hover:bg-green-700">
                                    Lunasi
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">Tidak ada order outdoor dengan sisa DP.</td></tr>
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

        <div x-show="lunasiModalOpen" x-cloak @keydown.escape.window="lunasiModalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="lunasiModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                <form method="POST" :action="`/kasir/outdoor/${lunasiId}/lunasi`" class="p-5 space-y-4"
                      @submit="if (lunasiRincianError) { $event.preventDefault(); }">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Pelunasan DP — <span x-text="lunasiNoOrder"></span></h3>
                    <p class="text-sm text-gray-600">Sisa piutang: <span class="font-semibold" x-text="`Rp ${lunasiSisa}`"></span></p>

                    <div>
                        <x-input-label value="Rincian Pembayaran" />
                        <p class="text-xs text-gray-500 mt-0.5">Bisa dibagi ke beberapa metode sekaligus.</p>

                        <div class="mt-2 space-y-2">
                            <template x-for="(row, idx) in lunasiRincian" :key="idx">
                                <div class="flex items-start gap-2 rounded-md border border-gray-200 p-2">
                                    <div class="flex-1 space-y-1.5">
                                        <div class="flex gap-2">
                                            <select :name="`rincian[${idx}][cara_bayar]`" x-model="row.cara_bayar"
                                                    class="rounded-md border-gray-300 text-sm py-1.5 w-28">
                                                <option value="tunai">Tunai</option>
                                                <option value="qris">QRIS</option>
                                                <option value="transfer">Transfer</option>
                                            </select>
                                            <input type="text" inputmode="numeric"
                                                   :value="row.jumlah ? Number(row.jumlah).toLocaleString('id-ID') : ''"
                                                   @input="row.jumlah = $event.target.value.replace(/\D/g, '')"
                                                   placeholder="Jumlah" class="flex-1 rounded-md border-gray-300 text-sm py-1.5">
                                            <input type="hidden" :name="`rincian[${idx}][jumlah]`" :value="row.jumlah">
                                            <button type="button" x-show="lunasiRincian.length > 1" @click="removeLunasiRincian(idx)"
                                                    class="text-gray-400 hover:text-red-600 px-1">&times;</button>
                                        </div>
                                        <input type="text" x-show="row.cara_bayar !== 'tunai'" x-cloak
                                               :name="`rincian[${idx}][no_referensi]`" x-model="row.no_referensi"
                                               maxlength="50" placeholder="No. referensi QRIS/transfer"
                                               class="w-full rounded-md border-gray-300 text-xs py-1.5">
                                    </div>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addLunasiRincian()" class="mt-2 text-xs font-semibold text-blue-600 hover:underline">
                            + Tambah metode pembayaran
                        </button>

                        <p class="text-xs mt-2" :class="lunasiRincianError ? 'text-red-600 font-semibold' : 'text-gray-400'">
                            Total rincian: <span x-text="lunasiRincianTotal.toLocaleString('id-ID')"></span>
                            / <span x-text="lunasiSisaRaw.toLocaleString('id-ID')"></span>
                            <span x-show="lunasiRincianError" x-text="'— ' + lunasiRincianError"></span>
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="lunasiModalOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">Konfirmasi Lunasi</button>
                    </div>
                </form>
            </div>
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
