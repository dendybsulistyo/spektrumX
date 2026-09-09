<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kwitansi {{ $receiptNumber }}</title>
    <style>
        :root { --ink:#172321; --muted:#596966; --line:#82928f; --accent:#155f60; }
        * { box-sizing:border-box; }
        body { margin:0; padding:24px; background:#e9eeed; color:var(--ink); font:12px Arial,Helvetica,sans-serif; }
        .toolbar { width:21.6cm; max-width:100%; margin:0 auto 12px; display:flex; justify-content:space-between; align-items:center; }
        .toolbar a { color:var(--accent); text-decoration:none; }
        .toolbar button { border:0; border-radius:6px; padding:9px 15px; background:var(--accent); color:#fff; font-weight:700; cursor:pointer; }
        .receipt { width:21.6cm; min-height:10cm; margin:auto; padding:8mm 10mm 7mm; background:#fff; box-shadow:0 3px 20px rgba(28,45,42,.16); }
        .header { display:grid; grid-template-columns:72mm 1fr; gap:10mm; padding-bottom:4mm; border-bottom:1px solid var(--line); }
        .brand-name { color:var(--accent); font-size:25px; font-weight:900; letter-spacing:.12em; }
        .brand-tagline { margin-top:2px; font-size:9px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; }
        .company { margin:5px 0 0; color:var(--muted); font-size:9px; line-height:1.4; }
        h1 { margin:0 0 6px; color:var(--accent); font-size:23px; text-align:right; letter-spacing:.12em; }
        .meta { width:100%; border-collapse:collapse; }
        .meta th,.meta td { padding:2px 0 2px 8px; text-align:left; vertical-align:top; }
        .meta th { width:30mm; color:var(--accent); white-space:nowrap; }
        .body { padding-top:5mm; }
        .field { display:grid; grid-template-columns:43mm 4mm 1fr; gap:2mm; margin-bottom:3mm; align-items:start; }
        .field-label { color:var(--muted); font-weight:700; }
        .field-value { min-height:5mm; border-bottom:1px dotted var(--line); font-weight:600; line-height:1.45; }
        .words { font-style:italic; text-transform:capitalize; }
        .bottom { display:grid; grid-template-columns:1fr 66mm; gap:12mm; margin-top:5mm; align-items:end; }
        .amount { border:1px solid var(--line); padding:4mm 6mm; font-size:19px; font-weight:800; white-space:nowrap; }
        .signature { text-align:center; line-height:1.5; }
        .signature-space { height:15mm; }
        .signer { border-top:1px solid var(--line); padding-top:2mm; font-weight:700; }
        .reference { margin-top:3mm; color:var(--muted); font-size:9px; }
        @media (max-width:760px) {
            body { padding:0; background:#fff; }
            .toolbar { padding:12px; }
            .receipt { width:100%; min-height:0; padding:18px; box-shadow:none; }
            .header,.bottom { grid-template-columns:1fr; }
            h1 { text-align:left; }
        }
        @media print {
            @page { size:21.6cm 10cm; margin:0; }
            html,body { width:21.6cm; height:10cm; background:#fff; }
            body { padding:0; print-color-adjust:exact; -webkit-print-color-adjust:exact; }
            body,.receipt,.meta { color:#000 !important; font-family:"Courier New",Courier,monospace !important; }
            .toolbar { display:none; }
            .receipt { width:21.6cm; min-height:10cm; height:10cm; margin:0; padding:6mm 9mm 5mm; box-shadow:none; overflow:hidden; }
            .brand-name,h1,.meta th,.field-label { color:#000 !important; }
        }
    </style>
</head>
<body>
    @php
        $total = (float) $document->total;
        $companyName = $snapshot['company_name'] ?? $pengaturan->nama_perusahaan ?: 'CV Spektrum Digital Artwork';
        $companyAddress = $snapshot['company_address'] ?? $pengaturan->alamat_perusahaan;
        $companyNpwp = $snapshot['company_npwp'] ?? $pengaturan->npwp_perusahaan;
        $payment = match ($snapshot['payment_method'] ?? null) {
            'qris' => 'QRIS', 'transfer' => 'Transfer', 'campuran' => 'Campuran', 'tunai' => 'Tunai', default => 'Lunas',
        };
        $reference = $snapshot['payment_reference'] ?? null;
    @endphp

    <div class="toolbar">
        <a href="{{ route('order-documents.show', $document) }}">← Kembali ke Invoice</a>
        <button type="button" onclick="window.print()">Cetak Kwitansi</button>
    </div>

    <main class="receipt">
        <header class="header">
            <section>
                <div class="brand-name">SPEKTRUM</div>
                <div class="brand-tagline">Digital Printing Studio</div>
                <p class="company">
                    {{ $companyName }}<br>
                    @if ($companyAddress){{ $companyAddress }}<br>@endif
                    @if ($companyNpwp)NPWP: {{ $companyNpwp }}@endif
                </p>
            </section>
            <section>
                <h1>KWITANSI</h1>
                <table class="meta">
                    <tr><th>No.</th><td>{{ $receiptNumber }}</td></tr>
                    <tr><th>Tanggal</th><td>{{ $document->issued_at->translatedFormat('d F Y') }}</td></tr>
                    <tr><th>Invoice</th><td>{{ $document->number }}</td></tr>
                </table>
            </section>
        </header>

        <section class="body">
            <div class="field">
                <span class="field-label">Telah terima dari</span><span>:</span>
                <span class="field-value">{{ $snapshot['customer'] ?? '-' }}</span>
            </div>
            <div class="field">
                <span class="field-label">Banyaknya uang</span><span>:</span>
                <span class="field-value words"># {{ \App\Support\Terbilang::rupiah($total) }} #</span>
            </div>
            <div class="field">
                <span class="field-label">Untuk pembayaran</span><span>:</span>
                <span class="field-value">Pelunasan Sales Order {{ $snapshot['sales_order'] ?? '-' }}</span>
            </div>

            <div class="bottom">
                <div>
                    <div class="amount">Terbilang Rp {{ number_format($total, 0, ',', '.') }},-</div>
                    <div class="reference">Pembayaran: {{ $payment }}@if ($reference) · Referensi: {{ $reference }}@endif</div>
                </div>
                <div class="signature">
                    <div>Yogyakarta, {{ $document->issued_at->translatedFormat('d F Y') }}</div>
                    <div class="signature-space"></div>
                    <div class="signer">{{ $snapshot['issued_by_name'] ?? $document->issuedBy?->name ?? 'Kasir' }}</div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
