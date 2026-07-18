<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Harga Outdoor</h2>
    </x-slot>

    <form method="POST" action="{{ route('harga-cetak-outdoor.update-matrix') }}" novalidate>
        @csrf

        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[900px]">
                    <thead class="bg-gray-900 text-white text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Bahan</th>
                            <th class="px-4 py-3 text-center w-16">Kode</th>
                            @foreach ($printers as $printer)
                                <th class="px-4 py-3 text-center whitespace-nowrap">{{ $printer->NmPrn }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($bahanList as $bahan)
                            <tr>
                                <td class="px-4 py-3 bg-gray-900 text-white font-medium whitespace-nowrap">{{ $bahan->NmBhn }}</td>
                                <td class="px-2 py-3 text-center bg-red-600 text-white font-semibold">{{ $bahan->NoCetak }}</td>
                                @foreach ($printers as $printer)
                                    @php
                                        $kdCtk = $printer->KdPrn.$bahan->NoCetak;
                                        $harga = $prices->get($kdCtk)?->HargaStd;
                                    @endphp
                                    <td class="px-2 py-1.5">
                                        <input type="number" step="100" min="0"
                                               name="harga[{{ $bahan->NoCetak }}][{{ $printer->KdPrn }}]"
                                               value="{{ old('harga.'.$bahan->NoCetak.'.'.$printer->KdPrn, $harga) }}"
                                               placeholder="-"
                                               class="w-28 text-right rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 2 + count($printers) }}" class="px-4 py-8 text-center text-gray-400">Belum ada data bahan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-200 flex items-center justify-between">
                <p class="text-xs text-gray-400">Kosongkan sel untuk menghapus harga kombinasi bahan &amp; printer tersebut.</p>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                    Simpan Harga
                </button>
            </div>
        </div>
    </form>
</x-app-layout>
