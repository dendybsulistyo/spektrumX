<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Order Indoor</h2>
            <a href="{{ route('order-indoor.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Buat Order
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm">

        <div class="p-4 border-b">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari no. order atau kode customer..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">Cari</button>
                @if (request('search'))
                    <a href="{{ route('order-indoor.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">No. Order</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Status Cetak</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3 font-mono">{{ $order->NoOrder }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($order->TglOrder)->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-gray-900 font-semibold">{{ $order->customer?->NmCust ?? $order->KdCust }}</td>
                        <td class="px-4 py-3">
                            @if ($order->Cetak)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Sudah Cetak</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Belum Cetak</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('order-indoor.edit', $order) }}" class="text-blue-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('order-indoor.destroy', $order) }}" class="inline"
                                  onsubmit="return confirm('Hapus order {{ $order->NoOrder }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline ml-3">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-400">Belum ada order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4">
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
