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
            ['label' => 'Proses QC', 'value' => $stats['qc'], 'color' => 'text-indigo-600'],
            ['label' => 'Siap Diambil', 'value' => $stats['siap_diambil'], 'color' => 'text-blue-600'],
            ['label' => 'Selesai', 'value' => $stats['selesai'], 'color' => 'text-green-700'],
            ['label' => 'Telat > 3x24 Jam', 'value' => $stats['telat'], 'color' => 'text-red-600', 'border' => 'border-red-400', 'sub' => 'Belum siap diambil'],
        ];

        $statusBadge = fn (?string $status) => match ($status) {
            'baru' => ['Baru', 'bg-gray-100 text-gray-600'],
            'desain' => ['Desain', 'bg-indigo-50 text-indigo-700'],
            'cetak' => ['Cetak', 'bg-indigo-50 text-indigo-700'],
            'qc' => ['QC', 'bg-indigo-50 text-indigo-700'],
            'siap_diambil' => ['Siap Diambil', 'bg-blue-50 text-blue-700'],
            'selesai' => ['Selesai', 'bg-green-100 text-green-800'],
            'batal' => ['Batal', 'bg-red-50 text-red-700'],
            default => [ucfirst($status ?? '-'), 'bg-gray-100 text-gray-600'],
        };
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        @foreach ($cards as $card)
            <div class="bg-white rounded-lg border {{ $card['border'] ?? 'border-gray-200' }} p-4">
                <p class="text-xs text-gray-500">{{ $card['label'] }}</p>
                <p class="text-2xl font-bold {{ $card['color'] }} mt-1">{{ $card['value'] }}</p>
                @isset($card['sub'])
                    <p class="text-xs text-gray-400 mt-0.5">{{ $card['sub'] }}</p>
                @endisset
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Monitoring Order Terbaru</h3>
            <p class="text-xs text-gray-400 mt-0.5">30 order terbaru, layout -> kasir → desain → cetak → QC → pengambilan.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[960px]">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 w-12">No</th>
                        <th class="px-4 py-3">No Order</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Masuk</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Bayar</th>
                        <th class="px-4 py-3">Lama Proses</th>
                        <th class="px-4 py-3">Kasir</th>
                        <th class="px-4 py-3">Desain</th>
                        <th class="px-4 py-3">Cetak</th>
                        <th class="px-4 py-3">QC</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($recent as $row)
                        @php [$statusLabel, $statusClass] = $statusBadge($row['status']); @endphp
                        <tr>
                            <td class="px-4 py-3 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $row['no_order'] }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['tipe'] }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['customer'] }}</td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $row['created_at']?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                                @if ($row['telat'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 ml-1">Telat</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
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
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                {{ $row['durasi'] ?? '-' }}
                                @if ($row['selesai']) <span class="text-gray-400">(selesai)</span> @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['kasir'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['desain_by'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['cetak_by'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $row['qc_by'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-gray-400">Belum ada order yang diproses lewat pipeline baru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
