@props(['type', 'orderId', 'noOrder', 'comments', 'unread' => 0, 'compact' => false])

<div x-data="{ open: false, unread: {{ (int) $unread }} }" class="inline-block mr-1">
    <button type="button" @click="open = true; if (unread > 0) { unread = 0; axios.post('{{ route('order-comments.read', [$type, $orderId]) }}'); }"
            title="Chat ({{ $comments->count() }})"
            class="relative inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md border border-gray-300 text-gray-600 text-xs font-semibold hover:bg-gray-50">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
        </svg>
        @unless ($compact)
            Chat ({{ $comments->count() }})
        @endunless
        <span x-show="unread > 0" x-cloak
              class="absolute -top-2 -right-2 inline-flex items-center justify-center gap-0.5 h-4 px-1.5 rounded-full bg-red-600 text-white text-[10px] font-bold leading-none shadow-sm">
            <span>Baru</span>
            <span x-text="unread"></span>
        </span>
    </button>

    <div x-show="open" x-cloak @keydown.escape.window="open = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div @click="open = false" class="absolute inset-0 bg-gray-900/50"></div>

        <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md flex flex-col" style="max-height: 80vh;" @click.stop>
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between shrink-0">
                <h3 class="font-semibold text-gray-900">Diskusi — {{ $noOrder }}</h3>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-4 space-y-3 overflow-y-auto" style="flex: 1; min-height: 0;">
                @forelse ($comments as $comment)
                    <div class="bg-gray-50 rounded-md p-3">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-xs font-semibold text-gray-700">{{ $comment->user?->name ?? 'Tidak diketahui' }}</span>
                            <span class="text-xs text-gray-400 shrink-0">{{ $comment->created_at->format('d M Y H:i') }}</span>
                        </div>
                        <p class="text-sm text-gray-700 mt-1" style="white-space: pre-wrap;">{{ $comment->pesan }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-6">Belum ada diskusi untuk order ini.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('order-comments.store', [$type, $orderId]) }}" class="p-4 border-t border-gray-200 flex gap-2 shrink-0">
                @csrf
                <input type="text" name="pesan" required maxlength="1000" placeholder="Tulis pesan..."
                       class="flex-1 rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    Kirim
                </button>
            </form>
        </div>
    </div>
</div>
