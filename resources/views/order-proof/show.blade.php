<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Proof <span class="font-mono">{{ $proof->NoOrder }}</span></h2>
            <div class="no-print flex gap-2">
                <button type="button" onclick="window.print()" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cetak</button>
                @can('order-proof.manage')
                    <a href="{{ route('order-proof.index') }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Daftar proof</a>
                @endcan
            </div>
        </div>
    </x-slot>

    @push('styles')
        <style>
            @media print {
                header, nav, .no-print { display: none !important; }
                body { background: #fff !important; }
                main { padding: 0 !important; }
            }
        </style>
    @endpush

    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <div class="grid gap-4 text-sm sm:grid-cols-4">
            <div><div class="text-xs uppercase text-gray-500">Tipe</div><div class="font-semibold">Proof {{ $proof->typeLabel() }}</div></div>
            <div><div class="text-xs uppercase text-gray-500">Customer</div><div class="font-semibold">{{ $proof->customer?->NmCust ?? $proof->KdCust }}</div></div>
            <div><div class="text-xs uppercase text-gray-500">Status</div><div class="font-semibold">{{ $proof->statusLabel() }}</div></div>
            <div><div class="text-xs uppercase text-gray-500">Tagihan</div><div class="font-semibold">Rp 0</div></div>
        </div>

        <table class="mt-6 w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-3 py-2">#</th>
                    <th class="px-3 py-2">Nama file / judul</th>
                    <th class="px-3 py-2">{{ $proof->order_type === 'outdoor' ? 'Printer · Bahan' : 'Produk' }}</th>
                    <th class="px-3 py-2">Perkiraan ukuran</th>
                    <th class="px-3 py-2">Qty</th>
                    <th class="px-3 py-2">Catatan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($proof->items as $item)
                    <tr>
                        <td class="px-3 py-2 text-gray-500">{{ $loop->iteration }}</td>
                        <td class="px-3 py-2 font-semibold">{{ $item->NmFile ?: '-' }}</td>
                        <td class="px-3 py-2">{{ $itemLabels[$proof->order_type][$item->kode] ?? ($item->kode ?: '-') }}</td>
                        <td class="px-3 py-2">{{ $item->Panjang || $item->Lebar ? (float) $item->Panjang.' × '.(float) $item->Lebar : 'Belum pasti' }}</td>
                        <td class="px-3 py-2">{{ $item->Qty }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $item->catatan ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($proof->catatan)
            <div class="mt-4 rounded-md bg-gray-50 p-3 text-sm"><span class="text-xs font-semibold uppercase text-gray-500">Catatan</span><div class="mt-1 whitespace-pre-line">{{ $proof->catatan }}</div></div>
        @endif

        <div class="mt-6">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Riwayat</div>
            <ul class="mt-2 space-y-1 text-sm">
                <li>Dibuat {{ $proof->created_at?->format('d-m-Y H:i') }} oleh <strong>{{ $proof->createdBy?->name ?? '-' }}</strong></li>
                @if ($proof->layout_done_at)
                    <li>Selesai di Layout {{ $proof->layout_done_at->format('d-m-Y H:i') }} oleh <strong>{{ $proof->layoutDoneBy?->name ?? '-' }}</strong></li>
                @endif
                @if ($proof->converted_at)
                    <li>Jadi order <strong class="font-mono">{{ $convertedOrder?->NoOrder ?? '#'.$proof->converted_order_id }}</strong> {{ $proof->converted_at->format('d-m-Y H:i') }} oleh <strong>{{ $proof->convertedBy?->name ?? '-' }}</strong></li>
                @endif
                @if ($proof->closed_at)
                    <li>Ditutup {{ $proof->closed_at->format('d-m-Y H:i') }} oleh <strong>{{ $proof->closedBy?->name ?? '-' }}</strong> — {{ $proof->close_reason }}</li>
                @endif
            </ul>
        </div>

        @can('order-proof.manage')
            <div class="no-print mt-6 flex flex-wrap items-start gap-3 border-t pt-4">
                @if ($proof->status === 'selesai')
                    <a href="{{ route($proof->orderFormType() === 'outdoor' ? 'order-outdoor.create' : 'order-indoor.create', ['proof' => $proof->id]) }}"
                       class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Buat order reguler</a>
                @endif
                @if (in_array($proof->status, ['layout', 'selesai'], true))
                    <form method="POST" action="{{ route('order-proof.close', $proof) }}" class="flex gap-2"
                          onsubmit="return confirm('Tutup proof ini? Proof tidak bisa dibuatkan order lagi.')">
                        @csrf
                        <input type="text" name="alasan" required maxlength="255" placeholder="Alasan tidak jadi order" class="rounded-md border-gray-300 text-sm">
                        <button type="submit" class="rounded-md border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">Tutup proof</button>
                    </form>
                @endif
            </div>
        @endcan
    </div>
</x-app-layout>
