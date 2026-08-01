<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Dashboard</h2>
    </x-slot>

    @php
        $cards = [
            ['label' => 'Order Masuk', 'value' => $stats['total'], 'color' => 'text-gray-900'],
            ['label' => 'Menunggu Bayar', 'value' => $stats['belum_bayar'], 'color' => 'text-gray-600'],
            ['label' => 'Lunas', 'value' => $stats['lunas'], 'color' => 'text-green-600'],
            ['label' => 'VIP', 'value' => $stats['hutang'], 'color' => 'text-amber-600', 'sub' => 'Rp '.number_format($stats['hutang_nominal'], 0, ',', '.')],
            ['label' => 'Proses Desain', 'value' => $stats['desain'], 'color' => 'text-indigo-600'],
            ['label' => 'Proses Cetak', 'value' => $stats['cetak'], 'color' => 'text-indigo-600'],
            ['label' => 'Proses Finishing', 'value' => $stats['finishing'], 'color' => 'text-indigo-600'],
            ['label' => 'Proses QC', 'value' => $stats['qc'], 'color' => 'text-indigo-600'],
            ['label' => 'Proses Bungkus', 'value' => $stats['bungkus'], 'color' => 'text-indigo-600'],
            ['label' => 'Siap Diambil', 'value' => $stats['siap_diambil'], 'color' => 'text-blue-600'],
            ['label' => 'Selesai', 'value' => $stats['selesai'], 'color' => 'text-green-700'],
            ['label' => 'Telat > 3x24 Jam', 'value' => $stats['telat'], 'color' => 'text-red-600', 'border' => 'border-red-400', 'sub' => 'Belum siap diambil'],
        ];

        $statusBadge = fn (?string $status) => match ($status) {
            'baru' => ['Baru', 'bg-gray-100 text-gray-600'],
            'desain' => ['Desain', 'bg-indigo-50 text-indigo-700'],
            'cetak' => ['Cetak', 'bg-indigo-50 text-indigo-700'],
            'finishing' => ['Finishing', 'bg-indigo-50 text-indigo-700'],
            'qc' => ['QC', 'bg-indigo-50 text-indigo-700'],
            'bungkus' => ['Bungkus', 'bg-indigo-50 text-indigo-700'],
            'siap_diambil' => ['Siap Diambil', 'bg-blue-50 text-blue-700'],
            'selesai' => ['Selesai', 'bg-green-100 text-green-800'],
            'batal' => ['Batal', 'bg-red-50 text-red-700'],
            default => [ucfirst($status ?? '-'), 'bg-gray-100 text-gray-600'],
        };
    @endphp

    <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-2 mb-4">
        @foreach ($cards as $card)
            <div class="bg-white rounded-lg border {{ $card['border'] ?? 'border-gray-200' }} px-3 py-2">
                <p class="text-[11px] text-gray-500 truncate">{{ $card['label'] }}</p>
                <p class="text-lg font-bold {{ $card['color'] }} leading-tight">{{ $card['value'] }}</p>
                @isset($card['sub'])
                    <p class="text-[10px] text-gray-400 truncate">{{ $card['sub'] }}</p>
                @endisset
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Monitoring Order Terbaru</h3>
            <p class="text-xs text-gray-400 mt-0.5">30 order terbaru, file -> kasir → layout → cetak → finishing -> QC → bungkus -> pengambilan.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-[13px] min-w-[1180px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-3 py-2 w-12">No</th>
                        <th class="px-3 py-2">No Order</th>
                        <th class="px-3 py-2">Tipe</th>
                        <th class="px-3 py-2">Customer</th>
                        <th class="px-3 py-2">Masuk</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Bayar</th>
                        <th class="px-3 py-2">Lama Proses</th>
                        <th class="px-2 py-2">Kasir</th>
                        <th class="px-2 py-2">Desain</th>
                        <th class="px-2 py-2">Cetak</th>
                        <th class="px-2 py-2">Finishing</th>
                        <th class="px-2 py-2">QC</th>
                        <th class="px-2 py-2">Bungkus</th>
                        <th class="px-2 py-2">Ambil</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($recent as $row)
                        @php [$statusLabel, $statusClass] = $statusBadge($row['status']); @endphp
                        <tr>
                            <td class="px-3 py-2 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-semibold text-gray-900">{{ $row['no_order'] }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ ['Indoor' => 'IN', 'Outdoor' => 'OUT', 'Artwork' => 'ART'][$row['tipe']] ?? $row['tipe'] }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $row['customer'] }}</td>
                            <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $row['created_at']?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                                @if ($row['progress'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 ml-1" title="Progress unit cetak">
                                        {{ $row['progress'] }}
                                    </span>
                                @endif
                                @if ($row['telat'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 ml-1">Telat</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if ($row['status_bayar'] === 'lunas')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Lunas</span>
                                @elseif ($row['status_bayar'] === 'hutang')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                        Hutang Rp {{ number_format($row['jumlah_piutang'], 0, ',', '.') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Belum Bayar</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-600 whitespace-nowrap">
                                {{ $row['durasi'] ?? '-' }}
                                {{-- @if ($row['selesai']) <span class="text-gray-400">(selesai)</span> @endif --}}
                            </td>
                            <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 90px;" title="{{ $row['kasir'] }}">{{ $row['kasir'] ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 90px;" title="{{ $row['desain_by'] }}">{{ $row['desain_by'] ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 90px;" title="{{ $row['cetak_by'] }}">{{ $row['cetak_by'] ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 90px;" title="{{ $row['finishing_by'] }}">{{ $row['finishing_by'] ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 90px;" title="{{ $row['qc_by'] }}">{{ $row['qc_by'] ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 90px;" title="{{ $row['bungkus_by'] }}">{{ $row['bungkus_by'] ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-gray-600 truncate" style="max-width: 90px;" title="{{ $row['pengambilan_by'] }}">{{ $row['pengambilan_by'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="px-4 py-6 text-center text-gray-400">Belum ada order yang diproses lewat pipeline baru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
