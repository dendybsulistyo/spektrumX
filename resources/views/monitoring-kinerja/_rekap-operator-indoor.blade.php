{{-- Rekap Operator Indoor: format seperti "Rekap Operator" aplikasi lama,
     kolom Jumlah diganti Qty Order (jumlah nota) per produk. Jumlah Produk =
     banyaknya baris produk pada SO (dasar kinerja Operator File). --}}
<style>
    .operator-recap { color:#111827; }
    .operator-recap h3 { margin:0 0 2px; font-size:15px; font-weight:700; }
    .operator-recap .operator-name { margin:18px 0 6px; font-size:13px; }
    .operator-recap table { width:100%; border-collapse:collapse; font-size:12px; }
    .operator-recap th, .operator-recap td { border:1px solid #475569; padding:3px 8px; }
    .operator-recap th { font-weight:700; text-align:center; background:#fff; }
    .operator-recap .num { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .operator-recap tfoot td { font-weight:700; }
    @media print {
        @page { size:A4 portrait; margin:10mm; }
        header, nav, .no-print { display:none !important; }
        .operator-recap { font-family:Arial, sans-serif; }
        .operator-recap table { font-size:9pt; }
        .operator-recap .operator-block { break-inside:avoid; }
    }
</style>

<div class="operator-recap bg-white rounded-lg border border-gray-200 p-5">
    <h3>REKAP OPERATOR SPEKTRUM</h3>
    <p class="text-sm font-semibold">
        {{ \Carbon\Carbon::parse($from)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($to)->translatedFormat('d F Y') }}
        · Order Indoor
    </p>

    @forelse ($operatorRecap as $operator)
        <div class="operator-block">
            <p class="operator-name">Operator : <strong>{{ $operator->name }}</strong></p>
            <table>
                <colgroup><col style="width:40px"><col><col style="width:100px"><col style="width:130px"><col style="width:110px"><col style="width:130px"></colgroup>
                <thead><tr><th>No</th><th>Produk</th><th>Jumlah Produk</th><th>Subtotal</th><th>Diskon</th><th>Total</th></tr></thead>
                <tbody>
                    @foreach ($operator->rows as $row)
                        <tr>
                            <td class="num">{{ $loop->iteration }}</td>
                            <td>{{ $row->product }}</td>
                            <td class="num">{{ number_format($row->items, 0, ',', '.') }}</td>
                            <td class="num">{{ number_format($row->subtotal, 0, ',', '.') }}</td>
                            <td class="num">{{ number_format($row->discount, 0, ',', '.') }}</td>
                            <td class="num">{{ number_format($row->total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><td colspan="2" class="num">Total</td><td class="num">{{ number_format($operator->totalItems, 0, ',', '.') }}</td><td colspan="2" class="num">Grand Total</td><td class="num">{{ number_format($operator->grandTotal, 0, ',', '.') }}</td></tr></tfoot>
            </table>
        </div>
    @empty
        <p class="py-8 text-center text-sm text-gray-500">Belum ada transaksi order indoor pada periode ini.</p>
    @endforelse
</div>
