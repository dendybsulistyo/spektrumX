<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Data Produk Indoor</h2>
            <a href="{{ route('produk.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Tambah Produk
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm">

        <div class="p-4 border-b">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama atau kode produk..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">Cari</button>
                @if (request('search'))
                    <a href="{{ route('produk.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama Produk</th>
                    <th class="px-4 py-3">Satuan</th>
                    <th class="px-4 py-3 text-right">Harga Standar</th>
                    <th class="px-4 py-3 text-right">Harga Minimum</th>
                    <th class="px-4 py-3">Catatan</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($produk as $item)
                    <tr>
                        <td class="px-4 py-3 font-mono">{{ $item->KdProd }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $item->NmProd }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $item->Satuan }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format($item->HargaStd, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">Rp {{ number_format($item->HargaMin, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if ($item->isPjLb)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-50 text-blue-700 mr-1">Pakai P×L</span>
                            @endif
                            @if ($item->isHPilih)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-purple-50 text-purple-700">Harga Bertingkat</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('produk.edit', $item) }}" class="text-blue-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('produk.destroy', $item) }}" class="inline"
                                  onsubmit="return confirm('Hapus produk {{ $item->NmProd }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline ml-3">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada data produk.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4">
            {{ $produk->links() }}
        </div>
    </div>
</x-app-layout>
