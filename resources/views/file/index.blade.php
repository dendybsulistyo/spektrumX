<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Operator Penerima File</h2>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">

        <div class="p-4 border-b flex flex-wrap items-center gap-3">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari no order, nama customer, atau nama file..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Cari</button>
                @if (request('search'))
                    <a href="{{ route('file.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>

            <div id="file-live-queues"
                 data-endpoint="{{ route('file.live-queue-stats') }}"
                 class="ml-auto flex flex-wrap items-stretch justify-end gap-2"
                 aria-live="polite">
                @php($csQueueTag = auth()->user()->can('customer-service.view') ? 'a' : 'div')
                <{{ $csQueueTag }} @if ($csQueueTag === 'a') href="{{ route('customer-service.job-sheets.index', ['tab' => 'pending']) }}" @endif
                   class="relative flex min-w-[150px] flex-col items-center justify-center rounded-md border border-blue-200 bg-blue-50 px-4 py-2 text-center text-blue-800 transition {{ $csQueueTag === 'a' ? 'hover:border-blue-300 hover:bg-blue-100' : '' }}">
                    <span class="absolute right-2 top-2 flex h-2 w-2" title="Live">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-50"></span>
                        <span id="file-live-pulse" class="relative inline-flex h-2 w-2 rounded-full bg-blue-600"></span>
                    </span>
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-600">Antrian CS</span>
                    <strong id="file-cs-pending-count" class="block text-4xl font-extrabold leading-tight text-blue-900 transition-transform">{{ $liveQueueStats['cs_pending_count'] }}</strong>
                    <span class="text-xs font-semibold text-blue-700">Bisa Diambil</span>
                </{{ $csQueueTag }}>

            @can('kasir.replacement.manage')
                <a href="{{ route('kasir.index', ['tab' => 'replacement']) }}"
                   class="inline-flex items-center px-3 py-2 bg-rose-50 text-rose-700 border border-rose-200 text-xs font-semibold rounded-md hover:bg-rose-100">
                    Nota Pengganti (<span id="file-replacement-count">{{ $replacementCount }}</span>)
                </a>
            @endcan
                <span id="file-live-status" class="sr-only">Terakhir diperbarui {{ $liveQueueStats['checked_at'] }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full text-[13px] min-w-[720px]">
            <thead class="bg-gray-900 text-left text-xs uppercase text-white">
                <tr>
                    <th class="px-3 py-2">No Order</th>
                    <th class="px-3 py-2">Jenis</th>
                    <th class="px-3 py-2">Nama File</th>
                    <th class="px-3 py-2">Customer</th>
                    <th class="px-3 py-2">Tanggal</th>
                    <th class="px-3 py-2">User Input</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($files as $file)
                    <tr>
                        <td class="px-3 py-2 font-semibold text-gray-900">{{ $file->no_order }}</td>
                        <td class="px-3 py-2">
                            <span @class([
                                'inline-flex items-center px-2 py-0.5 rounded-full text-xs',
                                'bg-blue-50 text-blue-700' => $file->jenis === 'Indoor',
                                'bg-amber-50 text-amber-700' => $file->jenis === 'Outdoor',
                                'bg-purple-50 text-purple-700' => $file->jenis === 'Artwork',
                            ])>{{ $file->jenis }}</span>
                        </td>
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $file->nama_file ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $file->customer ? ucwords(mb_strtolower($file->customer)) : '-' }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $file->tanggal }}</td>
                        <td class="px-3 py-2 text-gray-600">{{ $file->user_input ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">Belum ada order masuk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="p-4">
            {{ $files->links() }}
        </div>
    </div>

    <script>
        (() => {
            const widget = document.getElementById('file-live-queues');
            if (!widget) return;

            const pendingCount = document.getElementById('file-cs-pending-count');
            const oldestQueue = document.getElementById('file-cs-oldest');
            const replacementCount = document.getElementById('file-replacement-count');
            const pulse = document.getElementById('file-live-pulse');
            const liveStatus = document.getElementById('file-live-status');
            let fetching = false;

            const updateText = (element, value) => {
                if (!element || element.textContent === String(value)) return;
                element.textContent = value;
                element.classList.add('scale-125');
                window.setTimeout(() => element.classList.remove('scale-125'), 250);
            };

            const refresh = async () => {
                if (fetching || document.hidden) return;
                fetching = true;

                try {
                    const response = await fetch(widget.dataset.endpoint, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-store',
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const stats = await response.json();

                    updateText(pendingCount, stats.cs_pending_count);
                    updateText(oldestQueue, stats.cs_oldest_received_at ? `Terlama ${stats.cs_oldest_received_at}` : 'Tidak ada antrean');
                    updateText(replacementCount, stats.replacement_count);
                    pulse?.classList.remove('bg-amber-500');
                    pulse?.classList.add('bg-blue-600');
                    widget.title = `Data diperbarui pukul ${stats.checked_at}`;
                    if (liveStatus) liveStatus.textContent = `Terakhir diperbarui ${stats.checked_at}`;
                } catch (error) {
                    pulse?.classList.remove('bg-blue-600');
                    pulse?.classList.add('bg-amber-500');
                    widget.title = 'Pembaruan antrean tertunda. Angka terakhir tetap ditampilkan.';
                } finally {
                    fetching = false;
                }
            };

            const timer = window.setInterval(refresh, 5000);
            document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
            window.addEventListener('pagehide', () => window.clearInterval(timer), { once: true });
        })();
    </script>
</x-app-layout>
