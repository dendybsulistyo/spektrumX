<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Koreksi Proses</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <section class="rounded border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 shadow-sm">
                <h3 class="font-semibold">Khusus koreksi admin</h3>
                <p class="mt-1">Gunakan hanya bila barang secara fisik sudah melewati proses yang tidak diperlukan. Semua tahap yang dilewati dicatat sebagai selesai beserta admin dan alasannya.</p>
            </section>

            <section class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('order-corrections.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <label class="flex-1 text-sm font-medium text-gray-700">
                        Cari nomor order, customer, atau nama barang
                        <input type="search" name="q" value="{{ $search }}" placeholder="Contoh: Sandblast V170 SM"
                               class="mt-1 block w-full rounded border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </label>
                    <button class="rounded bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Cari</button>
                </form>
            </section>

            <div class="space-y-4">
                @forelse ($items as $row)
                    @php($item = $row['item'])
                    <article class="rounded border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col justify-between gap-4 lg:flex-row">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold uppercase text-indigo-700">{{ $row['type'] }}</span>
                                    <strong class="text-gray-900">{{ $item->order->NoOrder }}</strong>
                                    <span class="text-sm text-gray-500">{{ $item->order->customer?->NmCust ?: '-' }}</span>
                                </div>
                                <h3 class="mt-2 text-base font-semibold text-gray-900">{{ $row['name'] }}</h3>
                                @if ($row['material'])
                                    <p class="mt-1 text-sm text-gray-600">Bahan: {{ $row['material'] }}</p>
                                @endif
                                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                    @foreach ($row['positions'] as $stage => $qty)
                                        <span class="rounded-full bg-gray-100 px-2.5 py-1 font-medium text-gray-700">{{ str($stage)->replace('_', ' ')->title() }}: {{ $qty }}</span>
                                    @endforeach
                                </div>
                            </div>

                            <form method="POST" action="{{ route('order-corrections.store', [$row['type'], $item->id]) }}"
                                  class="grid w-full gap-3 rounded border border-gray-200 bg-gray-50 p-4 lg:w-[32rem] lg:grid-cols-2"
                                  onsubmit="return confirm('Yakin memaksa item ini melewati proses ke status tujuan?')">
                                @csrf
                                <label class="text-sm font-medium text-gray-700">
                                    Pindahkan ke
                                    <select name="target_stage" required class="mt-1 block w-full rounded border-gray-300 text-sm">
                                        <option value="bungkus">Bungkus</option>
                                        <option value="siap_diambil">Siap Diambil</option>
                                    </select>
                                </label>
                                <label class="text-sm font-medium text-gray-700 lg:col-span-2">
                                    Alasan koreksi
                                    <textarea name="reason" required minlength="5" maxlength="500" rows="2" placeholder="Contoh: Stand banner tidak perlu layout dan cetak"
                                              class="mt-1 block w-full rounded border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </label>
                                <button class="rounded bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 lg:col-span-2">Terapkan Koreksi</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded border border-gray-200 bg-white p-10 text-center text-sm text-gray-500 shadow-sm">Tidak ada item produksi yang cocok.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
