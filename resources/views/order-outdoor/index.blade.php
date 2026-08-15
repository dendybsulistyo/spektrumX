<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800">Order Outdoor</h2>
            <a href="{{ route('order-outdoor.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Buat Order
            </a>
        </div>
    </x-slot>

    @php
        $statusBadgeStyle = fn (?string $status) => match ($status) {
            'baru' => 'background-color:#ffffff;color:#6b7280;border:1px solid #d1d5db',
            'desain' => 'background-color:#e0e7ff;color:#3730a3',
            'cetak' => 'background-color:#cffafe;color:#155e75',
            'finishing' => 'background-color:#fef3c7;color:#92400e',
            'qc' => 'background-color:#f3e8ff;color:#6b21a8',
            'bungkus' => 'background-color:#fce7f3;color:#9d174d',
            'siap_diambil' => 'background-color:#ccfbf1;color:#115e59',
            'selesai' => 'background-color:#d1fae5;color:#065f46',
            'batal' => 'background-color:#7f1d1d;color:#ffffff',
            default => 'background-color:#f3f4f6;color:#6b7280',
        };
    @endphp

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">

        <div class="p-4 border-b">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari no. order atau kode customer..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Cari</button>
                @if (request('search'))
                    <a href="{{ route('order-outdoor.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full text-[13px] min-w-[640px]">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-3 py-2">No. Order</th>
                    <th class="px-3 py-2">Tanggal</th>
                    <th class="px-3 py-2">Customer</th>
                    <th class="px-3 py-2 text-right">Total</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-3 py-2"><x-order-number :number="$order->NoOrder" /></td>
                        <td class="px-3 py-2 text-gray-600">{{ $order->TglOrder->format('d M Y') }}</td>
                        <td class="px-3 py-2 text-gray-900 font-semibold">{{ $order->customer?->NmCust ?? $order->KdCust }}</td>
                        <td class="px-3 py-2 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" style="{{ $statusBadgeStyle($order->status) }}">{{ ucfirst(str_replace('_', ' ', $order->status ?? '-')) }}</span>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <x-order-discussion type="outdoor" :order-id="$order->id" :no-order="$order->NoOrder"
                                                 :comments="$comments->get($order->id, collect())"
                                                 :unread="$unread->get($order->id, 0)" />
                            <a href="{{ route('order-outdoor.edit', $order) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800" title="Edit"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg></a>
                            <form method="POST" action="{{ route('order-outdoor.destroy', $order) }}" class="inline"
                                  onsubmit="return confirm('Hapus order {{ $order->NoOrder }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center text-red-600 hover:text-red-800 ml-2" title="Hapus"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">Belum ada order.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="p-4">
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
