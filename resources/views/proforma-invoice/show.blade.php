<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proforma {{ $order->NoOrder }}</title>
    <x-app-favicon />
    {{-- Tata letak & setelan cetak sama dengan Invoice (order-documents/show): kertas CForm yang sama. --}}
    <style>
        :root { --green:#197064; --green-dark:#10564d; --ink:#202c2a; --muted:#667572; --line:#8ca39f; }
        * { box-sizing:border-box; }
        body { margin:0; padding:28px 16px; background:#e9eeed; color:var(--ink); font:12px Arial,Helvetica,sans-serif; }
        .toolbar { width:216mm; max-width:100%; margin:0 auto 12px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .toolbar a { color:var(--green-dark); text-decoration:none; }
        .toolbar button { border:0; border-radius:6px; padding:9px 15px; background:var(--green); color:#fff; font-weight:700; cursor:pointer; }
        .sheet { position:relative; width:216mm; min-height:139mm; max-width:100%; margin:auto; padding:8mm 10mm 6mm; background:#fff; box-shadow:0 3px 20px rgba(28,45,42,.16); display:flex; flex-direction:column; overflow:hidden; }
        .sheet + .sheet { margin-top:16px; }
        .watermark { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; pointer-events:none; color:rgba(25,112,100,.07); font-size:72px; font-weight:900; letter-spacing:.1em; transform:rotate(-18deg); }
        .header { display:grid; grid-template-columns:1.15fr .85fr; gap:10mm; align-items:start; padding-bottom:3mm; border-bottom:1px solid var(--line); }
        .brand { color:var(--green); }
        .brand-name { font-size:27px; font-weight:900; letter-spacing:.08em; line-height:1; }
        .brand-tagline { margin-top:3px; color:var(--ink); font-size:9px; font-weight:700; letter-spacing:.09em; text-transform:uppercase; }
        .company { margin:4px 0 0; color:var(--muted); font-size:10px; line-height:1.3; }
        .document-head { text-align:right; }
        .document-title { margin:0 0 4px; color:var(--green); font-size:23px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }
        .proforma-mark { margin:0 0 3px; font-size:13px; font-weight:800; letter-spacing:.12em; }
        .meta { margin-left:auto; border-collapse:collapse; }
        .meta th,.meta td { padding:2px 0 2px 12px; border:0; text-align:left; vertical-align:top; }
        .meta th { color:var(--green); font-weight:700; white-space:nowrap; }
        .meta td { font-weight:600; overflow-wrap:anywhere; }
        .party { display:grid; grid-template-columns:1.2fr .8fr; gap:10mm; margin-top:4mm; }
        .label { color:var(--green); font-weight:700; }
        .party p { margin:2px 0; line-height:1.35; }
        .status { display:inline-block; margin-top:5px; padding:3px 8px; border:1px solid var(--green); color:var(--green-dark); font-size:10px; font-weight:700; letter-spacing:.08em; }
        .items { width:100%; margin-top:4mm; border-collapse:collapse; table-layout:fixed; }
        .items th { padding:4px 5px; border-top:1px solid var(--line); border-bottom:1px solid var(--line); color:var(--green); font-size:10px; text-align:left; }
        .items td { padding:5px; border-bottom:1px solid #d7e0de; vertical-align:top; }
        .items .number { width:29mm; text-align:right; white-space:nowrap; }
        .items .qty { width:15mm; text-align:right; }
        .items .size { width:25mm; text-align:center; }
        .description { font-weight:700; }
        .summary-wrap { display:grid; grid-template-columns:1fr 67mm; gap:10mm; margin-top:3mm; align-items:start; }
        .notes { color:var(--muted); font-size:10px; line-height:1.5; }
        .summary { width:100%; border-collapse:collapse; }
        .summary th,.summary td { padding:3px 0 3px 10px; text-align:right; }
        .summary th { color:var(--green-dark); font-weight:700; }
        .summary .grand th,.summary .grand td { padding-top:7px; border-top:1px solid var(--line); font-size:14px; font-weight:800; }
        .summary .paid th,.summary .paid td { color:var(--green-dark); }
        .page-spacer { flex:1; }
        .footer { display:grid; grid-template-columns:1fr 1fr; gap:10mm; margin-top:auto; padding-top:3mm; color:var(--muted); font-size:10px; }
        @media screen and (max-width:760px) {
            body { padding:0; background:#fff; }
            .toolbar { padding:12px; }
            .sheet { width:100%; min-height:0; padding:18px; box-shadow:none; }
            .header,.party,.summary-wrap,.footer { grid-template-columns:1fr; gap:16px; }
            .document-head { text-align:left; }
            .meta { margin:0; }
            .items { font-size:10px; }
        }
        @media print {
            @page { size:21.6cm 13.9cm; margin:0; }
            html,body { width:21.6cm; min-height:13.9cm; background:#fff; }
            body { padding:0; print-color-adjust:exact; -webkit-print-color-adjust:exact; }
            .toolbar,.watermark { display:none; }
            body,.sheet,.items,.summary,.meta { font-family:"Courier New",Courier,monospace !important; color:#000 !important; }
            .sheet { width:21.6cm; min-height:13.9cm; height:13.9cm; margin:0; padding:6mm 9mm 5mm; box-shadow:none; break-after:page; page-break-after:always; transform:translateY(var(--cform-page-offset, 0mm)); }
            main.sheet:last-of-type { break-after:auto; page-break-after:auto; }
            .brand,.document-title,.label,.meta th,.items th,.status,.summary th { color:#000 !important; }
            .header { padding-bottom:2mm; }
            .party { margin-top:3mm; }
            .items { margin-top:3mm; }
            .items th,.items td { padding:3px 4px; }
            /* Form kontinu sudah tercetak logo, alamat & judul INVOICE: isi blok kanan saja. */
            .header { grid-template-columns:1fr 1fr; border-bottom:0; }
            .header > section:first-child { visibility:hidden; }
            .document-head { text-align:left; padding-left:6mm; }
            .meta { margin-left:0; }
            .document-title { visibility:hidden; }
            .proforma-mark { color:#000 !important; }
            .notes,.footer,.company,.items td,.party p,.summary td { color:#000 !important; }
            .notes,.footer { font-size:11px; font-weight:700; }
            .items th { font-size:11px; font-weight:700; }
            .items td,.description,.summary td { font-size:12px; font-weight:700; }
        }
    </style>
</head>
<body>
    @php
        $companyName = $pengaturan->nama_perusahaan;
        $customerAddress = collect([$order->customer?->Alamat, $order->customer?->Kota])->filter()->implode(', ');
        $itemPages = $items->values()->chunk(5);
        if ($itemPages->isEmpty()) {
            $itemPages = collect([collect()]);
        }
        $totalPages = $itemPages->count();
    @endphp

    <div class="toolbar">
        <a href="#" onclick="history.length > 1 ? history.back() : window.close(); return false;">← Kembali</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    @foreach ($itemPages as $pageIndex => $pageItems)
        @php $isLastPage = $pageIndex === $totalPages - 1; @endphp
    <main class="sheet" style="--cform-page-offset: -{{ number_format($pageIndex * 4.23, 2, '.', '') }}mm;">
        <div class="watermark" aria-hidden="true">PROFORMA</div>
        <header class="header">
            <section>
                <div class="brand">
                    <div class="brand-name">SPEKTRUM</div>
                    <div class="brand-tagline">Digital Printing &amp; Creative Solution</div>
                </div>
                <p class="company">
                    @if ($companyName && mb_strtoupper($companyName) !== 'SPEKTRUM'){{ $companyName }}<br>@endif
                    @if ($pengaturan->alamat_perusahaan){{ $pengaturan->alamat_perusahaan }}<br>@endif
                    @if ($pengaturan->npwp_perusahaan)NPWP: {{ $pengaturan->npwp_perusahaan }}@endif
                </p>
            </section>

            <section class="document-head">
                <h1 class="document-title">Invoice</h1>
                <p class="proforma-mark">PROFORMA INVOICE</p>
                <table class="meta">
                    <tr><th>No.</th><td>PRO/{{ $order->NoOrder }}</td></tr>
                    <tr><th>Tanggal</th><td>{{ $documentDate->translatedFormat('d F Y') }}</td></tr>
                    <tr><th>Sales Order</th><td>{{ $order->NoOrder }}</td></tr>
                    @if ($invoiceNumber)<tr><th>Invoice</th><td>{{ $invoiceNumber }}</td></tr>@endif
                    <tr><th>Pembayaran</th><td>Tagihan VIP</td></tr>
                    <tr><th>Halaman</th><td>{{ $pageIndex + 1 }} / {{ $totalPages }}</td></tr>
                </table>
            </section>
        </header>

        <section class="party">
            <div>
                <p class="label">Diterbitkan kepada</p>
                <p><strong>{{ $order->customer?->NmCust ?? '-' }}</strong></p>
                <p>{{ $customerAddress ?: '-' }}</p>
                @if ($order->customer?->Telp)<p>Telp: {{ $order->customer->Telp }}</p>@endif
                @if ($order->customer?->NPWP)<p>NPWP: {{ $order->customer->NPWP }}</p>@endif
            </div>
            <div>
                <p class="label">Status</p>
                <span class="status">PROFORMA · BELUM LUNAS</span>
            </div>
        </section>

        <table class="items">
            <thead>
                <tr><th>Deskripsi</th><th class="size">Ukuran</th><th class="qty">Qty</th><th class="number">Harga</th><th class="number">Subtotal</th></tr>
            </thead>
            <tbody>
                @forelse ($pageItems as $item)
                    <tr>
                        <td class="description">{{ $item->name }}</td>
                        <td class="size">{{ $item->panjang ?: '-' }} × {{ $item->lebar ?: '-' }}</td>
                        <td class="qty">{{ $item->qty }}</td>
                        <td class="number">{{ $item->harga_satuan !== null ? 'Rp '.number_format($item->harga_satuan, 0, ',', '.') : '-' }}</td>
                        <td class="number">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Tidak ada rincian barang.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($isLastPage)
            <section class="summary-wrap">
                <div class="notes">
                    <strong>Catatan:</strong> Proforma Invoice ini adalah dokumen penagihan, bukan bukti pembayaran dan bukan faktur pajak.
                    Mohon pembayaran mencantumkan No. Sales Order {{ $order->NoOrder }}.
                </div>
                <table class="summary">
                    <tr><th>Subtotal</th><td>Rp {{ number_format($gross, 0, ',', '.') }}</td></tr>
                    @if ($discount > 0)<tr><th>Diskon</th><td>- Rp {{ number_format($discount, 0, ',', '.') }}</td></tr>@endif
                    <tr class="grand"><th>Total</th><td>Rp {{ number_format($total, 0, ',', '.') }}</td></tr>
                    <tr class="paid"><th>Dibayar</th><td>Rp {{ number_format($paid, 0, ',', '.') }}</td></tr>
                    <tr><th>Kurang Bayar</th><td>Rp {{ number_format($balance, 0, ',', '.') }}</td></tr>
                </table>
            </section>
        @else
            <div class="page-spacer"></div>
        @endif

        <footer class="footer">
            <div>Dicetak: {{ auth()->user()->name }} · {{ now()->format('d-m-Y H:i') }}</div>
            <div style="text-align:right">Terima kasih telah mempercayakan kebutuhan cetak Anda kepada kami.</div>
        </footer>
    </main>
    @endforeach
</body>
</html>
