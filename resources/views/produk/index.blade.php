<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800">Data Produk Indoor</h2>
            <a href="{{ route('produk.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Tambah Produk
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">

        <div class="p-4 border-b">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama atau kode produk..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <select name="kategori" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                    <option value="">Semua Kategori</option>
                    @foreach ($kategoriList as $k)
                        <option value="{{ $k->KdDivs }}" @selected(request('kategori') === $k->KdDivs)>{{ $k->NmDivs }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">Cari</button>
                @if (request('search') || request('kategori'))
                    <a href="{{ route('produk.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3 w-16">No. Urut</th>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Divisi</th>
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3 text-right">Harga Std</th>
                    <th class="px-4 py-3 text-right">Harga Min</th>
                    <th class="px-4 py-3">Satuan</th>
                    <th class="px-4 py-3 text-center">Pj x Lebar</th>
                    <th class="px-4 py-3 text-center">Pil Harga</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($produk as $item)
                    <tr>
                        <td class="px-4 py-3 text-gray-400">{{ $item->NoUrut }}</td>
                        <td class="px-4 py-3">{{ $item->KdProd }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->kategori?->NmDivs ?? '-' }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $item->NmProd }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($item->HargaStd, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($item->HargaMin, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->Satuan }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-1.5 rounded-full text-xs font-semibold whitespace-nowrap"
                                  style="{{ $item->pjLbBadgeStyle() }}"
                                  title="{{ $item->pjLbLabel() }}">{{ $item->pjLbBadgeText() }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $item->isHPilih === 1 ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('produk.edit', $item) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800" title="Edit"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg></a>
                            <form method="POST" action="{{ route('produk.destroy', $item) }}" class="inline"
                                  onsubmit="return confirm('Hapus produk {{ $item->NmProd }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center text-red-600 hover:text-red-800 ml-2" title="Hapus"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8 text-center text-gray-400">Belum ada data produk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="p-4">
            {{ $produk->links() }}
        </div>
    </div>
</x-app-layout>
