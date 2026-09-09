<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Dokumen SO / DO / Invoice</h2></x-slot>
    <div class="bg-white p-6 rounded-lg">
        <form class="mb-4" method="GET">
            <input name="search" value="{{ request('search') }}" placeholder="Cari nomor DO / invoice" class="border rounded">
            <button class="px-4 py-2 border rounded">Cari</button>
        </form>
        <table class="w-full text-sm text-left">
            <thead><tr><th>Tanggal</th><th>Dokumen</th><th>Sales Order</th><th>Customer</th></tr></thead>
            <tbody>
                @forelse ($documents as $document)
                    <tr class="border-t">
                        <td class="py-3">{{ $document->issued_at->format('d/m/Y H:i') }}</td>
                        <td><a class="text-blue-700 underline" href="{{ route('order-documents.show', $document) }}">{{ $document->number }}</a>
                            @if (($document->snapshot['origin'] ?? '') === 'historical_demo')
                                <span class="block text-xs text-amber-700">Impor historis · demo</span>
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
