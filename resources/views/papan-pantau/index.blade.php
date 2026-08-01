<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Papan Pantau</h2>
        <p class="text-xs text-gray-400 mt-0.5">Semua order aktif (Indoor/Outdoor/Artwork), dikelompokkan per tahap — read-only.</p>
    </x-slot>

    @php
        $tipeBadge = fn (string $tipe) => match ($tipe) {
            'Indoor' => 'bg-amber-50 text-amber-700',
            'Outdoor' => 'bg-teal-50 text-teal-700',
            'Artwork' => 'bg-violet-50 text-violet-700',
            default => 'bg-gray-100 text-gray-600',
        };
    @endphp

    <div class="overflow-x-auto pb-2">
        <div class="flex gap-3" style="min-width: max-content;">
            @foreach ($columns as $status => $column)
                <div class="bg-white rounded-lg border border-gray-200 flex flex-col" style="width: 260px;">
                    <div class="px-3 py-2.5 border-b border-gray-200 flex items-center justify-between shrink-0">
                        <span class="text-sm font-semibold text-gray-800">{{ $column['label'] }}</span>
                        <span class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">
                            {{ $column['cards']->count() }}
                        </span>
                    </div>

                    <div class="p-2 space-y-2 overflow-y-auto" style="max-height: 70vh;">
                        @forelse ($column['cards'] as $card)
                            <div class="border border-gray-200 rounded-md p-2.5 text-xs {{ $card['telat'] ? 'bg-red-50 border-red-200' : 'bg-gray-50' }}">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <span class="font-semibold text-gray-900 truncate">{{ $card['no_order'] }}</span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold shrink-0 {{ $tipeBadge($card['tipe']) }}">
                                        {{ $card['tipe'] }}
                                    </span>
                                </div>
                                <div class="text-gray-600 truncate">{{ $card['customer'] }}</div>
                                <div class="flex items-center justify-between mt-1.5 text-gray-400">
                                    <span>{{ $card['created_at']?->format('d M, H:i') ?? '-' }}</span>
                                    @if ($card['telat'])
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-red-100 text-red-700 font-semibold text-[10px]">Telat</span>
                                    @elseif ($card['status_bayar'] !== 'lunas')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold text-[10px]">
                                            {{ $card['status_bayar'] === 'belum_bayar' ? 'Belum Bayar' : 'Hutang' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400 text-center py-6">Kosong</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
