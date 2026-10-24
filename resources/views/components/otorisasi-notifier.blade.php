{{-- Pop-up bagi kasir saat pengajuan diskon/hutang miliknya selesai diotorisasi.
     Cek berkala ke server; "since" disimpan per user di localStorage agar
     keputusan yang terjadi saat kasir tidak membuka aplikasi tetap muncul. --}}
<div x-data="{
        open: false,
        items: [],
        storageKey: 'otorisasi-since-{{ auth()->id() }}',
        since: null,
        timer: null,
        init() {
            try { this.since = localStorage.getItem(this.storageKey); } catch (e) {}
            this.since ??= @js(now()->toIso8601String());
            this.check();
            this.timer = setInterval(() => this.check(), 15000);
        },
        check() {
            if (document.hidden || this.open) return;
            fetch(@js(route('kasir.authorization-updates')) + '?since=' + encodeURIComponent(this.since), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data) return;
                    this.since = data.server_time;
                    try { localStorage.setItem(this.storageKey, this.since); } catch (e) {}
                    if (data.items.length) { this.items = data.items; this.open = true; }
                })
                .catch(() => {});
        },
     }"
     x-show="open" x-cloak
     class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-4"
     @keydown.escape.window="open = false">
    <div class="w-full max-w-lg rounded-lg bg-white shadow-xl" @click.outside="open = false">
        <div class="border-b px-5 py-4">
            <h3 class="font-semibold text-gray-900">Hasil Otorisasi</h3>
            <p class="text-xs text-gray-500">Pengajuan Anda sudah diputuskan.</p>
        </div>
        <ul class="max-h-80 divide-y overflow-y-auto">
            <template x-for="item in items" :key="item.key">
                <li class="flex items-start justify-between gap-3 px-5 py-3 text-sm">
                    <div>
                        <div class="font-semibold text-gray-900" x-text="item.order"></div>
                        <div class="text-gray-600" x-text="item.customer"></div>
                        <div class="text-xs text-gray-500">
                            <span x-text="item.kind"></span> · <span x-text="item.value"></span>
                            · oleh <span x-text="item.decided_by || '-'"></span>, <span x-text="item.decided_at"></span>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                          :class="item.approved ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                          x-text="item.approved ? 'Disetujui' : 'Ditolak'"></span>
                </li>
            </template>
        </ul>
        <div class="flex justify-end gap-2 border-t px-5 py-3">
            <a href="{{ route('kasir.index', ['tab' => 'otorisasi']) }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Lihat Otorisasi</a>
            <button type="button" @click="open = false" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">OK</button>
        </div>
    </div>
</div>
