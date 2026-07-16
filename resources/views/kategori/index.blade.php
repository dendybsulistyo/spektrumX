<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800">Daftar Harga Indoor</h2>
            <a href="{{ route('kategori.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Tambah Harga Indoor
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden"
         x-data="{ modalOpen: false, modalTitle: '', modalItems: [] }">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 w-12">No</th>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama Kategori</th>
                    <th class="px-4 py-3">Nomor Urut</th>
                    <th class="px-4 py-3">Jumlah Produk</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($kategori as $item)
                    <tr>
                        <td class="px-4 py-3 text-gray-400">{{ $kategori->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">{{ $item->KdDivs }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $item->NmDivs }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->NoUrut }}</td>
                        <td class="px-4 py-3">
                            @if ($item->produk_count > 0)
                                <button type="button"
                                        @click="modalOpen = true; modalTitle = '{{ addslashes($item->NmDivs) }}'; modalItems = {{ $item->produk->map(fn ($p) => ['kode' => $p->KdProd, 'nama' => $p->NmProd, 'harga' => $p->HargaStd])->toJson() }}"
                                        class="text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                    {{ $item->produk_count }} produk
                                </button>
                            @else
                                <span class="text-gray-400">0 produk</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('kategori.edit', $item) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800" title="Edit"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg></a>
                            <form method="POST" action="{{ route('kategori.destroy', $item) }}" class="inline"
                                  onsubmit="return confirm('Hapus kategori {{ $item->NmDivs }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center text-red-600 hover:text-red-800 ml-2" title="Hapus"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada kategori.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="p-4">
            {{ $kategori->links() }}
        </div>

        <!-- Modal: daftar produk per kategori -->
        <div x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="modalOpen = false" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative bg-white rounded-lg shadow-lg w-full max-w-lg max-h-[80vh] flex flex-col">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between shrink-0">
                    <h3 class="font-semibold text-gray-900" x-text="modalTitle"></h3>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="overflow-y-auto divide-y">
                    <template x-for="produk in modalItems" :key="produk.kode">
                        <div class="px-5 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate" x-text="produk.nama"></p>
                                <p class="text-xs text-gray-400" x-text="produk.kode"></p>
                            </div>
                            <p class="text-sm text-gray-600 shrink-0" x-text="'Rp ' + Number(produk.harga).toLocaleString('id-ID')"></p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
