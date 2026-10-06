<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Nota/Invoice per Customer</h2></x-slot>
    <style>
        .customer-invoice-report table { width:100%; border-collapse:collapse; font-size:12px; }
        .customer-invoice-report th,.customer-invoice-report td { border:1px solid #94a3b8; padding:5px 7px; }
        .customer-invoice-report th { background:#e2e8f0; text-align:center; white-space:nowrap; }
        .customer-invoice-report .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 landscape; margin:8mm; }
            body { background:#fff !important; } header,nav,.no-print,body .fixed { display:none !important; } main { padding:0 !important; }
            .customer-invoice-report { padding:0 !important; }
            .customer-invoice-report > div { max-width:none !important; padding:0 !important; }
            .customer-invoice-report section { box-shadow:none !important; padding:0 !important; }
            .customer-invoice-report table { font-family:Arial,sans-serif; font-size:8pt; }
            .customer-invoice-report th,.customer-invoice-report td { padding:3px 4px; }
            .customer-invoice-report thead { display:table-header-group; }
            .customer-invoice-report tr { break-inside:avoid; }
        }
    </style>

    @php
        $statusLabels = ['belum_bayar' => 'Belum Bayar', 'dp' => 'DP', 'hutang' => 'Hutang', 'lunas' => 'Lunas'];
    @endphp

    <div class="customer-invoice-report py-6"><div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="no-print mb-4 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-sm text-gray-700">Customer
                    <select name="customer" required class="mt-1 block min-w-72 rounded-md border-gray-300">
                        <option value="">Pilih customer VIP / reguler</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->KdCust }}" @selected($customerCode === $customer->KdCust)>{{ $customer->NmCust }} — {{ $customer->KdCust }}{{ $customer->isVip ? ' (VIP)' : ' (Reguler)' }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm text-gray-700">Dari tanggal<input type="date" name="dari" value="{{ $from }}" class="mt-1 block rounded-md border-gray-300"></label>
                <label class="text-sm text-gray-700">Sampai tanggal<input type="date" name="sampai" value="{{ $to }}" class="mt-1 block rounded-md border-gray-300"></label>
                <label class="text-sm text-gray-700">Status pembayaran
                    <select name="status_bayar" class="mt-1 block rounded-md border-gray-300">
                        <option value="">Semua status</option>
                        @foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected($paymentStatus === $value)>{{ $label }}</option>@endforeach
                    </select>
                </label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
                <button type="button" onclick="window.print()" @disabled(!$selectedCustomer) class="ml-auto rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40">Cetak</button>
            </form>
        </div>

        <section class="bg-white p-5 shadow-sm">
            <div class="mb-4 text-center">
                <h1 class="text-base font-bold">NOTA / INVOICE PER CUSTOMER</h1>
                <p class="text-sm font-semibold">{{ $selectedCustomer?->NmCust ?? 'Pilih customer terlebih dahulu' }} @if($selectedCustomer) · {{ $selectedCustomer->isVip ? 'VIP' : 'Reguler' }} @endif</p>
                <p class="text-sm">Periode {{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table>
                    <thead><tr><th>Tanggal Order</th><th>Tipe</th><th>Sales Order</th><th>No. Invoice</th><th>Tanggal Invoice</th><th>Status</th><th>Total Nota</th><th>Dibayar</th><th>Sisa Tagihan</th></tr></thead>
                    <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="whitespace-nowrap text-center">{{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td>
                            <td>{{ $row->type }}</td>
                            <td>{{ $row->order }}</td>
                            <td>{{ $row->invoice ?: '-' }}</td>
                            <td class="whitespace-nowrap text-center">{{ $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('d-m-Y H:i') : '-' }}</td>
                            <td>{{ $statusLabels[$row->status] ?? ucfirst((string) $row->status) }}</td>
                            <td class="number">{{ number_format($row->total, 0, ',', '.') }}</td>
                            <td class="number">{{ number_format($row->paid, 0, ',', '.') }}</td>
                            <td class="number {{ $row->remaining > 0 ? 'font-semibold text-red-700' : '' }}">{{ number_format($row->remaining, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-8 text-center text-gray-500">{{ $selectedCustomer ? 'Tidak ada order pada periode dan status yang dipilih.' : 'Pilih customer untuk menampilkan laporan.' }}</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot class="font-bold"><tr><td colspan="6" class="number">Grand Total</td><td class="number">{{ number_format($totals->total,0,',','.') }}</td><td class="number">{{ number_format($totals->paid,0,',','.') }}</td><td class="number">{{ number_format($totals->remaining,0,',','.') }}</td></tr></tfoot>
                </table>
            </div>
        </section>
    </div></div>
</x-app-layout>
