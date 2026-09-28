<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Order Outdoor - {{ $printedAt->format('d-m-Y') }}</title>
    <x-app-favicon />
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 18px; color: #111; background: #eef1f4; font-family: Arial, sans-serif; font-size: 10px; }
        .sheet { width: 100%; max-width: 297mm; min-height: 190mm; margin: 0 auto; padding: 12mm; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,.12); }
        .heading { display: flex; align-items: end; justify-content: space-between; margin-bottom: 10px; }
        h1 { margin: 0 0 3px; font-size: 14px; text-transform: uppercase; }
        p { margin: 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #888; padding: 4px 5px; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #f2f2f2; font-size: 9px; text-align: left; }
        .number { text-align: center; white-space: nowrap; }
        .qty { text-align: center; }
        .notes { height: 28px; }
        .empty { padding: 28px; text-align: center; color: #666; }
        .actions { max-width: 297mm; margin: 12px auto 0; text-align: right; }
        button { border: 0; border-radius: 5px; padding: 9px 16px; color: #fff; background: #172033; font-weight: 700; cursor: pointer; }
        @media print {
            @page { size: A4 landscape; margin: 8mm; }
            body { padding: 0; background: #fff; font-size: 8.5px; }
            .sheet { max-width: none; min-height: auto; padding: 0; box-shadow: none; }
            .actions { display: none; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; page-break-inside: avoid; }
            th, td { padding: 3px 4px; }
        }
    </style>
</head>
<body>
    @php $showNotes = ! request()->has('keterangan') || request()->boolean('keterangan'); @endphp
    <main class="sheet">
        <div class="heading">
            <div>
                <h1>Rekap Order Outdoor</h1>
                <p>Tanggal cetak: {{ $printedAt->format('d-m-Y H:i') }}</p>
            </div>
            <p>Total: {{ $items->count() }} item</p>
        </div>

        <table>
            <colgroup>
                @if ($showNotes)
                    <col style="width: 12%"><col style="width: 14%"><col style="width: 18%"><col style="width: 9%">
                    <col style="width: 5%"><col style="width: 12%"><col style="width: 14%"><col style="width: 11%"><col style="width: 5%">
                @else
                    <col style="width: 13%"><col style="width: 15%"><col style="width: 22%"><col style="width: 10%">
                    <col style="width: 5%"><col style="width: 13%"><col style="width: 17%"><col style="width: 5%">
                @endif
            </colgroup>
            <thead>
                <tr>
                    <th>No. Order</th><th>Customer</th><th>Nama File</th><th>Ukuran</th>
                    <th>Qty</th><th>Printer</th><th>Bahan</th>
                    @if ($showNotes)<th>Keterangan</th>@endif
                    <th>Paraf / TTD</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    @php
                        $printerCode = $item->printerCode();
                        $materialCode = $item->bahanCode();
                        $notes = collect([$item->jenis_finishing, $item->gabungan])->filter()->join(' · ');
                    @endphp
                    <tr>
                        <td class="number">{{ $item->order?->NoOrder ?? '-' }}</td>
                        <td>{{ $item->order?->customer?->NmCust ? ucwords(mb_strtolower($item->order->customer->NmCust)) : '-' }}</td>
                        <td>{{ $item->NmFile ?: '-' }}</td>
                        <td class="number">{{ rtrim(rtrim(number_format((float) $item->Panjang, 2, ',', '.'), '0'), ',') }} × {{ rtrim(rtrim(number_format((float) $item->Lebar, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="qty">{{ $item->qtyAt('bungkus') }}</td>
                        <td>{{ $printerNames[$printerCode] ?? $printerCode ?? '-' }}</td>
                        <td>{{ $materialNames[$materialCode] ?? $materialCode ?? '-' }}</td>
                        @if ($showNotes)<td class="notes">{{ $notes ?: '' }}</td>@endif
                        <td class="notes"></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $showNotes ? 9 : 8 }}" class="empty">Tidak ada order Outdoor dalam antrean Bungkus.</td></tr>
                @endforelse
            </tbody>
        </table>
    </main>
    <div class="actions"><button type="button" onclick="window.print()">Cetak</button></div>
</body>
</html>
