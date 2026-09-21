@props(['type', 'orderId', 'noOrder', 'currentStage', 'maxQty' => null, 'items' => null, 'pending' => null, 'canApprove' => false, 'compact' => false])

@php
    // "Ulang" can only send an order backward in the pipeline — a stage
    // page's own currentStage is where it currently sits, so only the
    // stages before it (in STAGE_LABELS' pipeline order) are valid targets.
    // See OrderReworkController::STAGE_ORDER for the matching server-side check.
    $stageKeys = array_keys(\App\Models\OrderReworkRequest::STAGE_LABELS);
    $currentIndex = array_search($currentStage, $stageKeys, true);
    $stageOptions = collect(\App\Models\OrderReworkRequest::STAGE_LABELS)
        ->only($currentIndex !== false ? array_slice($stageKeys, 0, $currentIndex) : []);
    $reworkItems = collect($items)->map(fn ($item) => [
        'id' => $item->id,
        'label' => $type === 'outdoor'
            ? ($item->gabungan ?: ($item->NmFile ?: 'File #'.$item->id))
            : ($item->Judul ?: 'File #'.$item->id),
        'qty' => $item->qtyAt($currentStage),
    ])->filter(fn ($item) => $item['qty'] > 0)->values();
    $selectFiles = in_array($currentStage, ['cetak', 'finishing', 'qc', 'bungkus', 'siap_diambil'], true)
        && $reworkItems->isNotEmpty();
    $pendingFileNames = $pending && filled($pending->order_detail_ids)
        ? $reworkItems->whereIn('id', $pending->order_detail_ids)->pluck('label')
        : collect();
@endphp

<div x-data="{ open: false, selectedFiles: [] }" class="inline-block mr-1">
    @if ($pending)
        <span class="relative inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-amber-300 bg-amber-50 text-amber-700 text-xs font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            Menunggu Persetujuan
        </span>

        @if ($pending->action === 'batal')
            {{-- Historical "Batalkan Order" requests raised before that
                 action moved to Kasir (Desain stage only) — kept read-only
                 here so old pending rows still show status. Approved
                 centrally on the Approval page, since approving one
                 triggers a refund — not shown here to avoid two different
                 places to approve the same financial action. --}}
            <div class="mt-1 text-xs bg-amber-50 border border-amber-200 rounded-md p-2 max-w-xs">
                <p class="text-amber-800">
                    <span class="font-semibold">Pembatalan</span>
                    — {{ $pending->reason }}
                    <span class="text-amber-500">({{ $pending->requestedBy->name ?? '-' }})</span>
                </p>
                <p class="text-amber-600 mt-1">Lihat menu <span class="font-semibold">Approval</span> untuk menyetujui/menolak.</p>
            </div>
        @elseif ($canApprove)
            <div class="mt-1 text-xs bg-amber-50 border border-amber-200 rounded-md p-2 max-w-xs">
                <p class="text-amber-800">
                    <span class="font-semibold">{{ $pending->qty ?? 'Semua' }} unit &rarr; {{ \App\Models\OrderReworkRequest::STAGE_LABELS[$pending->target_stage] ?? $pending->target_stage }}</span>
                    @if ($pendingFileNames->isNotEmpty())
                        <span class="block mt-0.5">File: {{ $pendingFileNames->join(', ') }}</span>
                    @endif
                    — {{ $pending->reason }}
                    <span class="text-amber-500">({{ $pending->requestedBy->name ?? '-' }})</span>
                </p>
                <div class="flex gap-2 mt-2">
                    <form method="POST" action="{{ route('order-rework.approve', $pending) }}"
                          onsubmit="return confirm('Setujui pengajuan ini?')">
                        @csrf
                        <button type="submit" class="px-2 py-1 bg-emerald-600 text-white rounded font-medium hover:bg-emerald-700">Setujui</button>
                    </form>
                    <form method="POST" action="{{ route('order-rework.reject', $pending) }}"
                          onsubmit="return confirm('Tolak pengajuan ini?')">
                        @csrf
                        <button type="submit" class="px-2 py-1 bg-gray-200 text-gray-700 rounded font-medium hover:bg-gray-300">Tolak</button>
                    </form>
                </div>
            </div>
        @endif
    @elseif ($stageOptions->isNotEmpty())
        <button type="button" @click="open = true" title="Ulang Proses"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-red-300 text-red-600 text-xs font-semibold hover:bg-red-50">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
            </svg>
            {{ $compact ? 'Ulang' : 'Ulang Proses' }}
        </button>

        <div x-show="open" x-cloak @keydown.escape.window="open = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="open = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-md shadow-lg w-full max-w-md" @click.stop>
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Ulang Proses — {{ $noOrder }}</h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('order-rework.store', [$type, $orderId]) }}" class="p-5 space-y-4">
                    @csrf
                    <input type="hidden" name="action" value="ulang">
                    <input type="hidden" name="from_stage" value="{{ $currentStage }}">

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Ulang dari tahap</label>
                        <select name="target_stage" class="w-full rounded-md border-gray-300 text-sm">
                            @foreach ($stageOptions as $stage => $label)
                                <option value="{{ $stage }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($selectFiles)
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <label class="block text-xs font-semibold text-gray-700">Pilih file yang perlu diproses ulang</label>
                                <button type="button"
                                        @click="selectedFiles = selectedFiles.length === {{ $reworkItems->count() }} ? [] : {{ Illuminate\Support\Js::from($reworkItems->pluck('id')->map(fn ($id) => (string) $id)->values()) }}"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-700">
                                    Pilih semua
                                </button>
                            </div>
                            <div class="border border-gray-200 rounded-sm divide-y divide-gray-200 max-h-52 overflow-y-auto">
                                @foreach ($reworkItems as $file)
                                    <label class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-gray-50">
                                        <input type="checkbox" name="detail_ids[]" value="{{ $file['id'] }}" x-model="selectedFiles"
                                               class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="min-w-0 flex-1 text-sm font-medium text-gray-800 truncate">{{ $file['label'] }}</span>
                                        <span class="text-xs text-gray-500">{{ $file['qty'] }} unit</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 mt-1.5">Hanya file yang dicentang yang akan kembali ke tahap yang dipilih.</p>
                        </div>
                    @elseif ($maxQty !== null)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah unit diulang</label>
                            <input type="number" name="qty" min="1" max="{{ $maxQty }}" value="{{ $maxQty }}" required
                                   class="w-full rounded-md border-gray-300 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Maksimal {{ $maxQty }} unit yang sedang ada di tahap ini.</p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Alasan</label>
                        <textarea name="reason" required maxlength="255" rows="3"
                                  class="w-full rounded-md border-gray-300 text-sm" placeholder="Jelaskan alasan pengulangan..."></textarea>
                    </div>

                    <p class="text-xs text-gray-400">Pengajuan ini perlu disetujui sebelum order berubah tahap.</p>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="open = false" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded-md">Batal</button>
                        <button type="submit"
                                @if ($selectFiles) :disabled="selectedFiles.length === 0" @endif
                                class="px-4 py-1.5 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed">Kirim Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
