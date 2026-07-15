<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800">Data Printer</h2>
            <a href="{{ route('printers.create') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                + Tambah Printer
            </a>
        </div>
    </x-slot>

    <div class="bg-white rounded-lg shadow-sm">

        <div class="p-4 border-b">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari nama atau kode printer..."
                       class="w-full max-w-sm rounded-md border-gray-300 text-sm">
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-md hover:bg-gray-200">Cari</button>
                @if (request('search'))
                    <a href="{{ route('printers.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama Printer</th>
                    <th class="px-4 py-3">Nomor Urut</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($printers as $printer)
                    <tr>
                        <td class="px-4 py-3 font-mono">{{ $printer->KdPrn }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $printer->NmPrn }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $printer->NoUrut }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('printers.edit', $printer) }}" class="text-blue-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('printers.destroy', $printer) }}" class="inline"
                                  onsubmit="return confirm('Hapus printer {{ $printer->NmPrn }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline ml-3">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">Belum ada data printer.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4">
            {{ $printers->links() }}
        </div>
    </div>
</x-app-layout>
