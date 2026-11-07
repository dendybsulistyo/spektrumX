<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800">Order Proof</h2>
            <a href="{{ route('order-proof.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Buat Proof
            </a>
        </div>
    </x-slot>

    @php
        $statusBadgeStyle = fn (?string $status) => match ($status) {
            'layout' => 'background-color:#e0e7ff;color:#3730a3',
            'selesai' => 'background-color:#fef3c7;color:#92400e',
            'jadi_order' => 'background-color:#d1fae5;color:#065f46',
            'ditutup' => 'background-color:#f3f4f6;color:#6b7280',
            default => 'background-color:#f3f4f6;color:#6b7280',
        };
    @endphp

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-4 border-b">
            <div class="flex flex-wrap gap-2 mb-3">
                <a href="{{ route('order-proof.index', array_filter(['q' => $search])) }}"
                   class="inline-flex items-center px-3 py-1.5 rounded-md border text-sm font-medium {{ ! $status ? 'bg-gray-900 border-gray-900 text-white' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50' }}">Semua</a>
                @foreach (\App\Models\OrderProof::STATUS_LABELS as $key => $label)
                    <a href="{{ route('order-proof.index', array_filter(['q' => $search, 'status' => $key])) }}"
                       class="inline-flex items-center px-3 py-1.5 rounded-md border text-sm font-medium {{ $status === $key ? 'bg-gray-900 border-gray-900 text-white' : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
                @endforeach
            </div>
            <form method="GET" class="flex gap-2">
                @if ($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <input type="text" name="q" value="{{ $search }}" placeholder="Cari no. proof atau customer..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Cari</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-[13px] min-w-[640px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2">No. Proof</th>
                        <th class="px-3 py-2">Tanggal</th>
                        <th class="px-3 py-2">Tipe</th>
                        <th class="px-3 py-2">Customer</th>
                        <th class="px-3 py-2">Item</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Dibuat oleh</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($proofs as $proof)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 whitespace-nowrap"><a href="{{ route('order-proof.show', $proof) }}" class="font-mono font-semibold text-indigo-700 hover:underline">{{ $proof->NoOrder }}</a></td>
                            <td class="px-3 py-2 whitespace-nowrap text-gray-600">{{ $proof->created_at?->format('d-m-Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $proof->typeLabel() }}</td>
                            <td class="px-3 py-2">{{ $proof->customer?->NmCust ?? $proof->KdCust }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $proof->items->count() }} item</td>
                            <td class="px-3 py-2 whitespace-nowrap"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" style="{{ $statusBadgeStyle($proof->status) }}">{{ $proof->statusLabel() }}</span></td>
                            <td class="px-3 py-2 text-gray-600">{{ $proof->createdBy?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-gray-400">Belum ada order proof.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $proofs->links() }}</div>
    </div>
</x-app-layout>
