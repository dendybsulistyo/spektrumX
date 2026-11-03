<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proforma {{ $order->NoOrder }}</title>
    <x-app-favicon />
    <style>
        :root { --green:#197064; --green-dark:#10564d; --ink:#202c2a; --muted:#667572; --line:#8ca39f; }
        * { box-sizing:border-box; }
        body { margin:0; padding:28px 16px; background:#e9eeed; color:var(--ink); font:12px Arial,Helvetica,sans-serif; }
        .toolbar { width:210mm; max-width:100%; margin:0 auto 12px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .toolbar a { color:var(--green-dark); text-decoration:none; }
        .toolbar button { border:0; border-radius:6px; padding:9px 15px; background:var(--green); color:#fff; font-weight:700; cursor:pointer; }
        .sheet { position:relative; width:210mm; min-height:297mm; max-width:100%; margin:auto; padding:14mm 14mm 12mm; background:#fff; box-shadow:0 3px 20px rgba(28,45,42,.16); display:flex; flex-direction:column; }
        .watermark { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; pointer-events:none; color:rgba(25,112,100,.07); font-size:96px; font-weight:900; letter-spacing:.1em; transform:rotate(-24deg); }
        .header { display:grid; grid-template-columns:1.15fr .85fr; gap:10mm; align-items:start; padding-bottom:4mm; border-bottom:1px solid var(--line); }
        .brand { color:var(--green); }
        .brand-name { font-size:27px; font-weight:900; letter-spacing:.08em; line-height:1; }
        .brand-tagline { margin-top:3px; color:var(--ink); font-size:9px; font-weight:700; letter-spacing:.09em; text-transform:uppercase; }
        .company { margin:4px 0 0; color:var(--muted); font-size:10px; line-height:1.3; }
        .document-head { text-align:right; }
        .document-title { margin:0 0 4px; color:var(--green); font-size:23px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }
        .meta { margin-left:auto; border-collapse:collapse; }
        .meta th,.meta td { padding:2px 0 2px 12px; text-align:left; vertical-align:top; }
        .meta th { color:var(--green); font-weight:700; white-space:nowrap; }
        .meta td { font-weight:600; }
        .party { display:grid; grid-template-columns:1.2fr .8fr; gap:10mm; margin-top:5mm; }
        .label { color:var(--green); font-weight:700; }
        .party p { margin:2px 0; line-height:1.35; }
        .status { display:inline-block; margin-top:5px; padding:3px 8px; border:1px solid #9b2c2c; color:#9b2c2c; font-size:10px; font-weight:700; letter-spacing:.08em; }
        .items { width:100%; margin-top:6mm; border-collapse:collapse; }
        .items th { padding:5px; border-top:1px solid var(--line); border-bottom:1px solid var(--line); color:var(--green); font-size:10px; text-align:left; }
        .items td { padding:5px; border-bottom:1px solid #d7e0de; vertical-align:top; }
        .items .number { text-align:right; white-space:nowrap; }
        .description { font-weight:700; }
        .breakdown { margin:2px 0 0; color:var(--muted); font-size:10px; font-weight:400; }
        .summary-wrap { display:grid; grid-template-columns:1fr 72mm; gap:10mm; margin-top:4mm; align-items:start; }
        .notes { color:var(--muted); font-size:10.5px; line-height:1.55; }
        .summary { width:100%; border-collapse:collapse; }
        .summary th,.summary td { padding:3px 0 3px 10px; text-align:right; }
        .summary th { color:var(--green-dark); font-weight:700; }
        .summary .grand th,.summary .grand td { padding-top:7px; border-top:1px solid var(--line); font-size:13px; font-weight:800; }
        .summary .due th,.summary .due td { color:#9b2c2c; font-size:15px; font-weight:800; }
        .signatures { display:grid; grid-template-columns:1fr 1fr; gap:20mm; margin-top:auto; padding-top:12mm; text-align:center; }
        .signatures .line { margin-top:20mm; border-top:1px solid var(--ink); padding-top:3px; }
        .disclaimer { margin-top:6mm; padding-top:3mm; border-top:1px dashed var(--line); color:var(--muted); font-size:9.5px; text-align:center; }
        @media screen and (max-width:760px) {
            body { padding:0; background:#fff; }
            .toolbar { padding:12px; }
            .sheet { width:100%; min-height:0; padding:18px; box-shadow:none; }
            .header,.party,.summary-wrap { grid-template-columns:1fr; gap:16px; }
            .document-head { text-align:left; }
            .meta { margin:0; }
        }
        @media print {
            @page { size:A4; margin:0; }
            body { padding:0; background:#fff; print-color-adjust:exact; -webkit-print-color-adjust:exact; }
            .toolbar { display:none; }
            .sheet { width:210mm; min-height:297mm; margin:0; box-shadow:none; }
        }
    </style>
</head>
<body>
    @php
        $companyName = $pengaturan->nama_perusahaan;
        $customerAddress = collect([$order->customer?->Alamat, $order->customer?->Kota])->filter()->implode(', ');
        $orderDate = $order->TglOrder ? \Illuminate\Support\Carbon::parse($order->TglOrder)->translatedFormat('d F Y') : '-';
    @endphp

    <div class="toolbar">
        <a href="#" onclick="history.length > 1 ? history.back() : window.close(); return false;">← Kembali</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <main class="sheet">
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
                <h1 class="document-title">Proforma Invoice</h1>
                <table class="meta">
                    <tr><th>No.</th><td>PRO/{{ $order->NoOrder }}</td></tr>
                    <tr><th>Tanggal</th><td>{{ now()->translatedFormat('d F Y') }}</td></tr>
                    <tr><th>Sales Order</th><td>{{ $order->NoOrder }}</td></tr>
                    <tr><th>Tgl. Order</th><td>{{ $orderDate }}</td></tr>
                    @if ($invoiceNumber)<tr><th>Invoice</th><td>{{ $invoiceNumber }}</td></tr>@endif
                </table>
            </section>
        </header>

        <section class="party">
            <div>
                <p class="label">Ditagihkan kepada</p>
                <p><strong>{{ $order->customer?->NmCust ?? '-' }}</strong></p>
                <p>{{ $customerAddress ?: '-' }}</p>
                @if ($order->customer?->Telp)<p>Telp: {{ $order->customer->Telp }}</p>@endif
                @if ($order->customer?->NPWP)<p>NPWP: {{ $order->customer->NPWP }}</p>@endif
            </div>
            <div>
                <p class="label">Status</p>
                <span class="status">BELUM LUNAS · TAGIHAN VIP</span>
            </div>
        </section>

        <table class="items">
            <thead>
                <tr><th style="width:8mm;">No</th><th>Deskripsi</th><th style="text-align:center;">Ukuran</th><th class="number">Qty</th><th class="number">Harga</th><th class="number">Subtotal</th></tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="description">{{ $item->name }}
                            @php $detail = collect([$item->bahan ?? null, $item->printer ?? null])->filter()->implode(' / '); @endphp
                            @if ($detail || $item->breakdown)<p class="breakdown">{{ collect([$detail, $item->breakdown])->filter()->implode(' · ') }}</p>@endif
                        </td>
                        <td style="text-align:center; white-space:nowrap;">{{ ($item->panjang || $item->lebar) ? $item->panjang.' × '.$item->lebar : '-' }}</td>
                        <td class="number">{{ $item->qty }}</td>
                        <td class="number">{{ $item->harga_satuan !== null ? 'Rp '.number_format($item->harga_satuan, 0, ',', '.') : '-' }}</td>
                        <td class="number">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Tidak ada rincian barang.</td></tr>
                @endforelse
            </tbody>
        </table>

        <section class="summary-wrap">
            <div class="notes">
                <strong>Catatan:</strong><br>
                Mohon pembayaran mencantumkan No. Sales Order <strong>{{ $order->NoOrder }}</strong>.<br>
                Pembayaran dianggap sah setelah diterima dan dikonfirmasi oleh kasir Spektrum.
            </div>
            <table class="summary">
                <tr><th>Subtotal</th><td>Rp {{ number_format($gross, 0, ',', '.') }}</td></tr>
                @if ($discount > 0)<tr><th>Diskon</th><td>- Rp {{ number_format($discount, 0, ',', '.') }}</td></tr>@endif
                <tr class="grand"><th>Total Tagihan</th><td>Rp {{ number_format($total, 0, ',', '.') }}</td></tr>
                @if ($paid > 0)<tr><th>Sudah Dibayar</th><td>Rp {{ number_format($paid, 0, ',', '.') }}</td></tr>@endif
                <tr class="due"><th>Jumlah Harus Dibayar</th><td>Rp {{ number_format($balance, 0, ',', '.') }}</td></tr>
            </table>
        </section>

        <section class="signatures">
            <div>Hormat kami,<div class="line">{{ auth()->user()->name }}</div></div>
            <div>Diterima oleh,<div class="line">&nbsp;</div></div>
        </section>

        <p class="disclaimer">Proforma Invoice ini adalah dokumen penagihan, <strong>bukan bukti pembayaran dan bukan faktur pajak</strong>. Dicetak {{ now()->format('d-m-Y H:i') }}.</p>
    </main>
</body>
</html>
