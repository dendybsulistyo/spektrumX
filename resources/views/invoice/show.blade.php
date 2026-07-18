<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $order->NoOrder }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            color: #111827;
            background: #f3f4f6;
            margin: 0;
            padding: 32px 16px;
        }
        .card {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 8px 24px rgba(0,0,0,0.06);
            border-top: 4px solid #4f46e5;
            padding: 32px;
        }
        .muted { color: #6b7280; }
        .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; font-weight: 600; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .company-name { font-size: 20px; font-weight: 700; margin: 0; }
        .header .right { text-align: right; }
        .header .right p { margin: 2px 0; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .bill-to { display: flex; justify-content: space-between; align-items: flex-start; }
        .bill-to .customer-name { font-weight: 700; font-size: 15px; margin: 4px 0 2px; }
        .badge {
            display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 999px;
            font-size: 11px; font-weight: 700;
        }
        .badge-lunas { background: #d1fae5; color: #065f46; }
        .badge-hutang { background: #fef3c7; color: #92400e; }
        .badge-belum { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { text-align: left; padding: 10px 8px; }
        thead th { font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; border-bottom: 2px solid #e5e7eb; }
        tbody td { border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .item-name { font-weight: 600; color: #111827; }
        .total-row td { padding-top: 16px; border-bottom: none; }
        .total-label { font-size: 13px; color: #6b7280; }
        .total-amount { font-size: 20px; font-weight: 700; color: #4f46e5; }
        .footer-note { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280; }
        .signature { margin-top: 40px; text-align: right; }
        .signature .line { display: inline-block; min-width: 180px; border-top: 1px solid #9ca3af; margin-top: 48px; padding-top: 4px; }
        .actions { max-width: 720px; margin: 20px auto 0; display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .link-back { color: #6b7280; text-decoration: none; font-size: 13px; }
        .link-back:hover { text-decoration: underline; }
        @media print {
            body { background: #fff; padding: 0; }
            .card { box-shadow: none; border: none; max-width: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    @php
        [$badgeClass, $badgeLabel, $statusNote] = match ($order->status_bayar) {
            'lunas' => ['badge-lunas', 'Lunas', 'Dibayar tunai di kasir.'],
            'hutang' => ['badge-hutang', 'Hutang', 'Piutang berjalan — belum lunas.'],
            default => ['badge-belum', 'Belum Bayar', 'Menunggu pembayaran di kasir.'],
        };
    @endphp

    <div class="card">
        <div class="header">
            <div>
                <p class="company-name">{{ config('app.name', 'SpektrumX') }}</p>
                <p class="muted">Admin Percetakan</p>
            </div>
            <div class="right">
                <p class="label">No Order</p>
                <p style="font-weight:700;">{{ $order->NoOrder }}</p>
                <p class="label" style="margin-top:8px;">Tanggal</p>
                <p>{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('d M Y') }}</p>
            </div>
        </div>

        <hr class="divider">

        <div class="bill-to">
            <div>
                <p class="label">Ditagihkan Kepada</p>
                <p class="customer-name">{{ $order->customer?->NmCust ?? '-' }}</p>
                <p class="muted">{{ $order->customer?->Alamat }}{{ $order->customer?->Kota ? ', '.$order->customer->Kota : '' }}</p>
            </div>
            <div style="text-align:right;">
                <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                <p class="muted" style="margin-top:6px;">{{ $statusNote }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Panjang</th>
                    <th class="text-right">Lebar</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td class="item-name">{{ $item->name }}</td>
                        <td class="text-right">{{ $item->panjang }}</td>
                        <td class="text-right">{{ $item->lebar }}</td>
                        <td class="text-right">{{ $item->qty }}</td>
                        <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4" class="text-right total-label">Total Tagihan</td>
                    <td class="text-right total-amount">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        @if ($order->kasir)
            <div class="signature">
                <span class="line">{{ $order->kasir->name }}</span>
            </div>
        @endif

        <div class="footer-note">
            Terima kasih atas pesanan Anda. Simpan invoice ini sebagai bukti transaksi.
        </div>
    </div>

    <div class="actions no-print">
        <a href="javascript:history.back()" class="link-back">← Kembali</a>
        <button class="btn btn-primary" onclick="window.print()">Print Invoice</button>
    </div>
</body>
</html>
