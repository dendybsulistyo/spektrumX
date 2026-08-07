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
            font-size: 14px;
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
        .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; font-weight: 600; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .company-name { font-size: 22px; font-weight: 700; margin: 0; }
        .header .right { text-align: right; }
        .header .right p { margin: 2px 0; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .bill-to { display: flex; justify-content: space-between; align-items: flex-start; }
        .bill-to .customer-name { font-weight: 700; font-size: 16px; margin: 4px 0 2px; }
        .badge {
            display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 999px;
            font-size: 12px; font-weight: 700;
        }
        .badge-lunas { background: #d1fae5; color: #065f46; }
        .badge-hutang { background: #fef3c7; color: #92400e; }
        .badge-belum { background: #fee2e2; color: #991b1b; }
        .badge-dp { background: #dbeafe; color: #1e40af; }
        .badge-void { background: #fee2e2; color: #991b1b; }
        .dp-summary { margin-top: 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px 16px; }
        .dp-summary .row { display: flex; justify-content: space-between; padding: 2px 0; }
        .dp-summary .row.sisa { font-weight: 700; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { text-align: left; padding: 10px 8px; }
        thead th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af; border-bottom: 2px solid #e5e7eb; }
        tbody td { border-bottom: 1px solid #f3f4f6; }
        .text-right { text-align: right; }
        .item-name { font-weight: 600; color: #111827; }
        .total-row td { padding-top: 16px; border-bottom: none; }
        .total-label { font-size: 14px; color: #6b7280; }
        .total-amount { font-size: 22px; font-weight: 700; color: #4f46e5; }
        .footer-note { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #6b7280; }
        .print-time { margin-top: 6px; font-size: 10px; color: #9ca3af; }
        .signature-row { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature { text-align: center; }
        .signature .line { display: inline-block; min-width: 180px; border-top: 1px solid #9ca3af; margin-top: 48px; padding-top: 4px; }
        .actions { max-width: 720px; margin: 20px auto 0; display: flex; justify-content: space-between; align-items: center; }
        .btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; }
        .link-back { color: #6b7280; text-decoration: none; font-size: 14px; }
        .link-back:hover { text-decoration: underline; }
        @media print {
            @page { size: A5; margin: 10mm; }
            body { background: #fff; padding: 0; font-size: 12px; }
            .card { box-shadow: none; border: none; max-width: none; padding: 0; }
            .no-print { display: none; }
            .company-name { font-size: 18px; }
            .total-amount { font-size: 18px; }
            table { margin-top: 16px; }
            th, td { padding: 6px 4px; }
            .signature-row { margin-top: 24px; }
            .signature .line { margin-top: 32px; }
            .footer-note { margin-top: 20px; padding-top: 10px; }
        }
    </style>
</head>
<body>
    @php
        $caraBayarLabel = match ($order->cara_bayar) {
            'qris' => 'QRIS',
            'transfer' => 'Transfer',
            'campuran' => 'Campuran',
            default => 'Tunai',
        };
        $caraBayarNote = $order->cara_bayar
            ? $caraBayarLabel.($order->no_referensi ? " (Ref: {$order->no_referensi})" : '').' di kasir.'
            : 'di kasir.';

        [$badgeClass, $badgeLabel, $statusNote] = $order->invoice_voided_at ? ['badge-void', 'HANGUS', 'Nota dibatalkan dan tidak berlaku sebagai tagihan.'] : match ($order->status_bayar) {
            'lunas' => ['badge-lunas', 'Lunas', 'Dibayar '.$caraBayarNote],
            'hutang' => ['badge-hutang', 'Hutang', 'Piutang berjalan — belum lunas.'],
            'dp' => ['badge-dp', 'DP (Belum Lunas)', 'Sudah bayar uang muka via '.$caraBayarNote],
            default => ['badge-belum', 'Belum Bayar', 'Menunggu pembayaran di kasir.'],
        };

        $jumlahPiutang = (float) ($order->jumlah_piutang ?? 0);
        $jumlahDpDibayar = ($order->total ?? 0) - $jumlahPiutang;
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

        @if ($order->status_bayar === 'dp')
            <div class="dp-summary">
                <div class="row">
                    <span class="muted">Uang Muka (DP) Dibayar</span>
                    <span>Rp {{ number_format($jumlahDpDibayar, 0, ',', '.') }}</span>
                </div>
                <div class="row sisa">
                    <span>Sisa yang Harus Dilunasi</span>
                    <span>Rp {{ number_format($jumlahPiutang, 0, ',', '.') }}</span>
                </div>
            </div>
        @endif

        @if ($order->replacement_order_id)
            <div class="dp-summary">
                <div class="row"><span class="muted">Nota asal yang hangus</span><span>{{ $order->replaces?->NoOrder ?? '-' }}</span></div>
                <div class="row"><span class="muted">Kredit dari nota lama</span><span>Rp {{ number_format($order->replacement_credit ?? 0, 0, ',', '.') }}</span></div>
                @if (($order->topup_amount ?? 0) > 0)<div class="row sisa"><span>Tambahan pembayaran</span><span>Rp {{ number_format($order->topup_amount, 0, ',', '.') }}</span></div>@endif
                @if (($order->cashback_amount ?? 0) > 0)<div class="row sisa"><span>Cashback</span><span>Rp {{ number_format($order->cashback_amount, 0, ',', '.') }}</span></div>@endif
            </div>
        @endif

        <div class="signature-row">
            <div class="signature">
                <span class="line">Customer</span>
            </div>
            <div class="signature">
                <span class="line">Kasir</span>
            </div>
        </div>

        <div class="footer-note">
            Terima kasih atas pesanan Anda. Simpan invoice ini sebagai bukti transaksi.
            <p class="print-time">Dicetak: {{ now()->translatedFormat('d M Y, H:i') }}</p>
        </div>
    </div>

    <div class="actions no-print" id="standaloneActions">
        <a href="javascript:history.back()" class="link-back">← Kembali</a>
        <button class="btn btn-primary" onclick="window.print()">Print Invoice</button>
    </div>

    <script>
        if (window.self !== window.top) {
            document.getElementById('standaloneActions').style.display = 'none';
        }
    </script>
</body>
</html>
