<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rekap Order {{ ($recapType ?? 'outdoor') === 'indoor' ? 'Indoor' : 'Outdoor' }} - {{ isset($recapDate) ? \Carbon\Carbon::parse($recapDate)->format('d-m-Y') : $printedAt->format('d-m-Y') }}</title>
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
        .compact th, .compact td { padding-top: 2px; padding-bottom: 2px; line-height: 1.15; }
        .compact .signature { height: 18px; }
        .empty { padding: 28px; text-align: center; color: #666; }
        .actions { max-width: 297mm; margin: 12px auto 0; text-align: right; }
        .date-filter { display: flex; gap: 8px; align-items: center; max-width: 297mm; margin: 0 auto 12px; font-size: 12px; }
        .date-filter input, .date-filter select { padding: 6px 8px; border: 1px solid #bbb; border-radius: 4px; }
        button { border: 0; border-radius: 5px; padding: 9px 16px; color: #fff; background: #172033; font-weight: 700; cursor: pointer; }
        @media print {
            @page { size: A4 landscape; margin: 8mm; }
            body { padding: 0; background: #fff; font-size: 8.5px; }
            .sheet { max-width: none; min-height: auto; padding: 0; box-shadow: none; }
            .actions, .date-filter { display: none; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; page-break-inside: avoid; }
            th, td { padding: 3px 4px; }
        }
    </style>
</head>
@php
    // $recapDate diisi = rekap harian Back Office (per tanggal order); kosong = antrean Bungkus.
    $isDailyRecap = isset($recapDate);
    $isIndoor = ($recapType ?? 'outdoor') === 'indoor';
    // Rekap harian: Keterangan selalu ada & kosong, untuk dicoret operator saat cek fisik.
    $showNotes = $isDailyRecap || ! request()->has('keterangan') || request()->boolean('keterangan');
@endphp
<body class="{{ $showNotes ? '' : 'compact' }}">
    @if ($isDailyRecap)
        <form method="GET" class="date-filter">
            <label>Jenis
                <select name="jenis">
                    <option value="outdoor" @selected(! $isIndoor)>Outdoor</option>
                    <option value="indoor" @selected($isIndoor)>Indoor</option>
                </select>
            </label>
            <label>Tanggal order <input type="date" name="tanggal" value="{{ $recapDate }}"></label>
            <button type="submit">Tampilkan</button>
        </form>
    @endif
    <main class="sheet">
        <div class="heading">
            <div>
                <h1>Rekap Order {{ $isIndoor ? 'Indoor' : 'Outdoor' }}</h1>
                @if ($isDailyRecap)
                    <p>Tanggal: {{ \Carbon\Carbon::parse($recapDate)->format('d-m-Y') }}</p>
                @else
                    <p>Tanggal cetak: {{ $printedAt->format('d-m-Y H:i') }}</p>
                @endif
            </div>
            <p>Total: {{ $items->count() }} item</p>
        </div>

        <table>
            <colgroup>
                @if ($isIndoor)
                    <col style="width: 12%"><col style="width: 15%"><col style="width: 17%"><col style="width: 20%">
                    <col style="width: 8%"><col style="width: 5%"><col style="width: 10%"><col style="width: 7%"><col style="width: 6%">
                @elseif ($isDailyRecap)
                    {{-- Rekap harian: kolom Gabungan sendiri. --}}
                    <col style="width: 11%"><col style="width: 12%"><col style="width: 15%"><col style="width: 8%">
                    <col style="width: 4%"><col style="width: 10%"><col style="width: 12%"><col style="width: 12%">
                    @if ($showNotes)<col style="width: 10%">@endif<col style="width: 6%">
                @elseif ($showNotes)
                    <col style="width: 12%"><col style="width: 14%"><col style="width: 18%"><col style="width: 9%">
                    <col style="width: 5%"><col style="width: 12%"><col style="width: 14%"><col style="width: 11%"><col style="width: 5%">
                @else
                    <col style="width: 13%"><col style="width: 15%"><col style="width: 22%"><col style="width: 10%">
                    <col style="width: 5%"><col style="width: 13%"><col style="width: 17%"><col style="width: 5%">
                @endif
            </colgroup>
            <thead>
                @if ($isIndoor)
                <tr>
                    <th>No. Order</th><th>Customer</th><th>Produk</th><th>Judul</th><th>Ukuran</th>
                    <th>Qty</th><th>Divisi</th><th>Keterangan</th><th>Paraf / TTD</th>
                </tr>
                @else
                <tr>
                    <th>No. Order</th><th>Customer</th><th>Nama File</th><th>Ukuran</th>
                    <th>Qty</th><th>Printer</th><th>Bahan</th>
                    @if ($isDailyRecap)<th>Gabungan</th>@endif
                    @if ($showNotes)<th>Keterangan</th>@endif
                    <th>Paraf / TTD</th>
                </tr>
                @endif
            </thead>
            <tbody>
                @php $previousOrderNumber = null; @endphp
                @forelse ($items as $item)
                    @php
                        $orderNumber = $item->order?->NoOrder;
                        $showOrderNumber = $orderNumber !== $previousOrderNumber;
                        $previousOrderNumber = $orderNumber;
                        $printerCode = $isIndoor ? null : $item->printerCode();
                        $materialCode = $isIndoor ? null : $item->bahanCode();
                        $notes = $isDailyRecap ? '' : collect([$item->jenis_finishing, $item->gabungan])->filter()->join(' · ');
                    @endphp
                    <tr>
                        <td class="number">{{ $showOrderNumber ? ($orderNumber ?? '-') : '' }}</td>
                        <td>{{ $item->order?->customer?->NmCust ? ucwords(mb_strtolower($item->order->customer->NmCust)) : '-' }}</td>
                        @if ($isIndoor)
                        <td>{{ $item->NmProd ?: ($item->produk?->NmProd ?: '-') }}</td>
                        <td>{{ $item->Judul ?: '-' }}</td>
                        <td class="number">{{ (float) $item->Panjang > 0 || (float) $item->Lebar > 0 ? rtrim(rtrim(number_format((float) $item->Panjang, 2, ',', '.'), '0'), ',').' × '.rtrim(rtrim(number_format((float) $item->Lebar, 2, ',', '.'), '0'), ',') : '-' }}</td>
                        <td class="qty">{{ $item->Qty }}</td>
                        <td>{{ $item->divisionName() ?? '-' }}</td>
                        <td class="notes"></td>
                        <td class="notes signature"></td>
                        @else
                        <td>{{ $item->NmFile ?: '-' }}</td>
                        <td class="number">{{ rtrim(rtrim(number_format((float) $item->Panjang, 2, ',', '.'), '0'), ',') }} × {{ rtrim(rtrim(number_format((float) $item->Lebar, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="qty">{{ $isDailyRecap ? $item->Qty : $item->qtyAt('bungkus') }}</td>
                        <td>{{ $printerNames[$printerCode] ?? $printerCode ?? '-' }}</td>
                        <td>{{ $materialNames[$materialCode] ?? $materialCode ?? '-' }}</td>
                        @if ($isDailyRecap)<td>{{ $item->gabungan ?: '' }}</td>@endif
                        @if ($showNotes)<td class="notes">{{ $notes ?: '' }}</td>@endif
                        <td class="notes signature"></td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $isIndoor ? 9 : ($showNotes ? 9 : 8) + ($isDailyRecap ? 1 : 0) }}" class="empty">{{ $isDailyRecap ? 'Tidak ada order '.($isIndoor ? 'Indoor' : 'Outdoor').' pada tanggal ini.' : 'Tidak ada order Outdoor dalam antrean Bungkus.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </main>
    <div class="actions"><button type="button" onclick="window.print()">Cetak</button></div>
</body>
</html>
