<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Kasir — Antrian Pembayaran</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden"
         x-data="{ tab: 'indoor', lunasiModalOpen: false, lunasiId: null, lunasiNoOrder: '', lunasiSisa: '', lunasiCaraBayar: 'tunai', lunasiNoReferensi: '' }">
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
            <button @click="tab = 'replacement'" :class="tab === 'replacement' ? 'bg-rose-100 text-rose-800' : 'text-gray-500 hover:bg-rose-50 hover:text-rose-700'"
                    class="px-4 py-2 rounded-md font-medium transition">
                Nota Pengganti ({{ $replacementOrders->count() }})
            </button>
            <button @click="tab = 'dp'" :class="tab === 'dp' ? 'bg-sky-100 text-sky-800' : 'text-gray-500 hover:bg-sky-50 hover:text-sky-700'"
                    class="px-4 py-2 rounded-md font-medium transition">
                DP Belum Lunas ({{ $dpOrders->count() }})
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
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Diskon {{ rtrim(rtrim(number_format($order->diskon_persen, 2), '0'), '.') }}%</span>
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
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Diskon {{ rtrim(rtrim(number_format($order->diskon_persen, 2), '0'), '.') }}%</span>
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
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Diskon {{ rtrim(rtrim(number_format($order->diskon_persen, 2), '0'), '.') }}%</span>
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

        <div x-show="tab === 'replacement'" x-cloak class="overflow-x-auto">
            <table class="w-full text-[13px] min-w-[720px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr>
                    <th class="px-3 py-2">Nota Hangus</th><th class="px-3 py-2">Customer</th><th class="px-3 py-2 text-right">Dana Dibayar Lama</th><th class="px-3 py-2 text-right">Aksi</th>
                </tr></thead>
                <tbody class="divide-y">
                    @forelse ($replacementOrders as $order)
                        <tr>
                            <td class="px-3 py-2"><span class="font-semibold text-gray-900">{{ $order->NoOrder }}</span><span class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Hangus</span><p class="mt-1 text-xs text-gray-500">{{ $order->cancel_reason }}</p></td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->jumlah_dibayar ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right"><a href="{{ route('kasir.replacement.create', $order) }}" class="inline-flex items-center rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">Buat Nota Pengganti</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Tidak ada nota hangus yang menunggu penggantian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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
                                    <span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Diskon {{ rtrim(rtrim(number_format($order->diskon_persen, 2), '0'), '.') }}%</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-green-700">Rp {{ number_format($order->jumlah_dibayar ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right text-amber-700 font-semibold">Rp {{ number_format($order->jumlah_piutang ?? 0, 0, ',', '.') }}</td>
                            <td class="px-3 py-2 text-right">
                                <button type="button"
                                        @click="lunasiModalOpen = true; lunasiId = {{ $order->id }}; lunasiNoOrder = '{{ $order->NoOrder }}'; lunasiSisa = '{{ number_format($order->jumlah_piutang ?? 0, 0, ',', '.') }}'; lunasiCaraBayar = 'tunai'; lunasiNoReferensi = ''"
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

        <div x-show="lunasiModalOpen" x-cloak @keydown.escape.window="lunasiModalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="lunasiModalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-sm">
                <form method="POST" :action="`/kasir/outdoor/${lunasiId}/lunasi`" class="p-5 space-y-4">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Pelunasan DP — <span x-text="lunasiNoOrder"></span></h3>
                    <p class="text-sm text-gray-600">Sisa piutang: <span class="font-semibold" x-text="`Rp ${lunasiSisa}`"></span></p>

                    <div>
                        <x-input-label value="Cara Bayar" />
                        <div class="mt-2 space-y-2">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="cara_bayar" value="tunai" x-model="lunasiCaraBayar" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">Tunai</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="cara_bayar" value="qris" x-model="lunasiCaraBayar" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">QRIS</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="cara_bayar" value="transfer" x-model="lunasiCaraBayar" class="text-gray-900 focus:ring-gray-900">
                                <span class="text-sm text-gray-700">Transfer</span>
                            </label>
                        </div>
                    </div>

                    <div x-show="lunasiCaraBayar !== 'tunai'" x-cloak>
                        <x-input-label value="No. Referensi" />
                        <input type="text" name="no_referensi" x-model="lunasiNoReferensi" maxlength="50"
                               placeholder="ID transaksi QRIS / 4 digit terakhir rekening tujuan"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="lunasiModalOpen = false" class="px-3 py-2 text-sm text-gray-500 hover:underline">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">Konfirmasi Lunasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
