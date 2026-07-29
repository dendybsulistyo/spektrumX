@php
    $stageLabels = [
        'desain' => 'Desain',
        'cetak' => 'Cetak',
        'qc' => 'QC',
        'kasir' => 'Kasir',
        'pengambilan' => 'Pengambilan',
        'pembatalan' => 'Pembatalan',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Monitoring Kinerja</h2>
    </x-slot>

    <div class="space-y-6">
        {{-- Filter bar --}}
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Dari Tanggal</label>
                    <input type="date" name="from" value="{{ $from }}" class="rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Sampai Tanggal</label>
                    <input type="date" name="to" value="{{ $to }}" class="rounded-md border-gray-300 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-700">Terapkan</button>
            </form>
        </div>

        {{-- Ringkasan per tahap --}}
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Jumlah Order Diproses per Staf</h3>
                <p class="text-xs text-gray-400 mt-0.5">Dihitung dari catatan perpindahan status tiap order (Order Indoor, Outdoor, Artwork).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[560px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Staf</th>
                            @foreach ($stages as $stage)
                                <th class="px-4 py-3 text-right">{{ $stageLabels[$stage] }}</th>
                            @endforeach
                            <th class="px-4 py-3 text-right font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($staffRows as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $row['name'] }}</td>
                                @foreach ($stages as $stage)
                                    <td class="px-4 py-3 text-right text-gray-600">{{ $row['counts'][$stage] ?? '-' }}</td>
                                @endforeach
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ array_sum($row['counts']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($stages) + 2 }}" class="px-4 py-8 text-center text-gray-400">
                                    Tidak ada aktivitas pada rentang tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detail cetak Outdoor per unit --}}
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700">Jumlah Unit Dicetak (Order Outdoor)</h3>
                <p class="text-xs text-gray-400 mt-0.5">Dihitung per lembar/pcs yang ditandai selesai di Antrian Cetak Outdoor.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[400px]">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Staf</th>
                            <th class="px-4 py-3 text-right">Jumlah Unit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($cetakUnitCounts as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $row->cetakBy?->name ?? 'Tidak diketahui' }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ $row->jumlah_unit }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-gray-400">
                                    Belum ada unit yang dicetak pada rentang tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
