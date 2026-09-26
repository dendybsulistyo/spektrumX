@php
    $stageLabels = [
        'desain' => 'Desain', 'cetak' => 'Cetak', 'finishing' => 'Finishing', 'qc' => 'QC',
        'bungkus' => 'Bungkus', 'kasir' => 'Kasir', 'pengambilan' => 'Pengambilan', 'pembatalan' => 'Pembatalan',
    ];
    $statusLabels = [
        'baru' => 'Baru', 'dibayar' => 'Dibayar', 'desain' => 'Desain', 'cetak' => 'Cetak',
        'finishing' => 'Finishing', 'qc' => 'QC', 'bungkus' => 'Bungkus', 'siap_diambil' => 'Siap Diambil',
        'selesai' => 'Selesai', 'batal' => 'Batal',
    ];
    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('monitoring-kinerja.index', ['from' => $from, 'to' => $to]) }}" class="text-xs font-semibold text-blue-700 hover:underline">← Monitoring Kinerja</a>
                <h2 class="mt-1 text-xl font-semibold text-gray-800">Detail Kinerja {{ $staff->name }}</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} – {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div><label class="mb-1 block text-xs text-gray-500">Dari Tanggal</label><input type="date" name="from" value="{{ $from }}" class="rounded-md border-gray-300 text-sm"></div>
                <div><label class="mb-1 block text-xs text-gray-500">Sampai Tanggal</label><input type="date" name="to" value="{{ $to }}" class="rounded-md border-gray-300 text-sm"></div>
                <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Terapkan</button>
            </form>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4"><p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nomor SO</p><p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($summary['orders'], 0, ',', '.') }}</p><p class="mt-1 text-xs text-gray-400">SO unik yang dikerjakan</p></div>
            <div class="rounded-lg border border-gray-200 bg-white p-4"><p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Item &amp; Qty</p><p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($summary['items'], 0, ',', '.') }} <span class="text-sm font-medium text-gray-400">item</span></p><p class="mt-1 text-xs text-gray-400">{{ number_format($summary['processed_qty'], 0, ',', '.') }} qty proses · {{ number_format($summary['touched_items'], 0, ',', '.') }} item tersentuh</p></div>
            <div class="rounded-lg border border-gray-200 bg-white p-4"><p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Aktivitas</p><p class="mt-2 text-2xl font-bold text-gray-900">{{ number_format($summary['activities'], 0, ',', '.') }}</p><p class="mt-1 text-xs text-gray-400">Tercatat dalam {{ $summary['active_days'] }} hari aktif</p></div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4"><p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Total Rupiah SO</p><p class="mt-2 text-2xl font-bold text-emerald-800">{{ $rupiah($summary['net_total']) }}</p><p class="mt-1 text-xs text-emerald-600">Nilai akhir {{ $summary['orders'] }} SO unik</p>@if($summary['discount'] > 0)<p class="mt-1 text-[10px] text-emerald-700">Bruto {{ $rupiah($summary['gross_total']) }} · potongan {{ $rupiah($summary['discount']) }}</p>@endif</div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
            <div class="border-b border-gray-200 px-4 py-3">
                <h3 class="text-sm font-semibold text-gray-800">Sebaran Pekerjaan per Tahap</h3>
                <p class="mt-0.5 text-xs text-gray-400">Qty proses adalah akumulasi perpindahan unit pada tahap tersebut.</p>
            </div>
            <div class="grid divide-y divide-gray-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4">
                @foreach($stageStats as $stage => $stat)
                    <div class="p-4 {{ $stat['activities'] ? '' : 'opacity-45' }}">
                        <div class="flex items-center justify-between gap-2"><span class="text-xs font-semibold text-gray-700">{{ $stageLabels[$stage] ?? ucfirst($stage) }}</span><span class="rounded bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600">{{ $stat['orders'] }} SO</span></div>
                        <div class="mt-2 flex items-baseline gap-2"><strong class="text-lg text-gray-900">{{ $stat['activities'] }}</strong><span class="text-xs text-gray-400">aktivitas</span></div>
                        <p class="mt-0.5 text-xs text-gray-500">{{ number_format($stat['qty'], 0, ',', '.') }} qty proses</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
            <div class="border-b border-gray-200 px-4 py-3">
                <h3 class="text-sm font-semibold text-gray-800">Rincian per Nomor SO</h3>
                <p class="mt-0.5 text-xs text-gray-400">Nilai rupiah dihitung satu kali untuk setiap SO, walaupun staf mengerjakan beberapa item atau tahap.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-[12px]">
                    <thead class="bg-gray-50 text-left text-[10px] uppercase tracking-wide text-gray-500">
                        <tr><th class="px-3 py-2.5">Nomor SO</th><th class="px-3 py-2.5">Customer</th><th class="px-3 py-2.5 text-center">Item SO</th><th class="px-3 py-2.5 text-center">Qty SO</th><th class="px-3 py-2.5 text-center">Item Dikerjakan</th><th class="px-3 py-2.5 text-center">Qty Proses</th><th class="px-3 py-2.5">Tahap</th><th class="px-3 py-2.5">Aktivitas Terakhir</th><th class="px-3 py-2.5 text-right">Nilai Akhir SO</th><th class="px-3 py-2.5 text-center">Detail</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $row)
                            <tr class="align-top hover:bg-gray-50/70">
                                <td class="px-3 py-3"><div class="font-semibold text-gray-900">{{ $row['no_order'] }}</div><div class="mt-1 flex items-center gap-1.5"><span class="rounded bg-blue-50 px-1.5 py-0.5 text-[9px] font-bold uppercase text-blue-700">{{ $row['order_type_label'] }}</span><span class="text-[10px] text-gray-400">{{ $statusLabels[$row['status']] ?? ucfirst(str_replace('_', ' ', $row['status'])) }}</span></div></td>
                                <td class="px-3 py-3"><div class="max-w-[180px] font-medium text-gray-700">{{ $row['customer'] }}</div><div class="mt-1 text-[10px] text-gray-400">{{ $row['tanggal_order']?->format('d-m-Y') ?? '-' }}</div></td>
                                <td class="px-3 py-3 text-center font-semibold text-gray-700">{{ $row['item_count'] }}</td>
                                <td class="px-3 py-3 text-center text-gray-600">{{ number_format($row['order_qty'], 0, ',', '.') }}</td>
                                <td class="px-3 py-3 text-center font-semibold text-blue-700">{{ $row['touched_item_count'] ?: '-' }}</td>
                                <td class="px-3 py-3 text-center font-semibold text-blue-700">{{ $row['processed_qty'] ?: '-' }}</td>
                                <td class="px-3 py-3"><div class="flex max-w-[220px] flex-wrap gap-1">@foreach($row['stage_summary'] as $stage)<span class="rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[9px] font-semibold text-gray-600">{{ $stageLabels[$stage['stage']] ?? ucfirst($stage['stage']) }} · {{ $stage['count'] }}</span>@endforeach</div></td>
                                <td class="px-3 py-3 text-gray-600">{{ $row['last_activity_at']?->format('d-m-Y H:i') ?? '-' }}<div class="mt-1 text-[10px] text-gray-400">{{ $row['activity_count'] }} catatan</div></td>
                                <td class="px-3 py-3 text-right"><strong class="whitespace-nowrap text-gray-900">{{ $rupiah($row['net_total']) }}</strong>@if($row['discount'] > 0)<div class="mt-1 text-[10px] text-rose-600">Potongan {{ $rupiah($row['discount']) }}</div>@endif</td>
                                <td class="px-3 py-3 text-center"><button type="button" data-detail-toggle="{{ $row['key'] }}" class="rounded border border-gray-300 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-gray-700 hover:border-blue-400 hover:text-blue-700">Lihat</button></td>
                            </tr>
                            <tr id="detail-{{ $row['key'] }}" class="hidden bg-slate-50/80">
                                <td colspan="10" class="px-4 py-4">
                                    <div class="grid gap-4 lg:grid-cols-[1.2fr_.8fr]">
                                        <div><h4 class="mb-2 text-xs font-semibold text-gray-800">Item dalam SO</h4><div class="overflow-hidden rounded border border-gray-200 bg-white"><table class="w-full text-[11px]"><thead class="bg-gray-50 text-gray-500"><tr><th class="px-3 py-2 text-left">Item</th><th class="px-3 py-2 text-right">Qty SO</th><th class="px-3 py-2 text-right">Qty Diproses Staf</th><th class="px-3 py-2 text-left">Tahap</th></tr></thead><tbody class="divide-y">@forelse($row['items'] as $item)<tr><td class="px-3 py-2"><strong class="text-gray-700">{{ $item['label'] }}</strong>@if($item['description'])<div class="text-[10px] text-gray-400">{{ $item['description'] }}</div>@endif</td><td class="px-3 py-2 text-right">{{ $item['qty'] }}</td><td class="px-3 py-2 text-right font-semibold {{ $item['processed_qty'] ? 'text-blue-700' : 'text-gray-300' }}">{{ $item['processed_qty'] ?: '-' }}</td><td class="px-3 py-2 text-gray-500">{{ $item['stages']->map(fn($stage) => $stageLabels[$stage] ?? ucfirst($stage))->join(', ') ?: '-' }}</td></tr>@empty<tr><td colspan="4" class="px-3 py-4 text-center text-gray-400">Detail item tidak tersedia.</td></tr>@endforelse</tbody></table></div></div>
                                        <div><h4 class="mb-2 text-xs font-semibold text-gray-800">Riwayat Aktivitas Staf</h4><div class="max-h-64 space-y-2 overflow-y-auto rounded border border-gray-200 bg-white p-3">@foreach($row['activities'] as $activity)<div class="border-l-2 border-blue-200 pl-3"><div class="flex items-center justify-between gap-3"><strong class="text-[11px] text-gray-700">{{ $stageLabels[$activity['stage']] ?? ucfirst($activity['stage']) }} · {{ ucfirst(str_replace('_', ' ', $activity['action'])) }}</strong><span class="whitespace-nowrap text-[9px] text-gray-400">{{ $activity['created_at']?->format('d-m H:i') }}</span></div><p class="mt-0.5 text-[10px] text-gray-500">{{ $activity['qty'] ? number_format($activity['qty'], 0, ',', '.').' qty' : 'Aktivitas order' }}{{ $activity['catatan'] ? ' · '.$activity['catatan'] : '' }}</p></div>@endforeach</div></div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="px-4 py-10 text-center text-gray-400">Tidak ada aktivitas staf pada rentang tanggal ini.</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows->isNotEmpty())
                    <tfoot class="border-t-2 border-gray-200 bg-gray-50 font-semibold"><tr><td colspan="2" class="px-3 py-3 text-gray-700">TOTAL {{ $summary['orders'] }} SO</td><td class="px-3 py-3 text-center">{{ $summary['items'] }}</td><td></td><td class="px-3 py-3 text-center text-blue-700">{{ $summary['touched_items'] }}</td><td class="px-3 py-3 text-center text-blue-700">{{ number_format($summary['processed_qty'], 0, ',', '.') }}</td><td colspan="2"></td><td class="px-3 py-3 text-right text-emerald-700">{{ $rupiah($summary['net_total']) }}</td><td></td></tr></tfoot>
                    @endif
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
            Total rupiah menunjukkan nilai akhir SO unik yang disentuh staf dalam periode ini. Jika satu SO dikerjakan beberapa staf, nilai SO tersebut dapat muncul pada detail masing-masing staf dan tidak dimaksudkan sebagai omzet pribadi.
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-detail-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const row = document.getElementById(`detail-${button.dataset.detailToggle}`);
                row.classList.toggle('hidden');
                button.textContent = row.classList.contains('hidden') ? 'Lihat' : 'Tutup';
            });
        });
    </script>
</x-app-layout>
