<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">History Telat</h2></x-slot>

    <style>
        #history-telat .filters { display:flex; flex-wrap:wrap; align-items:flex-end; gap:10px; }
        #history-telat .filters label { display:flex; flex-direction:column; gap:4px; color:#475569; font-size:12px; font-weight:600; }
        #history-telat .filters input, #history-telat .filters select { height:34px; padding:0 10px; border:1px solid #cbd5e1; border-radius:4px; background:#fff; font-size:13px; }
        #history-telat table { width:100%; border-collapse:collapse; font-size:13px; }
        #history-telat th { padding:8px 10px; background:#f1f5f9; border-bottom:1px solid #cbd5e1; color:#475569; font-size:11px; letter-spacing:.06em; text-align:left; text-transform:uppercase; white-space:nowrap; }
        #history-telat td { padding:8px 10px; border-bottom:1px solid #e2e8f0; vertical-align:top; }
        #history-telat .dot { display:inline-block; width:8px; height:8px; margin-right:6px; vertical-align:1px; border-radius:50%; background:#d64545; }
    </style>

    <div id="history-telat" class="mx-auto max-w-[1400px] space-y-4">
        @if ($unavailable)
            <div class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">History telat belum aktif: migrasi <code>order_late_logs</code> belum dijalankan.</div>
        @endif

        <form method="GET" class="filters rounded border border-slate-200 bg-white p-4">
            <label>Cari No. Order<input name="search" value="{{ $search }}" placeholder="IND.2.2610… / OUT.1.…" style="width:200px;"></label>
            <label>Telat dari<input type="date" name="dari" value="{{ $dari }}"></label>
            <label>Sampai<input type="date" name="sampai" value="{{ $sampai }}"></label>
            <label>Tipe
                <select name="tipe">
                    <option value="">Semua</option>
                    @foreach (['indoor' => 'Indoor', 'outdoor' => 'Outdoor', 'artwork' => 'Artwork'] as $value => $label)<option value="{{ $value }}" @selected($tipe === $value)>{{ $label }}</option>@endforeach
                </select>
            </label>
            <label>Tertahan di
                <select name="tahap">
                    <option value="">Semua tahap</option>
                    @foreach ($stageLabels as $value => $label)<option value="{{ $value }}" @selected($tahap === $value)>{{ $label }}</option>@endforeach
                </select>
            </label>
            <label>Kondisi
                <select name="kondisi">
                    <option value="">Semua</option>
                    <option value="berjalan" @selected($kondisi === 'berjalan')>Masih telat</option>
                    <option value="selesai" @selected($kondisi === 'selesai')>Sudah siap</option>
                </select>
            </label>
            <label>Sumber data
                <select name="sumber">
                    <option value="">Semua</option>
                    <option value="tercatat" @selected($sumber === 'tercatat')>Tercatat</option>
                    <option value="perkiraan" @selected($sumber === 'perkiraan')>Perkiraan (order lama)</option>
                </select>
            </label>
            <button class="rounded bg-slate-800 px-4 text-sm font-semibold text-white" style="height:34px;">Tampilkan</button>
            <a href="{{ route('history-telat.index') }}" class="flex items-center rounded border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700" style="height:34px;">Reset</a>
        </form>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Order pernah telat</div>
                <div class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($summary['total'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Masih telat sekarang</div>
                <div class="mt-1 text-2xl font-bold {{ $summary['ongoing'] > 0 ? 'text-red-700' : 'text-slate-900' }}">{{ number_format($summary['ongoing'], 0, ',', '.') }}</div>
            </div>
            <div class="rounded border border-slate-200 bg-white p-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Paling sering tertahan di</div>
                <div class="mt-1 text-sm text-slate-700">
                    @forelse ($summary['stages']->take(3) as $stage => $total)
                        <span class="mr-3"><strong>{{ $stageLabels[$stage] ?? ucfirst($stage) }}</strong> {{ number_format($total, 0, ',', '.') }}</span>
                    @empty
                        -
                    @endforelse
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded border border-slate-200 bg-white">
            <table>
                <thead><tr>
                    <th style="width:44px;text-align:center;">No</th><th>No. Order</th><th>Customer</th><th>Order dibuat</th>
                    <th>Mulai telat</th><th>Tertahan di</th><th>Operator tahap</th><th>Lama telat</th><th>Kondisi</th><th>Alasan</th>
                </tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $order = $orders->get($log->order_type.'-'.$log->order_id);
                            $operatorId = $order && $log->stage && in_array($log->stage, ['desain', 'cetak', 'finishing', 'qc', 'bungkus'], true) ? $order->{$log->stage.'_by'} : null;
                        @endphp
                        <tr>
                            <td style="text-align:center;" class="text-gray-500">{{ $logs->firstItem() + $loop->index }}</td>
                            <td class="whitespace-nowrap"><span class="dot"></span><span class="font-semibold text-gray-900">{{ $order?->NoOrder ?? '#'.$log->order_id }}</span> <span class="text-xs text-gray-400">{{ ucfirst($log->order_type) }}</span></td>
                            <td class="text-gray-600">{{ $order?->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-' }}</td>
                            <td class="whitespace-nowrap text-gray-600">{{ $log->order_created_at?->format('d-m-Y H:i') ?? '-' }}</td>
                            <td class="whitespace-nowrap text-gray-600">{{ $log->late_at->format('d-m-Y H:i') }}</td>
                            <td class="whitespace-nowrap">{{ $log->stageLabel() }}</td>
                            <td class="whitespace-nowrap text-gray-700">{{ $operatorId ? ($operators[$operatorId] ?? '-') : '-' }}</td>
                            <td class="whitespace-nowrap font-semibold {{ $log->resolved_at ? 'text-gray-800' : 'text-red-700' }}">{{ $log->lateDurationLabel() }}</td>
                            <td class="whitespace-nowrap">
                                @if ($log->resolved_at)
                                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">{{ $log->resolved_status === 'batal' ? 'Batal' : 'Sudah siap' }}</span>
                                    <div class="mt-1 text-xs text-gray-500">{{ $log->resolved_at->format('d-m-Y H:i') }}</div>
                                @else
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">Masih telat</span>
                                @endif
                                @if ($log->is_estimated)
                                    <div class="mt-1 text-xs text-amber-700">Perkiraan</div>
                                @endif
                            </td>
                            <td style="min-width:220px;" class="text-sm">
                                @forelse ($lateReasons->get($log->order_type.'-'.$log->order_id, collect()) as $reason)
                                    <div @class(['mt-1.5' => ! $loop->first])>
                                        <div class="text-gray-800">{{ $reason->alasan }}</div>
                                        <div class="text-xs text-gray-500">{{ $reason->user?->name ?? '-' }} · {{ $reason->created_at?->format('d-m-Y H:i') }}</div>
                                    </div>
                                @empty
                                    <span class="text-gray-400">-</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="py-8 text-center text-gray-400">Tidak ada order telat pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between text-sm text-gray-500">
            <span>{{ number_format($logs->total(), 0, ',', '.') }} order · operator tahap = yang menyelesaikan tahap tersebut</span>
            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
