<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Riwayat Ulang Proses</h2></x-slot>

    <style>
        #rework-history .filters { display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px; }
        #rework-history .filters label { display:flex; flex-direction:column; gap:4px; color:#475569; font-size:12px; font-weight:600; }
        #rework-history .filters input, #rework-history .filters select { height:34px; padding:0 10px; border:1px solid #cbd5e1; border-radius:4px; background:#fff; font-size:13px; }
        #rework-history table { width:100%; border-collapse:collapse; font-size:13px; }
        #rework-history th { padding:8px 10px; background:#f1f5f9; border-bottom:1px solid #cbd5e1; color:#475569; font-size:11px; letter-spacing:.06em; text-align:left; text-transform:uppercase; white-space:nowrap; }
        #rework-history td { padding:8px 10px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
    </style>

    <div id="rework-history" class="mx-auto max-w-[1400px] space-y-4">
        <form method="GET" class="filters rounded border border-slate-200 bg-white p-4">
            <label>Cari No. Order<input name="search" value="{{ $search }}" placeholder="IND.2.2610… / OUT.1.…" style="width:200px;"></label>
            <label>Dari<input type="date" name="dari" value="{{ $dari }}"></label>
            <label>Sampai<input type="date" name="sampai" value="{{ $sampai }}"></label>
            <label>Tahap asal
                <select name="tahap">
                    <option value="">Semua tahap</option>
                    @foreach ($stageLabels as $value => $label)<option value="{{ $value }}" @selected($tahap === $value)>{{ $label }}</option>@endforeach
                </select>
            </label>
            <label>Jenis
                <select name="jenis">
                    <option value="ulang" @selected($jenis === 'ulang')>Ulang proses</option>
                    <option value="batal" @selected($jenis === 'batal')>Batal</option>
                    <option value="semua" @selected($jenis === 'semua')>Semua</option>
                </select>
            </label>
            <label>Status
                <select name="status">
                    <option value="">Semua</option>
                    <option value="pending" @selected($status === 'pending')>Menunggu</option>
                    <option value="approved" @selected($status === 'approved')>Disetujui</option>
                    <option value="rejected" @selected($status === 'rejected')>Ditolak</option>
                </select>
            </label>
            <button class="rounded bg-slate-800 px-4 text-sm font-semibold text-white" style="height:34px;">Tampilkan</button>
            <a href="{{ route('rework-history.index') }}" class="flex items-center rounded border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700" style="height:34px;">Reset</a>
        </form>

        <div class="overflow-x-auto rounded border border-slate-200 bg-white">
            <table>
                <thead><tr>
                    <th style="width:44px;text-align:center;">No</th><th>Tgl Request</th><th>No. Order</th><th>Customer</th>
                    <th>Tahap</th><th>Proses</th><th style="text-align:center;">Qty</th><th>Alasan</th><th>Diminta oleh</th><th>Status</th>
                </tr></thead>
                <tbody>
                    @forelse ($requests as $rework)
                        @php
                            $order = $orders->get($rework->order_type.'-'.$rework->order_id);
                            $statusLabel = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$rework->status] ?? $rework->status;
                            $statusClass = ['pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-700'][$rework->status] ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <tr>
                            <td style="text-align:center;" class="text-gray-500">{{ $requests->firstItem() + $loop->index }}</td>
                            <td class="whitespace-nowrap text-gray-600">{{ $rework->requested_at?->format('d-m-Y H:i') }}</td>
                            <td class="whitespace-nowrap"><span class="font-semibold text-gray-900">{{ $order?->NoOrder ?? '#'.$rework->order_id }}</span> <span class="text-xs text-gray-400">{{ ucfirst($rework->order_type) }}</span></td>
                            <td class="text-gray-600">{{ $order?->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="whitespace-nowrap">{{ $stageLabels[$rework->current_stage] ?? ucfirst((string) $rework->current_stage) }}</td>
                            <td class="whitespace-nowrap">
                                @if ($rework->action === 'ulang')
                                    Ulang ke <strong>{{ $stageLabels[$rework->target_stage] ?? ucfirst((string) $rework->target_stage) }}</strong>
                                @else
                                    <span class="text-red-700">Batal</span>
                                @endif
                            </td>
                            <td style="text-align:center;">{{ $rework->qty ?: '-' }}</td>
                            <td class="text-gray-700" style="min-width:220px;">{{ $rework->reason ?: '-' }}</td>
                            <td class="whitespace-nowrap text-gray-700">{{ $rework->requestedBy?->name ?? '-' }}</td>
                            <td class="whitespace-nowrap">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                @if ($rework->resolvedBy)
                                    <div class="mt-1 text-xs text-gray-500">oleh {{ $rework->resolvedBy->name }}{{ $rework->resolved_at ? ', '.$rework->resolved_at->format('d-m H:i') : '' }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="py-8 text-center text-gray-400">Tidak ada pengajuan ulang proses pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-gray-500">
            <span>{{ number_format($requests->total(), 0, ',', '.') }} pengajuan</span>
            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
