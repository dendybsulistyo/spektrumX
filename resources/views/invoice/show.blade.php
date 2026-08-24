<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Pesanan {{ $order->NoOrder }}</title>
    <style>
        :root { --ink:#155f60; --line:#5f7474; --muted:#536b6b; }
        * { box-sizing:border-box; }
        body { margin:0; padding:24px; background:#edf0f0; color:#173b3b; font-family:Arial,Helvetica,sans-serif; font-size:10pt; }
        .sheet { width:21.6cm; min-height:13.9cm; margin:0 auto; background:#fff; box-shadow:0 2px 16px rgba(0,0,0,.14); display:flex; flex-direction:column; overflow:hidden; }
        .sheet + .sheet { margin-top:18px; }
        .invoice-header { height:4.8cm; min-height:4.8cm; display:grid; grid-template-columns:12.6cm 9cm; }
        /* The left two-thirds and the labels in the customer area are
           pre-printed on the continuous form, so this page only supplies
           the transaction values in their matching positions. */
        .preprinted-header-space { min-width:0; }
        .customer-block { padding:.62cm .55cm .3cm .2cm; font-size:9pt; line-height:1.45; }
        .customer-row { display:grid; grid-template-columns:1.85cm 1fr; gap:.12cm; }
        .customer-row .key { color:var(--ink); font-weight:700; white-space:nowrap; }
        .customer-row .value { font-weight:600; overflow-wrap:anywhere; }
        .customer-row.title-row { margin-bottom:.1cm; }
        .order-status { margin:.12cm 0 0 1.97cm; color:#714d00; font-size:8pt; font-weight:700; }
        .page-info { margin:.08cm 0 0 1.97cm; color:var(--muted); font-size:8pt; font-weight:700; }
        .content { flex:1; display:flex; flex-direction:column; padding:.3cm .55cm 0; }
        table { width:100%; border-collapse:collapse; font-size:8pt; }
        thead th { padding:.12cm .1cm; color:var(--ink); border-bottom:1px solid var(--line); font-size:7pt; letter-spacing:.025em; text-align:left; white-space:nowrap; }
        tbody td { padding:.12cm .1cm; border-bottom:1px solid #c9d3d3; vertical-align:top; }
        .text-right { text-align:right; }
        .item-name { font-weight:700; }
        .item-breakdown { margin:.04cm 0 0; color:var(--muted); font-size:6.7pt; font-weight:400; }
        .total-row td { border-bottom:0; padding-top:.18cm; }
        .total-label { color:var(--ink); font-weight:700; }
        .total-amount { color:var(--ink); font-size:10pt; font-weight:800; white-space:nowrap; }
        .payment-summary { align-self:flex-end; width:8.5cm; margin-top:.08cm; font-size:7.5pt; }
        .payment-summary .row { display:flex; justify-content:space-between; gap:.3cm; padding:.03cm 0; }
        .payment-summary .sisa { color:#8b330f; font-weight:700; }
        .spacer { flex:1; min-height:.3cm; }
        .bottom-area { min-height:2.05cm; padding:0 1.5cm .42cm; display:grid; grid-template-columns:1fr 5.2cm 1fr; column-gap:.45cm; break-inside:avoid; page-break-inside:avoid; }
        .bottom-area--dp { min-height:2.7cm; }
        .print-meta { align-self:end; color:var(--muted); font-size:10pt; line-height:1.55; }
        .print-meta .key { display:inline-block; min-width:1.85cm; color:var(--ink); font-weight:700; }
        .dp-breakdown { align-self:end; font-size:9pt; }
        .dp-breakdown .row { display:flex; justify-content:space-between; gap:.45cm; padding:.06cm 0; }
        .dp-breakdown .label { color:var(--ink); font-weight:700; }
        .dp-breakdown .amount { min-width:2.25cm; text-align:right; font-weight:700; }
        .dp-breakdown .balance { border-top:1px solid var(--line); margin-top:.05cm; padding-top:.1cm; }
        .actions { width:21.6cm; margin:14px auto 0; display:flex; justify-content:space-between; align-items:center; }
        .btn { padding:10px 18px; border:0; border-radius:7px; background:var(--ink); color:#fff; cursor:pointer; font-weight:700; text-decoration:none; }
        .link-back { color:#506363; font-size:14px; text-decoration:none; }
        @media print {
            @page { size:21.6cm 13.9cm; margin:0; }
            html, body { width:21.6cm; height:13.9cm; background:#fff; }
            /* Dot-matrix printers reproduce the standard fixed-pitch font
               much more clearly than the styled screen font. This only
               affects the data printed by the application; the existing
               graphic/header on the CForm remains the paper's own print. */
            body, .sheet, table, th, td, .customer-block, .print-meta,
            .dp-breakdown, .payment-summary, .order-status, .page-info {
                font-family:"Courier New", Courier, monospace !important;
                color:#000 !important;
                letter-spacing:0 !important;
                text-shadow:none !important;
            }
            body { padding:0; -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            /* Keep the type large enough for a 9-pin dot-matrix head. */
            .customer-block { font-size:10pt; line-height:1.35; }
            .order-status, .page-info { font-size:8.5pt; }
            table { font-size:8.5pt; line-height:1.2; }
            thead th { font-size:8pt; }
            .item-breakdown { font-size:7.5pt; }
            .total-amount { font-size:10pt; }
            .payment-summary { font-size:8.5pt; }
            .print-meta, .dp-breakdown { font-size:10pt; line-height:1.35; }
            .customer-row .value, .item-name, .total-label,
            .total-amount, .print-meta .key, .dp-breakdown .label,
            .dp-breakdown .amount { font-weight:400 !important; }
            .sheet { width:21.6cm; min-height:13.9cm; height:13.9cm; margin:0; box-shadow:none; break-after:page; page-break-after:always; }
            /* LQ-310 CForm calibration: the tractor feed advances one
               1/6-inch line farther than the printed form from page two.
               Offset each subsequent rendered page cumulatively while
               keeping the first page as the alignment reference. */
            .sheet { transform:translateY(var(--cform-page-offset, 0cm)); }
            main.sheet:last-of-type { break-after:auto; page-break-after:auto; }
            .no-print { display:none !important; }
        }
    </style>
</head>
<body>
    @php
        $caraBayarLabel = match ($order->cara_bayar) {
            'qris' => 'QRIS', 'transfer' => 'Transfer', 'campuran' => 'Campuran', default => 'Tunai',
        };
        $caraBayarNote = $order->cara_bayar ? $caraBayarLabel.($order->no_referensi ? " (Ref: {$order->no_referensi})" : '') : '-';
        $statusNote = $order->invoice_voided_at ? 'HANGUS' : match ($order->status_bayar) {
            'lunas' => 'LUNAS · '.$caraBayarNote, 'hutang' => 'CUSTOMER VIP · BELUM LUNAS',
            'dp' => 'DP · BELUM LUNAS', default => 'BELUM DIBAYAR',
        };
        $diskonStatus = $order->diskonStatus();
        $totalTagihan = $diskonStatus === 'approved' ? $order->totalSetelahDiskon() : (float) ($order->total ?? 0);
        $jumlahPiutang = (float) ($order->jumlah_piutang ?? 0);
        $jumlahDpDibayar = $totalTagihan - $jumlahPiutang;
        $uangMuka = $totalTagihan * 0.5;
        $kurangBayarDp = max($uangMuka - $jumlahDpDibayar, 0);
        $tanggalOrder = is_string($order->TglOrder) ? $order->TglOrder : $order->TglOrder?->format('d-m-Y');
        $customerAddress = trim(collect([$order->customer?->Alamat, $order->customer?->Kota])->filter()->implode(', '));
        // A form sheet has limited usable height. Keep item rows together
        // and repeat the document header on subsequent printed sheets.
        // The DP breakdown is four rows tall. CForm pages need a little more
        // reserved space for it so the last “Kurang Bayar” row never moves
        // on to a separate sheet.
        $itemsPerPage = $order->status_bayar === 'dp' ? 5 : 7;
        $itemPages = $items->chunk($itemsPerPage);
        if ($itemPages->isEmpty()) {
            $itemPages = collect([collect()]);
        }
        $totalPages = $itemPages->count();
    @endphp

    @foreach ($itemPages as $pageIndex => $pageItems)
        @php $isLastPage = $pageIndex === $totalPages - 1; @endphp
    <main class="sheet" style="--cform-page-offset: -{{ number_format($pageIndex * 0.423, 3, '.', '') }}cm;">
        <header class="invoice-header">
            <div class="preprinted-header-space" aria-hidden="true"></div>
            <section class="customer-block">
                <div class="customer-row title-row"><span class="key"></span><span class="value">{{ $tanggalOrder }}</span></div>
                <div class="customer-row"><span class="key"></span><span class="value">{{ $order->customer?->NmCust ?? '-' }}</span></div>
                <div class="customer-row"><span class="key"></span><span class="value">{{ $customerAddress ?: '-' }}</span></div>
                <div class="customer-row"><span class="key"></span><span class="value">{{ $order->NoOrder }}</span></div>
                <div class="customer-row"><span class="key"></span><span class="value">{{ $order->customer?->NPWP ?: '-' }}</span></div>
                <p class="order-status">{{ $statusNote }}</p>
                <p class="page-info">Cetakan ke {{ $pageIndex + 1 }} dari {{ $totalPages }} halaman</p>
            </section>
        </header>

        <section class="content">
            <table>
                <thead><tr><th>Item</th><th>Bahan</th><th>Printer</th><th class="text-right">PJ</th><th class="text-right">LB</th><th class="text-right">Qty</th><th class="text-right">Hrg. Satuan</th><th class="text-right">Subtotal</th></tr></thead>
                <tbody>
                    @foreach ($pageItems as $item)
                        <tr>
                            <td class="item-name">{{ $item->name }} @if ($item->breakdown)<p class="item-breakdown">{{ $item->breakdown }}</p>@endif</td>
                            <td>{{ $item->bahan ?? '-' }}</td><td>{{ $item->printer ?? '-' }}</td>
                            <td class="text-right">{{ $item->panjang }}</td><td class="text-right">{{ $item->lebar }}</td><td class="text-right">{{ $item->qty }}</td>
                            <td class="text-right">{{ $item->harga_satuan !== null ? 'Rp '.number_format($item->harga_satuan, 0, ',', '.') : '-' }}</td>
                            <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    @if ($isLastPage && $diskonStatus === 'approved')
                        <tr><td colspan="7" class="text-right total-label">Subtotal</td><td class="text-right">Rp {{ number_format($order->total ?? 0, 0, ',', '.') }}</td></tr>
                        <tr><td colspan="7" class="text-right total-label">Diskon {{ $order->diskonApprovedLabel() }}</td><td class="text-right">- Rp {{ number_format($order->diskonNominal(), 0, ',', '.') }}</td></tr>
                    @endif
                    <tr class="total-row"><td colspan="7" class="text-right total-label">Total Tagihan</td><td class="text-right total-amount">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
            @if ($isLastPage && $order->replacement_order_id)
                <div class="payment-summary">
                    <div class="row"><span>Nota asal hangus</span><span>{{ $order->replaces?->NoOrder ?? '-' }}</span></div>
                    <div class="row"><span>Kredit nota lama</span><span>Rp {{ number_format($order->replacement_credit ?? 0, 0, ',', '.') }}</span></div>
                    @if (($order->topup_amount ?? 0) > 0)<div class="row sisa"><span>Tambahan pembayaran</span><span>Rp {{ number_format($order->topup_amount, 0, ',', '.') }}</span></div>@endif
                    @if (($order->cashback_amount ?? 0) > 0)<div class="row sisa"><span>Cashback</span><span>Rp {{ number_format($order->cashback_amount, 0, ',', '.') }}</span></div>@endif
                </div>
            @endif
            <div class="spacer"></div>
        </section>

        <section class="bottom-area {{ $isLastPage && $order->status_bayar === 'dp' ? 'bottom-area--dp' : '' }}">
            <div>
                @if ($pageIndex === 0)
                    <div class="print-meta">
                        <div><span class="key">Operator Kasir</span>: {{ $order->kasir?->name ?? '-' }}</div>
                        <div><span class="key">Tanggal Order</span>: {{ $tanggalOrder ?? '-' }}</div>
                    </div>
                @endif
            </div>
            @if ($isLastPage && $order->status_bayar === 'dp')
                <div class="dp-breakdown">
                    <div class="row"><span class="label">Total</span><span class="amount">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</span></div>
                    <div class="row"><span class="label">Uang Muka</span><span class="amount">Rp {{ number_format($uangMuka, 0, ',', '.') }}</span></div>
                    <div class="row"><span class="label">Sudah Bayar</span><span class="amount">Rp {{ number_format($jumlahDpDibayar, 0, ',', '.') }}</span></div>
                    <div class="row balance"><span class="label">Kurang Bayar</span><span class="amount">Rp {{ number_format($kurangBayarDp, 0, ',', '.') }}</span></div>
                </div>
            @endif
        </section>
    </main>
    @endforeach

    <div class="actions no-print" id="standaloneActions">
        <a href="#" id="backLink" class="link-back">← Kembali</a>
        <button class="btn" onclick="window.print()">Cetak Surat Pesanan</button>
    </div>
    <script>
        if (window.self !== window.top) document.getElementById('standaloneActions').style.display = 'none';
        else {
            const backLink = document.getElementById('backLink');
            if (history.length > 1) backLink.addEventListener('click', (event) => { event.preventDefault(); history.back(); });
            else if (document.referrer) backLink.href = document.referrer;
            else backLink.style.display = 'none';
        }
    </script>
</body>
</html>
