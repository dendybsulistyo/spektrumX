<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">Lembar Kerja Customer Service</h2>
        </div>
    </x-slot>

    @push('styles')
        <style>
            .job-history-filter {
                display: grid;
                grid-template-columns: minmax(280px, 1fr) 170px 170px auto;
                align-items: end;
                gap: 12px;
            }

            @media (max-width: 900px) {
                .job-history-filter {
                    grid-template-columns: 1fr 1fr;
                }

                .job-history-search,
                .job-history-submit {
                    grid-column: 1 / -1;
                }
            }

            @media (max-width: 560px) {
                .job-history-filter {
                    grid-template-columns: 1fr;
                }

                .job-history-search,
                .job-history-submit {
                    grid-column: auto;
                }
            }
        </style>
    @endpush

    <div class="mb-4 flex justify-end">
        <a href="{{ route('customer-service.index') }}" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">+ Lembar Kerja Baru</a>
    </div>

    <div class="mb-4 flex gap-1 border-b border-slate-200">
        <a href="{{ route('customer-service.job-sheets.index', ['tab' => 'pending']) }}"
           class="border-b-2 px-4 py-2.5 text-sm font-semibold {{ $tab === 'pending' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            Belum Diambil ({{ $pendingCount }})
        </a>
        <a href="{{ route('customer-service.job-sheets.index', ['tab' => 'claimed']) }}"
           class="border-b-2 px-4 py-2.5 text-sm font-semibold {{ $tab === 'claimed' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
            Sudah Diambil ({{ $claimedCount }})
        </a>
    </div>

    @if ($tab === 'claimed')
        <div class="mb-4 border border-slate-200 bg-white p-4 shadow-sm">
            <form method="GET" action="{{ route('customer-service.job-sheets.index') }}" class="job-history-filter">
                <input type="hidden" name="tab" value="claimed">
                @if ($showAllHistory)
                    <input type="hidden" name="history" value="all">
                @endif
                <div class="job-history-search">
                    <label for="history-q" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari riwayat</label>
                    <input id="history-q" type="search" name="q" value="{{ $keyword }}"
                           placeholder="Customer, file, PC, OPF, atau operator"
                           class="w-full rounded-sm border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="history-from" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Dari tanggal</label>
                    <input id="history-from" type="date" name="from" value="{{ $from }}"
                           class="w-full rounded-sm border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="history-to" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sampai tanggal</label>
                    <input id="history-to" type="date" name="to" value="{{ $to }}"
                           class="w-full rounded-sm border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <button type="submit" class="job-history-submit rounded-sm bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Cari</button>
            </form>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                <p>
                    @if ($showAllHistory)
                        Menampilkan seluruh {{ $totalClaimedCount }} riwayat lembar kerja yang sudah diambil.
                    @else
                        Menampilkan {{ $claimedCount }} dari {{ $totalClaimedCount }} riwayat untuk periode {{ \Illuminate\Support\Carbon::parse($from)->format('d/m/Y') }}–{{ \Illuminate\Support\Carbon::parse($to)->format('d/m/Y') }}.
                    @endif
                </p>
                @if ($showAllHistory)
                    <a href="{{ route('customer-service.job-sheets.index', ['tab' => 'claimed']) }}" class="font-semibold text-blue-700 hover:text-blue-800">Kembali ke 1 bulan terakhir</a>
                @else
                    <a href="{{ route('customer-service.job-sheets.index', ['tab' => 'claimed', 'history' => 'all']) }}" class="font-semibold text-blue-700 hover:text-blue-800">Lihat seluruh riwayat</a>
                @endif
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">PC</th>
                        <th class="px-4 py-3">Folder / File</th>
                        {{-- <th class="px-4 py-3">Deadline</th> --}}
                        <th class="px-4 py-3">OPF</th>
                        <th class="px-4 py-3">Pembuat</th>
                        <th class="px-4 py-3">Diambil Oleh</th>
                        <th class="px-4 py-3">Jenis Order</th>
                        <th class="px-4 py-3 text-center">Item</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($jobSheets as $sheet)
                        <tr class="align-middle hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $sheet->received_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3"><p class="font-semibold text-slate-900">{{ $sheet->customer?->NmCust ?? $sheet->customer_name }}</p><p class="text-xs text-slate-400">{{ $sheet->customer_code }}</p></td>
                            <td class="px-4 py-3 text-slate-600">{{ $sheet->pc ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $sheet->folder_file ?: '-' }}</td>
                            {{-- <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $sheet->deadline?->format('d/m/Y') ?? '-' }}</td> --}}
                            <td class="px-4 py-3 text-slate-600">{{ $sheet->opf ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $sheet->creator?->name ? ucwords(mb_strtolower($sheet->creator->name)) : '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                @if ($sheet->claimed_at)
                                    <p class="font-medium text-slate-800">{{ $sheet->claimant?->name ? ucwords(mb_strtolower($sheet->claimant->name)) : '-' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-400">{{ ucfirst($sheet->claimed_order_type) }} · {{ $sheet->claimed_at->format('d/m/Y H:i') }}</p>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach (collect($sheet->items)->pluck('order_type')->unique() as $orderType)
                                        <span class="rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">{{ $orderType }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center"><span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ count($sheet->items) }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" onclick="document.getElementById('sheet-detail-{{ $sheet->id }}').showModal()" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Lihat</button>
                                    @if (! $sheet->claimed_at)
                                        @can('file-monitor.view')
                                            <button type="button" onclick="document.getElementById('sheet-claim-{{ $sheet->id }}').showModal()" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">Ambil</button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-6 py-14 text-center text-slate-400">{{ $tab === 'claimed' ? 'Tidak ada riwayat lembar kerja pada pencarian ini.' : 'Belum ada lembar kerja yang menunggu diambil.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $jobSheets->links() }}</div>

    @foreach ($jobSheets as $sheet)
        @if (! $sheet->claimed_at)
            @can('file-monitor.view')
                <dialog id="sheet-claim-{{ $sheet->id }}" class="w-[min(480px,calc(100%-2rem))] rounded border-0 p-0 shadow-2xl backdrop:bg-slate-950/60">
                    <div class="border-b border-slate-700 px-5 py-4 text-white" style="background:#17233c">
                        <div class="flex items-start justify-between gap-4">
                            <div><p class="text-xs uppercase tracking-widest text-blue-300">Ambil Lembar Kerja</p><h3 class="mt-1 text-lg font-semibold">{{ $sheet->customer_name }}</h3></div>
                            <button type="button" onclick="this.closest('dialog').close()" class="text-xl text-slate-300 hover:text-white">&times;</button>
                        </div>
                    </div>
                    <div class="p-5">
                        <p class="text-sm leading-6 text-slate-600">Pilih form transaksi yang akan dibuka. Form dibuka di tab baru dan data customer, ukuran, jumlah, serta finishing akan dibawa dari lembar kerja.</p>
                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <form method="POST" target="_blank" action="{{ route('customer-service.job-sheets.claim', ['jobSheet' => $sheet, 'target' => 'indoor']) }}" onsubmit="setTimeout(() => window.location.reload(), 800)">
                                @csrf
                                <button type="submit" class="w-full rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800 hover:bg-blue-100">Order Indoor</button>
                            </form>
                            <form method="POST" target="_blank" action="{{ route('customer-service.job-sheets.claim', ['jobSheet' => $sheet, 'target' => 'outdoor']) }}" onsubmit="setTimeout(() => window.location.reload(), 800)">
                                @csrf
                                <button type="submit" class="w-full rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 hover:bg-amber-100">Order Outdoor</button>
                            </form>
                        </div>
                    </div>
                </dialog>
            @endcan
        @endif

        <dialog id="sheet-detail-{{ $sheet->id }}" class="w-[min(1100px,calc(100%-2rem))] rounded border-0 p-0 shadow-2xl backdrop:bg-slate-950/60">
            <div class="border-b border-slate-700 px-5 py-4 text-white" style="background:#17233c">
                <div class="flex items-start justify-between gap-4">
                    <div><p class="text-xs uppercase tracking-widest text-blue-300">Lembar Kerja CS</p><h3 class="mt-1 text-lg font-semibold">{{ $sheet->customer_name }}</h3></div>
                    <button type="button" onclick="this.closest('dialog').close()" class="text-xl text-slate-300 hover:text-white">&times;</button>
                </div>
            </div>
            <div class="grid gap-3 border-b border-slate-200 bg-slate-50 p-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="text-xs text-slate-400">Tanggal Masuk</p><p class="mt-1 font-medium">{{ $sheet->received_at->format('d/m/Y') }}</p></div>
                <div><p class="text-xs text-slate-400">Deadline</p><p class="mt-1 font-medium">{{ $sheet->deadline?->format('d/m/Y') ?? '-' }}</p></div>
                <div><p class="text-xs text-slate-400">PC</p><p class="mt-1 font-medium">{{ $sheet->pc ?: '-' }}</p></div>
                <div><p class="text-xs text-slate-400">OPF</p><p class="mt-1 font-medium">{{ $sheet->opf ?: '-' }}</p></div>
                <div class="sm:col-span-2"><p class="text-xs text-slate-400">Folder / File</p><p class="mt-1 font-medium">{{ $sheet->folder_file ?: '-' }}</p></div>
                <div class="sm:col-span-2"><p class="text-xs text-slate-400">Keterangan</p><p class="mt-1 font-medium">{{ $sheet->notes ?: '-' }}</p></div>
            </div>
            <div class="overflow-x-auto p-5">
                <table class="w-full min-w-[850px] text-sm">
                    <thead class="bg-slate-100 text-left text-xs uppercase text-slate-500"><tr><th class="px-3 py-2">No.</th><th class="px-3 py-2">Jenis</th><th class="px-3 py-2">Ukuran</th><th class="px-3 py-2">Jumlah</th><th class="px-3 py-2">Material</th><th class="px-3 py-2">Printer</th><th class="px-3 py-2">Finishing</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($sheet->items as $item)
                            <tr><td class="px-3 py-2 text-slate-400">{{ $loop->iteration }}</td><td class="px-3 py-2 font-semibold">{{ $item['order_type'] }}</td><td class="px-3 py-2">{{ $item['width'] }} × {{ $item['height'] }} cm</td><td class="px-3 py-2">{{ $item['quantity'] }}</td><td class="px-3 py-2">{{ $item['material'] }}</td><td class="px-3 py-2">{{ $item['printer'] ?: '-' }}</td><td class="px-3 py-2">{{ $item['finishing'] ?: '-' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end border-t border-slate-200 px-5 py-3"><button type="button" onclick="this.closest('dialog').close()" class="rounded bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Tutup</button></div>
        </dialog>
    @endforeach
</x-app-layout>
