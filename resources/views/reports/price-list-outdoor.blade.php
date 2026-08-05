<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Price List Outdoor 2025 — SpektrumX</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.3;
            background: #f5f5f5;
            color: #333;
        }
        .page {
            background: white;
            width: 210mm;
            height: auto;
            margin: 20px auto;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #d32f2f;
            padding-bottom: 10px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #d32f2f;
        }
        .logo {
            font-weight: bold;
            color: #333;
        }
        .section-title {
            background: #f0f0f0;
            padding: 8px 12px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: bold;
            color: #d32f2f;
            border-left: 4px solid #d32f2f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background: #e8e8e8;
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ccc;
            font-size: 11px;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) { background: #fafafa; }
        .price { text-align: right; }
        .tidak-tersedia {
            color: #999;
            font-size: 10px;
            font-style: italic;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        .actions {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }
        .btn {
            padding: 10px 16px;
            background: #1976d2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn:hover { background: #1565c0; }
        .btn-pdf { background: #d32f2f; }
        .btn-pdf:hover { background: #c62828; }

        @media print {
            body { background: white; }
            .page { width: 100%; margin: 0; padding: 0; box-shadow: none; }
            .actions { display: none; }
            .footer { page-break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="actions" id="actions">
        <button class="btn" onclick="window.print()">🖨️ Cetak</button>
        <button class="btn btn-pdf" onclick="downloadPDF()">📄 Download PDF</button>
    </div>

    <div class="page">
        <div class="header">
            <div class="title">PRICE LIST OUTDOOR 2025</div>
            <div class="logo">SPEKTRUM</div>
        </div>

        @foreach ($printers as $printer)
            <div class="section-title">
                {{ $printer->NmPrn }} ({{ $printer->KdPrn }})
            </div>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Bahan</th>
                        @foreach ($tiers as $tier)
                            <th class="price">{{ $tier['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @foreach ($bahans as $bahan)
                        @php
                            $kdCtk = $printer->KdPrn . $bahan->NoCetak;
                            $harga = $hargas->get($kdCtk);
                        @endphp
                        <tr>
                            <td>{{ $no++ }}</td>
                            <td>{{ $bahan->NmBhn }}</td>
                            @foreach ($tiers as $tier)
                                <td class="price">
                                    @if ($harga)
                                        Rp {{ number_format((int)($harga->HargaStd * $tier['multiplier']), 0, ',', '.') }}
                                    @else
                                        <span class="tidak-tersedia">Tidak tersedia</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach

        <div class="footer">
            <p>Update: {{ date('F Y') }} | Harga per meter persegi</p>
        </div>
    </div>

    <script src="{{ asset('vendor/html2pdf/html2pdf.bundle.min.js') }}"></script>
    <script>
        function downloadPDF() {
            const element = document.querySelector('.page');
            const opt = {
                margin: 10,
                filename: 'price-list-outdoor-2025.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { orientation: 'portrait', unit: 'mm', format: 'a4' }
            };
            html2pdf().set(opt).save().from(element).to('pdf').save();
        }
    </script>
</body>
</html>
