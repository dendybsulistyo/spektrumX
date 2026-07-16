<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->NoOrder }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #111827; max-width: 700px; margin: 24px auto; padding: 0 16px; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        th { text-transform: uppercase; font-size: 11px; color: #6b7280; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 15px; border-top: 2px solid #111827; border-bottom: none; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111827; padding-bottom: 12px; margin-bottom: 16px; }
        .print-btn { margin-top: 24px; padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ config('app.name', 'SpektrumX') }}</h1>
            <p class="muted">Admin Percetakan</p>
        </div>
        <div class="text-right">
            <p><strong>No Order:</strong> {{ $order->NoOrder }}</p>
            <p class="muted">{{ is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('Y-m-d') }}</p>
        </div>
    </div>

    <p><strong>Customer:</strong> {{ $order->customer?->NmCust ?? '-' }}</p>
    <p class="muted">{{ $order->customer?->Alamat }} {{ $order->customer?->Kota }}</p>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Panjang</th>
                <th class="text-right">Lebar</th>
                <th class="text-right">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->Judul ?? $item->NmFile }}</td>
                    <td class="text-right">{{ $item->Panjang }}</td>
                    <td class="text-right">{{ $item->Lebar }}</td>
                    <td class="text-right">{{ $item->Qty }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3">Total</td>
                <td class="text-right">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top:16px;">
        <strong>Status Pembayaran:</strong>
        {{ match($order->status_bayar) {
            'lunas' => 'Lunas (Tunai)',
            'hutang' => 'Hutang / Piutang',
            default => 'Belum Bayar',
        } }}
    </p>

    <button class="print-btn no-print" onclick="window.print()">Print</button>
</body>
</html>
