<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Dokumen SO / DO / Invoice</h2></x-slot>
    <div class="bg-white p-6 rounded-lg">
        <form class="mb-4" method="GET">
            <input name="search" value="{{ request('search') }}" placeholder="Cari SO / DO / invoice / customer" class="border rounded">
            <button class="px-4 py-2 border rounded">Cari</button>
        </form>
        <h3 class="mb-2 font-semibold text-gray-900">SO Customer Hutang ({{ $hutangOrders->count() }})</h3>
        <div class="mb-6 overflow-x-auto">
            <table class="w-full text-xs leading-tight text-left">
                <thead><tr><th>Tanggal</th><th>Sales Order</th><th>Jenis</th><th>Customer</th><th class="text-right">Sisa Hutang</th></tr></thead>
                <tbody>
                    @forelse ($hutangOrders as $order)
                        <tr class="border-t">
                            <td class="py-2">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('d/m/Y') }}</td>
                            <td><a class="text-blue-700 underline" href="{{ route('invoice.show', ['type' => $order->order_type, 'id' => $order->id]) }}">{{ $order->NoOrder }}</a></td>
                            <td class="capitalize">{{ $order->order_type }}</td>
                            <td>{{ $order->customer?->NmCust ?: '-' }}</td>
                            <td class="text-right font-semibold text-orange-700">Rp {{ number_format($order->jumlah_piutang ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-gray-500">Tidak ada customer dengan hutang berjalan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3 class="mb-2 font-semibold text-gray-900">Dokumen Terbit</h3>
        <table class="w-full text-xs leading-tight text-left">
            <thead><tr><th>Tanggal</th><th>Dokumen</th><th>Sales Order</th><th>Customer</th></tr></thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr class="border-t">
                        <td class="py-2">{{ $document->issued_at->format('d/m/Y H:i') }}</td>
                        <td><a class="text-blue-700 underline" href="{{ route('order-documents.show', $document) }}">{{ $document->number }}</a>
                            @if (($document->snapshot['origin'] ?? '') === 'historical_demo')
                                <span class="block text-[11px] text-amber-700">Impor historis · demo</span>
                            @endif
                        </td>
                        <td>{{ $document->snapshot['sales_order'] }}</td>
                        <td>{{ $document->snapshot['customer'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4">Belum ada dokumen.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $documents->links() }}
    </div>
</x-app-layout>
