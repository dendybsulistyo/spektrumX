<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Kasir — Antrian Pembayaran</h2>
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
            <button @click="tab = 'artwork'" :class="tab === 'artwork' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                    class="px-4 py-3 border-b-2 font-medium">
                Artwork ({{ $artworkOrders->count() }})
            </button>
            <button @click="tab = 'replacement'" :class="tab === 'replacement' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                    class="px-4 py-3 border-b-2 font-medium">
                Nota Pengganti ({{ $replacementOrders->count() }})
            </button>
            <button @click="tab = 'dp'" :class="tab === 'dp' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500'"
                    class="px-4 py-3 border-b-2 font-medium">
                DP Belum Lunas ({{ $dpOrders->count() }})
            </button>
        </div>

        <div x-show="tab === 'indoor'" class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 w-12">No</th>
                        <th class="px-4 py-3">No Order</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($indoorOrders as $order)
                        <tr>
                            <td class="px-4 py-3 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $order->NoOrder }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->TglOrder }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->customer?->NmCust ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('kasir.show', ['type' => 'indoor', 'id' => $order->id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                    Bayar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada order indoor yang menunggu pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'outdoor'" x-cloak class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 w-12">No</th>
                        <th class="px-4 py-3">No Order</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($outdoorOrders as $order)
                        <tr>
                            <td class="px-4 py-3 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $order->NoOrder }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->TglOrder?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->customer?->NmCust ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('kasir.show', ['type' => 'outdoor', 'id' => $order->id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                    Bayar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada order outdoor yang menunggu pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'artwork'" x-cloak class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 w-12">No</th>
                        <th class="px-4 py-3">No Order</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($artworkOrders as $order)
                        <tr>
                            <td class="px-4 py-3 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $order->NoOrder }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->customer?->NmCust ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('kasir.show', ['type' => 'artwork', 'id' => $order->id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700">
                                    Bayar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada order artwork yang menunggu pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'replacement'" x-cloak class="overflow-x-auto">
            <table class="w-full text-sm min-w-[720px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr>
                    <th class="px-4 py-3">Nota Hangus</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3 text-right">Dana Dibayar Lama</th><th class="px-4 py-3 text-right">Aksi</th>
                </tr></thead>
                <tbody class="divide-y">
                    @forelse ($replacementOrders as $order)
                        <tr>
                            <td class="px-4 py-3"><span class="font-semibold text-gray-900">{{ $order->NoOrder }}</span><span class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Hangus</span><p class="mt-1 text-xs text-gray-500">{{ $order->cancel_reason }}</p></td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->customer?->NmCust ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-gray-900">Rp {{ number_format($order->jumlah_dibayar ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('kasir.replacement.create', $order) }}" class="inline-flex items-center rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">Buat Nota Pengganti</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Tidak ada nota hangus yang menunggu penggantian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-show="tab === 'dp'" x-cloak class="overflow-x-auto">
            <table class="w-full text-sm min-w-[720px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 w-12">No</th>
                        <th class="px-4 py-3">No Order</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Sudah Dibayar</th>
                        <th class="px-4 py-3 text-right">Sisa Piutang</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($dpOrders as $order)
                        <tr>
                            <td class="px-4 py-3 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $order->NoOrder }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->customer?->NmCust ?? '-' }}</td>
                            <td class="px-4 py-3 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-green-700">Rp {{ number_format($order->jumlah_dibayar ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-amber-700 font-semibold">Rp {{ number_format($order->jumlah_piutang ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('kasir.lunasi', ['type' => 'outdoor', 'id' => $order->id]) }}" class="inline"
                                      onsubmit="return confirm('Konfirmasi pelunasan sisa DP order {{ $order->NoOrder }} sebesar Rp {{ number_format($order->jumlah_piutang ?? 0, 0, ',', '.') }}?')">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-md hover:bg-green-700">
                                        Lunasi
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Tidak ada order outdoor dengan sisa DP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
