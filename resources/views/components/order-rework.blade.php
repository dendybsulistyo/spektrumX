@props(['type', 'orderId', 'noOrder', 'currentStage', 'pending' => null, 'canApprove' => false])

@php
    $stageOptions = collect(\App\Models\OrderReworkRequest::STAGE_LABELS)
        ->except($currentStage);
@endphp

<div x-data="{ open: false, action: 'ulang' }" class="inline-block mr-1">
    @if ($pending)
        <span class="relative inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-amber-300 bg-amber-50 text-amber-700 text-xs font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            Menunggu Persetujuan
        </span>

        @if ($canApprove)
            <div class="mt-1 text-xs bg-amber-50 border border-amber-200 rounded-md p-2 max-w-xs">
                <p class="text-amber-800">
                    <span class="font-semibold">{{ $pending->action === 'batal' ? 'Pembatalan' : 'Ulang ke ' . (\App\Models\OrderReworkRequest::STAGE_LABELS[$pending->target_stage] ?? $pending->target_stage) }}</span>
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
    @else
        <button type="button" @click="open = true"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-red-300 text-red-600 text-xs font-semibold hover:bg-red-50">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
            </svg>
            Batal / Ulang
        </button>

        <div x-show="open" x-cloak @keydown.escape.window="open = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="open = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md" @click.stop>
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Pembatalan / Ulang Proses — {{ $noOrder }}</h3>
                    <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('order-rework.store', [$type, $orderId]) }}" class="p-5 space-y-4">
                    @csrf

                    <div class="flex gap-4 text-sm">
                        <label class="inline-flex items-center gap-1.5">
                            <input type="radio" name="action" value="ulang" x-model="action" checked>
                            Ulang Proses
                        </label>
                        <label class="inline-flex items-center gap-1.5">
                            <input type="radio" name="action" value="batal" x-model="action">
                            Batalkan Order
                        </label>
                    </div>

                    <div x-show="action === 'ulang'">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Ulang dari tahap</label>
                        <select name="target_stage" class="w-full rounded-md border-gray-300 text-sm">
                            @foreach ($stageOptions as $stage => $label)
                                <option value="{{ $stage }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Alasan</label>
                        <textarea name="reason" required maxlength="255" rows="3"
                                  class="w-full rounded-md border-gray-300 text-sm" placeholder="Jelaskan alasan pembatalan/pengulangan..."></textarea>
                    </div>

                    <p class="text-xs text-gray-400">Pengajuan ini perlu disetujui sebelum order berubah tahap.</p>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" @click="open = false" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded-md">Batal</button>
                        <button type="submit" class="px-4 py-1.5 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">Kirim Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
