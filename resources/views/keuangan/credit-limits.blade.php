<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Rekap Limit Piutang</h2></x-slot>
    <style>
        .credit-limit-report { color:#111827; }
        .credit-limit-report table { width:100%; border-collapse:collapse; font-size:12px; }
        .credit-limit-report th,.credit-limit-report td { border:1px solid #64748b; padding:5px 7px; }
        .credit-limit-report th { background:#e2e8f0; text-align:center; }
        .credit-limit-report .number { text-align:right; white-space:nowrap; }
        @media print {
            @page { size:A4 portrait; margin:12mm; }
            body { background:#fff !important; } header,nav,.no-print { display:none !important; } main { padding:0 !important; }
            .credit-limit-report section { box-shadow:none !important; padding:0 !important; }
            .credit-limit-report table { font-family:Arial,sans-serif; font-size:9pt; }
            .credit-limit-report th,.credit-limit-report td { padding:3px 5px; }
            .credit-limit-report thead { display:table-header-group; }
        }
    </style>
    <div class="credit-limit-report py-6"><div class="mx-auto max-w-4xl px-4 sm:px-6">
        <div class="no-print mb-4 flex justify-end rounded-lg border bg-white p-4 shadow-sm">
            <button type="button" onclick="window.print()" class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white">Cetak</button>
        </div>
        <section class="bg-white p-5 shadow-sm">
            <h1 class="mb-4 text-base font-bold">REKAP LIMIT PIUTANG SPEKTRUM</h1>
            <table>
                <thead><tr><th>CUSTOMER</th><th style="width:25%;">PIUTANG</th><th style="width:25%;">BATAS</th></tr></thead>
                <tbody>@forelse($rows as $row)<tr><td>{{ $row->customer }}</td><td class="number">{{ number_format($row->receivable,0,',','.') }}</td><td class="number">{{ number_format($row->limit,0,',','.') }}</td></tr>@empty<tr><td colspan="3" style="padding:28px;text-align:center;">Belum ada customer yang mempunyai plafon piutang.</td></tr>@endforelse</tbody>
                <tfoot><tr><td class="number"><strong>Total</strong></td><td class="number"><strong>{{ number_format($totalReceivable,0,',','.') }}</strong></td><td class="number"><strong>{{ number_format($totalLimit,0,',','.') }}</strong></td></tr></tfoot>
            </table>
        </section>
    </div></div>
</x-app-layout>
