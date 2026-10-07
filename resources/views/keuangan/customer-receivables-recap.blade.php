<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Piutang Customer</h2></x-slot>
    <style>
        .receivable-recap { color:#111827; }
        .receivable-recap table { width:100%; border-collapse:collapse; font-size:12px; }
        .receivable-recap th,.receivable-recap td { border:1px solid #64748b; padding:5px 7px; }
        .receivable-recap th { background:#e2e8f0; text-align:center; }
        .receivable-recap .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 portrait; margin:12mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .receivable-recap section { box-shadow:none !important; padding:0 !important; }
            .receivable-recap table { font-family:Arial,sans-serif; font-size:9pt; }
            .receivable-recap th,.receivable-recap td { padding:3px 5px; }
            .receivable-recap thead { display:table-header-group; }
            .receivable-recap tr { break-inside:avoid; }
        }
    </style>
    <div class="receivable-recap py-6"><div class="mx-auto max-w-5xl px-4 sm:px-6">
        <div class="no-print mb-4 flex flex-wrap items-end justify-between gap-3 rounded-lg border bg-white p-4 shadow-sm">
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-sm text-gray-700">Sampai tanggal
                    <input type="date" name="tanggal" value="{{ $asOf }}" class="mt-1 block rounded-md border-gray-300">
                </label>
                <label class="text-sm text-gray-700">Customer
                    <select name="jenis" class="mt-1 block rounded-md border-gray-300">
                        <option value="semua" @selected($segment === 'semua')>Semua (VIP &amp; Reguler)</option>
                        <option value="vip" @selected($segment === 'vip')>VIP saja</option>
                        <option value="reguler" @selected($segment === 'reguler')>Reguler saja</option>
                    </select>
                </label>
                <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
            </form>
            <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak</button>
        </div>
        <section class="bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h1 class="text-base font-bold">REKAP PIUTANG CUSTOMER SPEKTRUM{{ ['vip' => ' - VIP', 'reguler' => ' - REGULER'][$segment] ?? '' }}</h1>
                <p class="text-sm font-semibold">Sampai Tanggal : {{ \Carbon\Carbon::parse($asOf)->translatedFormat('d F Y') }}</p>
            </div>
            <table>
                <thead><tr><th>Customer</th><th style="width:19%;">Piutang</th><th style="width:15%;">Discount</th><th style="width:17%;">Bayar</th><th style="width:19%;">Sisa Piutang</th></tr></thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>
                            @if ($row['code'] && $row['has_vip'])
                                <a class="text-indigo-700 hover:underline" href="{{ route('keuangan.customer-receivable-details', ['customer' => $row['code']]) }}">{{ $row['customer'] }}</a>
                            @else
                                {{ $row['customer'] }}
                            @endif
                        </td>
                        <td class="number">{{ number_format($row['receivable'], 0, ',', '.') }}</td>
                        <td class="number">{{ $row['discount'] > 0 ? number_format($row['discount'], 0, ',', '.') : '' }}</td>
                        <td class="number">{{ number_format($row['paid'], 0, ',', '.') }}</td>
                        <td class="number">{{ number_format($row['remaining'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="padding:28px;text-align:center;">Tidak ada piutang customer sampai tanggal ini.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr><td colspan="4" class="number"><strong>Total Piutang</strong></td><td class="number"><strong>{{ number_format($totals->remaining, 0, ',', '.') }}</strong></td></tr>
                </tfoot>
            </table>
        </section>
    </div></div>
</x-app-layout>
