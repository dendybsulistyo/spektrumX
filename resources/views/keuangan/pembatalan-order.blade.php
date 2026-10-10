<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Pembatalan Pre-Order &amp; Nota</h2></x-slot>

    <div class="mx-auto max-w-7xl space-y-5" x-data="{ tab: 'preorder' }">
        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-600">Pre-order belum dibayar dapat dibatalkan tanpa menghapus data. Nota yang sudah memiliki transaksi tetap melalui approval agar refund, piutang, dan histori tetap terkontrol.</p>
            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <button type="button" @click="tab = 'preorder'" :class="tab === 'preorder' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'" class="rounded-md px-3 py-2 font-semibold">Pre-Order ({{ $preOrders->count() }})</button>
                <button type="button" @click="tab = 'pending'" :class="tab === 'pending' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'" class="rounded-md px-3 py-2 font-semibold">Menunggu Approval ({{ $pending->count() }})</button>
                <button type="button" @click="tab = 'history'" :class="tab === 'history' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'" class="rounded-md px-3 py-2 font-semibold">Riwayat ({{ $history->count() }})</button>
            </div>
        </section>

        <section x-show="tab === 'preorder'" class="overflow-hidden rounded-lg border bg-white shadow-sm">
            <div class="border-b px-4 py-3"><h3 class="font-semibold text-gray-900">Pre-Order Belum Dibayar</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-xs">
                    <thead class="bg-gray-50 uppercase text-gray-500"><tr><th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Order</th><th class="px-4 py-2">Tipe</th><th class="px-4 py-2">Customer</th><th class="px-4 py-2 text-right">Total</th><th class="px-4 py-2">Pengajuan</th></tr></thead>
                    <tbody class="divide-y">
                    @forelse ($preOrders as $order)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $order->TglOrder?->format('d/m/Y') ?? $order->TglOrder }}</td>
                            <td class="px-4 py-2 font-semibold">{{ $order->NoOrder }}</td>
                            <td class="px-4 py-2">{{ ucfirst($order->order_type) }}</td>
                            <td class="px-4 py-2">{{ $order->customer?->NmCust ?: '-' }}</td>
                            <td class="px-4 py-2 text-right whitespace-nowrap">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                            <td class="px-4 py-2">
                                <details><summary class="cursor-pointer font-semibold text-red-700">Ajukan pembatalan</summary>
                                    <form method="POST" action="{{ route('order-'.$order->order_type.'.request-cancel', $order->id) }}" class="mt-2 flex min-w-[300px] gap-2">
                                        @csrf
                                        <input name="cancel_reason" required maxlength="255" placeholder="Alasan pembatalan" class="min-w-0 flex-1 rounded-md border-gray-300 text-xs">
                                        <button class="rounded-md bg-red-700 px-3 py-2 font-semibold text-white">Ajukan</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada pre-order yang dapat diajukan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab === 'pending'" x-cloak class="overflow-hidden rounded-lg border bg-white shadow-sm">
            <div class="border-b px-4 py-3"><h3 class="font-semibold text-gray-900">Menunggu Persetujuan</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1000px] text-left text-xs">
                    <thead class="bg-gray-50 uppercase text-gray-500"><tr><th class="px-4 py-2">Order</th><th class="px-4 py-2">Jenis</th><th class="px-4 py-2">Customer</th><th class="px-4 py-2">Alasan</th><th class="px-4 py-2">Pengaju</th><th class="px-4 py-2 text-right">Keputusan</th></tr></thead>
                    <tbody class="divide-y">
                    @forelse ($pending as $order)
                        <tr>
                            <td class="px-4 py-2 font-semibold">{{ $order->NoOrder }}<div class="font-normal text-gray-500">{{ ucfirst($order->order_type) }}</div></td>
                            <td class="px-4 py-2">{{ $order->jenis_pembatalan }}</td>
                            <td class="px-4 py-2">{{ $order->customer?->NmCust ?: '-' }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $order->cancel_reason }}</td>
                            <td class="px-4 py-2">{{ $order->cancelRequestedBy?->name ?? '-' }}<div class="text-gray-500">{{ $order->cancel_requested_at?->format('d/m/Y H:i') }}</div></td>
                            <td class="px-4 py-2 text-right">
                                @if (($order->central_kind ?? null) === 'rework_batal')
                                    @can('order-rework.approve')
                                        <div class="inline-flex gap-1">
                                            <form method="POST" action="{{ route('order-rework.approve', $order->cancellation_request_id) }}" onsubmit="return confirm('Setujui pembatalan dan refund {{ $order->NoOrder }}?')">@csrf<button class="rounded bg-red-700 px-2 py-1.5 font-semibold text-white">Setujui + Refund</button></form>
                                            <form method="POST" action="{{ route('order-rework.reject', $order->cancellation_request_id) }}">@csrf<button class="rounded border px-2 py-1.5 font-semibold">Tolak</button></form>
                                        </div>
                                    @else
                                        <span class="text-gray-400">Menunggu approver</span>
                                    @endcan
                                @else
                                @can('order-'.$order->order_type.'.approve-cancel')
                                    <div class="inline-flex flex-wrap justify-end gap-1">
                                        @if ($order->status_bayar !== 'belum_bayar')
                                            <form method="POST" action="{{ route('order-'.$order->order_type.'.approve-cancel', $order->id) }}">@csrf<input type="hidden" name="resolution" value="nota_pengganti"><button class="rounded bg-indigo-600 px-2 py-1.5 font-semibold text-white">Nota Pengganti</button></form>
                                        @endif
                                        <x-batal-total-form :order="$order" :type="$order->order_type" label="Batal Total" button-class="rounded bg-red-700 px-2 py-1.5 font-semibold text-white" />
                                        <form method="POST" action="{{ route('order-'.$order->order_type.'.reject-cancel', $order->id) }}">@csrf<button class="rounded border px-2 py-1.5 font-semibold">Tolak</button></form>
                                    </div>
                                @else
                                    <span class="text-gray-400">Menunggu approver</span>
                                @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada pengajuan yang menunggu.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section x-show="tab === 'history'" x-cloak class="overflow-hidden rounded-lg border bg-white shadow-sm">
            <div class="border-b px-4 py-3"><h3 class="font-semibold text-gray-900">Riwayat Pembatalan</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-xs">
                    <thead class="bg-gray-50 uppercase text-gray-500"><tr><th class="px-4 py-2">Tanggal</th><th class="px-4 py-2">Order</th><th class="px-4 py-2">Jenis</th><th class="px-4 py-2">Customer</th><th class="px-4 py-2">Alasan</th><th class="px-4 py-2">Disetujui Oleh</th><th class="px-4 py-2">Hasil</th></tr></thead>
                    <tbody class="divide-y">
                    @forelse ($history as $order)
                        <tr><td class="px-4 py-2 whitespace-nowrap">{{ $order->cancel_approved_at?->format('d/m/Y H:i') ?? '-' }}</td><td class="px-4 py-2 font-semibold">{{ $order->NoOrder }}<div class="font-normal text-gray-500">{{ ucfirst($order->order_type) }}</div></td><td class="px-4 py-2">{{ $order->jenis_pembatalan }}</td><td class="px-4 py-2">{{ $order->customer?->NmCust ?: '-' }}</td><td class="px-4 py-2 text-gray-600">{{ $order->cancel_reason ?: '-' }}</td><td class="px-4 py-2">{{ $order->cancelApprovedBy?->name ?? '-' }}</td><td class="px-4 py-2">{{ ($order->central_kind ?? null) === 'rework_batal' ? 'Batal total + refund' : ($order->invoice_voided_at ? 'Nota hangus / pengganti' : 'Batal total') }}</td></tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada riwayat pembatalan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
