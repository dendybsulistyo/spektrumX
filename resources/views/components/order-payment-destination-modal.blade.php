<div x-show="destinationOpen" x-cloak @keydown.escape.window="destinationOpen = false"
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 backdrop-blur-[2px]" style="background: rgba(15, 23, 42, .62)" @click="destinationOpen = false"></div>
    <div class="relative w-full max-w-md overflow-hidden rounded-md border border-gray-300 bg-white shadow-2xl" @click.stop>
        <div class="border-b border-gray-700 px-6 py-5 text-white" style="background: #17233c">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em]" style="color: #93c5fd">Tujuan order</p>
            <h3 class="mt-1.5 text-xl font-semibold tracking-tight">Kirim order ke mana?</h3>
            <p class="mt-2 text-sm leading-6 text-slate-300">Pilih alur pembayaran setelah order disimpan.</p>
        </div>
        <div class="grid gap-3 bg-gray-100 p-5 sm:grid-cols-2">
            <button type="submit" name="payment_queue" value="kasir"
                    class="group rounded border border-gray-300 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    style="border-left: 4px solid #2563eb">
                <span class="mb-3 flex h-8 w-8 items-center justify-center rounded transition" style="background: #eff6ff; color: #1d4ed8">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7.5h16v10H4z"/><path d="M7 11h5M16.5 13.5h.01"/></svg>
                </span>
                <span class="block font-semibold text-slate-900">Kirim ke Kasir</span>
                <span class="mt-1 block text-xs leading-5 text-slate-500">Langsung masuk antrean pembayaran Kasir.</span>
            </button>
            <button type="submit" name="payment_queue" value="cs"
                    class="group rounded border border-gray-300 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                    style="border-left: 4px solid #d97706">
                <span class="mb-3 flex h-8 w-8 items-center justify-center rounded transition" style="background: #fffbeb; color: #b45309">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 20v-2a5 5 0 0 1 10 0v2"/><circle cx="12" cy="8" r="3"/><path d="M18 10.5a2.5 2.5 0 0 1 2 2.45V15M6 10.5a2.5 2.5 0 0 0-2 2.45V15"/></svg>
                </span>
                <span class="block font-semibold text-slate-900">Kirim ke CS</span>
                <span class="mt-1 block text-xs leading-5 text-slate-500">Catat transfer dan status pembayaran lebih dahulu.</span>
            </button>
        </div>
        <div class="flex justify-end border-t border-slate-200 bg-white px-5 py-3.5">
            <button type="button" @click="destinationOpen = false" class="rounded px-3 py-1.5 text-sm font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">Batal</button>
        </div>
    </div>
</div>
