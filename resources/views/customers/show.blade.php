<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800">Riwayat Order Customer</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $customer->NmCust }} · {{ $customer->KdCust }}</p>
            </div>
            <a href="{{ route('customers.index') }}" class="text-sm text-indigo-600 hover:underline">← Data Customer</a>
        </div>
    </x-slot>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3">No. Order</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3">Pembayaran</th>
                        <th class="px-4 py-3">Status Produksi</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        <tr>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $order->NoOrder }}</td>
                            <td class="px-4 py-3 capitalize text-gray-600">{{ $order->order_type }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ \Carbon\Carbon::parse($order->TglOrder)->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right text-gray-900">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 capitalize text-gray-600">{{ str_replace('_', ' ', $order->status_bayar ?? '-') }}</td>
                            <td class="px-4 py-3 capitalize text-gray-600">{{ str_replace('_', ' ', $order->status ?? '-') }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('kasir.view')
                                    <a href="{{ route('invoice.show', ['type' => $order->order_type, 'id' => $order->id]) }}" class="text-xs font-semibold text-indigo-600 hover:underline">Lihat Nota</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada order untuk customer ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $orders->links() }}</div>
        @endif
    </div>
</x-app-layout>
