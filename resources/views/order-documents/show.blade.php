<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->number }}</title>
    <style>
        :root { --green:#197064; --green-dark:#10564d; --ink:#202c2a; --muted:#667572; --line:#8ca39f; }
        * { box-sizing:border-box; }
        body { margin:0; padding:28px 16px; background:#e9eeed; color:var(--ink); font:12px Arial,Helvetica,sans-serif; }
        .toolbar { width:216mm; max-width:100%; margin:0 auto 12px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
        .toolbar a { color:var(--green-dark); text-decoration:none; }
        .toolbar button { border:0; border-radius:6px; padding:9px 15px; background:var(--green); color:#fff; font-weight:700; cursor:pointer; }
        .sheet { width:216mm; min-height:139mm; max-width:100%; margin:auto; padding:11mm 12mm 8mm; background:#fff; box-shadow:0 3px 20px rgba(28,45,42,.16); }
        .header { display:grid; grid-template-columns:1.15fr .85fr; gap:14mm; align-items:start; }
        .brand { display:flex; align-items:center; gap:10px; color:var(--green); }
        .brand-mark { width:46px; height:46px; border:5px solid currentColor; border-radius:50%; display:grid; place-items:center; font-size:25px; font-weight:900; line-height:1; }
        .brand-name { font-size:27px; font-weight:900; letter-spacing:-1px; line-height:.9; }
        .brand-tagline { margin-top:5px; color:var(--ink); font-size:10px; font-weight:700; letter-spacing:.09em; text-transform:uppercase; }
        .company { margin:9px 0 0; color:var(--muted); line-height:1.45; }
        .document-head { text-align:right; }
        .document-title { margin:0 0 8px; color:var(--green); font-size:24px; font-weight:400; letter-spacing:.05em; text-transform:uppercase; }
        .meta { margin-left:auto; border-collapse:collapse; }
        .meta th,.meta td { padding:2px 0 2px 12px; border:0; text-align:left; vertical-align:top; }
        .meta th { color:var(--green); font-weight:700; white-space:nowrap; }
        .meta td { font-weight:600; overflow-wrap:anywhere; }
        .party { display:grid; grid-template-columns:1fr 1fr; gap:14mm; margin-top:10mm; }
        .label { color:var(--green); font-weight:700; }
        .party p { margin:2px 0; line-height:1.35; }
        .status { display:inline-block; margin-top:5px; padding:3px 8px; border:1px solid var(--green); color:var(--green-dark); font-size:10px; font-weight:700; letter-spacing:.08em; }
        .void { margin:8px 0; padding:7px; border:2px solid #9b2c2c; color:#9b2c2c; font-weight:800; text-align:center; }
        .demo { margin:8px 0; padding:6px 8px; background:#fff7df; color:#74520b; font-size:10px; }
        .items { width:100%; margin-top:8mm; border-collapse:collapse; table-layout:fixed; }
        .items th { padding:5px; border-top:1px solid var(--line); border-bottom:1px solid var(--line); color:var(--green); font-size:10px; text-align:left; }
        .items td { padding:7px 5px; border-bottom:1px solid #d7e0de; vertical-align:top; }
        .items .number { width:29mm; text-align:right; white-space:nowrap; }
        .items .qty { width:15mm; text-align:right; }
        .items .size { width:25mm; text-align:center; }
        .description { font-weight:700; }
        .summary-wrap { display:grid; grid-template-columns:1fr 67mm; gap:12mm; margin-top:6mm; align-items:start; }
        .notes { color:var(--muted); font-size:10px; line-height:1.5; }
        .summary { width:100%; border-collapse:collapse; }
        .summary th,.summary td { padding:3px 0 3px 10px; text-align:right; }
        .summary th { color:var(--green-dark); font-weight:700; }
        .summary .grand th,.summary .grand td { padding-top:7px; border-top:1px solid var(--line); font-size:14px; font-weight:800; }
        .summary .paid th,.summary .paid td { color:var(--green-dark); }
        .footer { display:grid; grid-template-columns:1fr 1fr; gap:14mm; margin-top:8mm; color:var(--muted); font-size:10px; }
        .signature { max-width:52mm; max-height:23mm; object-fit:contain; }
        @media (max-width:760px) {
            body { padding:0; background:#fff; }
            .toolbar { padding:12px; }
            .sheet { width:100%; min-height:0; padding:18px; box-shadow:none; }
            .header,.party,.summary-wrap,.footer { grid-template-columns:1fr; gap:16px; }
            .document-head { text-align:left; }
            .meta { margin:0; }
            .items { font-size:10px; }
        }
        @media print {
            @page { size:216mm 139mm landscape; margin:0; }
            html,body { width:216mm; min-height:139mm; background:#fff; }
            body { padding:0; print-color-adjust:exact; -webkit-print-color-adjust:exact; }
            .toolbar { display:none; }
            .sheet { width:216mm; min-height:139mm; padding:9mm 11mm 7mm; box-shadow:none; }
        }
    </style>
</head>
<body>
    @php
        $snapshot = $document->snapshot;
        $isInvoice = $document->kind === 'inv';
        $companyName = $snapshot['company_name'] ?? $pengaturan->nama_perusahaan ?: 'SPEKTRUM';
        $companyAddress = $snapshot['company_address'] ?? $pengaturan->alamat_perusahaan;
        $companyNpwp = $snapshot['company_npwp'] ?? $pengaturan->npwp_perusahaan;
        $customerAddress = collect([$snapshot['address'] ?? null, $snapshot['city'] ?? null])->filter()->implode(', ');
        $gross = (float) ($snapshot['order_total'] ?? $document->total);
        $total = (float) $document->total;
        $discount = max($gross - $total, 0);
        $paid = (float) ($snapshot['paid'] ?? ($isInvoice ? $total : 0));
        $balance = max((float) ($snapshot['balance'] ?? ($total - $paid)), 0);
        $singleItemFallback = count($snapshot['items'] ?? []) === 1;
        $payment = match ($snapshot['payment_method'] ?? null) {
            'qris' => 'QRIS', 'transfer' => 'Transfer', 'campuran' => 'Campuran',
            'tunai' => 'Tunai', default => $isInvoice ? 'Lunas' : '-',
        };
    @endphp

    <div class="toolbar">
        <a href="{{ route('order-documents.index') }}">← Daftar dokumen</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <main class="sheet">
        <header class="header">
            <section>
                <div class="brand">
                    <div class="brand-mark">S</div>
                    <div>
                        <div class="brand-name">{{ $companyName }}</div>
                        <div class="brand-tagline">Digital Printing &amp; Creative Solution</div>
                    </div>
                </div>
                <p class="company">
                    @if ($companyAddress){{ $companyAddress }}<br>@endif
                    @if ($companyNpwp)NPWP: {{ $companyNpwp }}@endif
                </p>
            </section>

            <section class="document-head">
                <h1 class="document-title">{{ $isInvoice ? 'Invoice' : 'Delivery Order' }}</h1>
                <table class="meta">
                    <tr><th>No.</th><td>{{ $document->number }}</td></tr>
                    <tr><th>Tanggal</th><td>{{ $document->issued_at->translatedFormat('d F Y') }}</td></tr>
                    <tr><th>Sales Order</th><td>{{ $snapshot['sales_order'] ?? '-' }}</td></tr>
                    @if ($isInvoice)<tr><th>Pembayaran</th><td>{{ $payment }}</td></tr>@endif
                </table>
            </section>
        </header>

        @if (($snapshot['origin'] ?? '') === 'historical_demo')
            <div class="demo">Data impor historis untuk demo penyajian transaksi.</div>
        @endif
        @if ($void)<div class="void">DIBATALKAN — DOKUMEN DISIMPAN SEBAGAI RIWAYAT</div>@endif

        <section class="party">
            <div>
                <p class="label">Diterbitkan kepada</p>
                <p><strong>{{ $snapshot['customer'] ?? '-' }}</strong></p>
                <p>{{ $customerAddress ?: '-' }}</p>
                @if ($snapshot['customer_phone'] ?? null)<p>Telp: {{ $snapshot['customer_phone'] }}</p>@endif
                @if ($snapshot['customer_npwp'] ?? null)<p>NPWP: {{ $snapshot['customer_npwp'] }}</p>@endif
            </div>
            <div>
                <p class="label">Status</p>
                <span class="status">{{ $isInvoice ? 'LUNAS' : 'BARANG DISERAHKAN' }}</span>
                @if (! $isInvoice)
                    <p>Penerima: <strong>{{ $snapshot['recipient'] ?? '-' }}</strong></p>
                    <p>Kontak: {{ $snapshot['contact'] ?? '-' }}</p>
                @endif
            </div>
        </section>

        <table class="items">
            <thead>
                <tr>
                    <th>Deskripsi</th><th class="size">Ukuran</th><th class="qty">Qty</th>
                    @if ($isInvoice)<th class="number">Harga</th><th class="number">Subtotal</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse ($snapshot['items'] ?? [] as $item)
                    <tr>
                        <td class="description">{{ $item['description'] ?? '-' }}</td>
                        <td class="size">{{ $item['length'] ?? '-' }} × {{ $item['width'] ?? '-' }}</td>
                        <td class="qty">{{ $item['qty'] ?? 0 }}</td>
                        @if ($isInvoice)
                            @php
                                $unitPrice = $item['unit_price'] ?? ($singleItemFallback && ($item['qty'] ?? 0) > 0 ? $gross / $item['qty'] : null);
                                $lineSubtotal = $item['subtotal'] ?? ($singleItemFallback ? $gross : null);
                            @endphp
                            <td class="number">{{ $unitPrice !== null ? 'Rp '.number_format($unitPrice, 0, ',', '.') : '-' }}</td>
                            <td class="number">{{ $lineSubtotal !== null ? 'Rp '.number_format($lineSubtotal, 0, ',', '.') : '-' }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $isInvoice ? 5 : 3 }}">Tidak ada rincian barang.</td></tr>
                @endforelse
            </tbody>
        </table>

        <section class="summary-wrap">
            <div class="notes">
                @if ($isInvoice)
                    <strong>Catatan:</strong> Barang yang sudah dibeli tidak dapat dikembalikan, kecuali sesuai ketentuan perusahaan.<br>
                    Referensi pembayaran: {{ $snapshot['payment_reference'] ?? '-' }}
                @else
                    <strong>Catatan:</strong> Dokumen ini merupakan bukti penyerahan barang untuk Sales Order tersebut.
                    @if ($signature)<br><img class="signature" src="{{ $signature }}" alt="Tanda tangan penerima">@endif
                @endif
            </div>

            @if ($isInvoice)
                <table class="summary">
                    <tr><th>Subtotal</th><td>Rp {{ number_format($gross, 0, ',', '.') }}</td></tr>
                    @if ($discount > 0)<tr><th>Diskon</th><td>- Rp {{ number_format($discount, 0, ',', '.') }}</td></tr>@endif
                    <tr class="grand"><th>Total</th><td>Rp {{ number_format($total, 0, ',', '.') }}</td></tr>
                    <tr class="paid"><th>Dibayar</th><td>Rp {{ number_format($paid, 0, ',', '.') }}</td></tr>
                    <tr><th>Kurang Bayar</th><td>Rp {{ number_format($balance, 0, ',', '.') }}</td></tr>
                </table>
            @endif
        </section>

        <footer class="footer">
            <div>Kasir: {{ $snapshot['issued_by_name'] ?? $document->issuedBy?->name ?? '-' }}</div>
            <div style="text-align:right">Terima kasih telah mempercayakan kebutuhan cetak Anda kepada kami.</div>
        </footer>
    </main>
</body>
</html>
