<?php

namespace App\Http\Controllers;

use App\Models\AccountingPurchase;
use App\Models\AccountingPurchasePayment;
use App\Models\AccountingPurchaseReturn;
use App\Models\AccountingPurchaseLine;
use App\Models\AccountingInventoryItem;
use App\Models\AccountingSupplier;
use App\Models\Akun;
use App\Models\PengaturanKeuangan;
use App\Models\PeriodeTutupBuku;
use App\Services\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountingPurchaseController extends Controller
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function index(Request $request): View
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : now()->startOfMonth()->format('Y-m-d');
        $to = $request->filled('to') ? $request->string('to')->toString() : now()->format('Y-m-d');
        $status = $request->string('status')->toString();

        $purchases = AccountingPurchase::query()
            ->with(['supplier', 'payments', 'lines.inventoryItem'])
            ->whereBetween('tanggal', [$from, $to])
            ->when(in_array($status, ['tunai', 'hutang', 'lunas'], true), fn ($query) => $query->where('status', $status))
            ->latest('tanggal')->latest('id')->paginate(30)->withQueryString();

        $summary = (clone AccountingPurchase::query())->whereBetween('tanggal', [$from, $to])
            ->selectRaw("COALESCE(SUM(total), 0) as total, COALESCE(SUM(CASE WHEN status = 'tunai' THEN total ELSE 0 END), 0) as tunai, COALESCE(SUM(jumlah_hutang), 0) as hutang")
            ->first();

        return view('akuntansi.purchases.index', [
            'purchases' => $purchases,
            'suppliers' => AccountingSupplier::where('is_active', true)->orderBy('nama')->get(),
            'accounts' => Akun::where('TipeDK', 'D')->orderBy('NoAkun')->get(),
            'inventoryItems' => AccountingInventoryItem::where('is_active', true)->orderBy('nama')->get(),
            'from' => $from, 'to' => $to, 'status' => $status, 'summary' => $summary,
            'taxRate' => (float) PengaturanKeuangan::current()->tarif_ppn_default,
        ]);
    }

    public function report(Request $request): View
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : now()->startOfMonth()->toDateString();
        $to = $request->filled('to') ? $request->string('to')->toString() : now()->toDateString();
        $classification = $request->string('klasifikasi')->toString();
        $lines = AccountingPurchaseLine::with(['purchase.supplier', 'inventoryItem'])
            ->whereHas('purchase', fn ($query) => $query->whereBetween('tanggal', [$from, $to]))
            ->when(in_array($classification, ['bahan_baku', 'bahan_penolong', 'aset', 'biaya'], true), fn ($query) => $query->where('klasifikasi', $classification))
            ->orderBy('purchase_id')->get()->sortBy(fn ($line) => $line->purchase->tanggal)->values();
        $lines->each(function (AccountingPurchaseLine $line): void {
            $dpp = (float) $line->subtotal;
            $purchaseDpp = (float) $line->purchase->dpp;
            $line->ppn_laporan = $purchaseDpp > 0 ? round($dpp / $purchaseDpp * (float) $line->purchase->ppn) : 0;
            $line->total_laporan = $dpp + $line->ppn_laporan;
        });
        $totals = $lines->groupBy('klasifikasi')->map(fn ($rows) => $rows->sum('subtotal'));
        $summary = (object) [
            'dpp' => $lines->sum('subtotal'),
            'ppn' => $lines->sum('ppn_laporan'),
            'total' => $lines->sum('total_laporan'),
        ];
        return view('akuntansi.purchase-report-v2', compact('from', 'to', 'classification', 'lines', 'totals', 'summary'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('accounting_suppliers', 'id')->where('is_active', true)],
            'tanggal' => ['required', 'date'],
            'nomor_bukti' => ['required', 'string', 'max:50', 'unique:accounting_purchases,nomor_bukti'],
            'keterangan' => ['required', 'string', 'max:255'],
            'akun_pembelian' => ['nullable', Rule::exists('am__', 'NoAkun')->where('TipeDK', 'D')],
            'total' => ['nullable', 'numeric', 'min:1'],
            'kena_ppn' => ['nullable', 'boolean'],
            'metode' => ['required', 'in:tunai,hutang'],
            'cara_bayar' => ['required_if:metode,tunai', 'nullable', 'in:tunai,qris,transfer'],
            'no_referensi' => ['nullable', 'string', 'max:50'],
            'termin_hari' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'tanggal_terima_invoice' => ['nullable', 'date'],
            'lines' => ['nullable', 'array'],
            'lines.*.deskripsi' => ['nullable', 'string', 'max:255'],
            'lines.*.inventory_item_id' => ['nullable', 'exists:accounting_inventory_items,id'],
            'lines.*.klasifikasi' => ['nullable', 'in:bahan_baku,bahan_penolong,aset,biaya'],
            'lines.*.qty' => ['nullable', 'numeric', 'gt:0'],
            'lines.*.satuan' => ['nullable', 'string', 'max:20'],
            'lines.*.harga_satuan' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (PeriodeTutupBuku::isClosed($data['tanggal'])) {
            return back()->withInput()->with('error', 'Periode ini sudah ditutup. Pembelian baru tidak dapat dicatat.');
        }

        $lines = collect($data['lines'] ?? [])->filter(fn ($line) => filled($line['deskripsi'] ?? null))->values();
        if ($lines->isNotEmpty() && $lines->contains(fn ($line) => empty($line['klasifikasi']) || empty($line['qty']) || ! isset($line['harga_satuan']))) return back()->withInput()->with('error', 'Setiap rincian pembelian harus memiliki klasifikasi, jumlah, dan harga satuan.');
        if ($lines->isEmpty() && empty($data['akun_pembelian'])) return back()->withInput()->with('error', 'Pilih akun pembelian atau isi rincian item pembelian.');
        if ($lines->isEmpty() && empty($data['total'])) return back()->withInput()->with('error', 'Total nota wajib diisi.');
        $accountsByClass = ['bahan_baku' => '53000', 'bahan_penolong' => '53003', 'aset' => '12200', 'biaya' => '63014'];
        $lines = $lines->map(function (array $line) use ($accountsByClass) {
            $subtotal = round((float) $line['qty'] * (float) $line['harga_satuan']);
            return $line + ['akun' => $accountsByClass[$line['klasifikasi']], 'subtotal' => $subtotal, 'satuan' => $line['satuan'] ?: 'pcs'];
        });
        $rate = (float) PengaturanKeuangan::current()->tarif_ppn_default;
        $dpp = $lines->isNotEmpty() ? (float) $lines->sum('subtotal') : ($request->boolean('kena_ppn') && $rate > 0 ? round((float) $data['total'] / (1 + $rate / 100)) : round((float) $data['total']));
        $ppn = $request->boolean('kena_ppn') && $rate > 0 ? round($dpp * $rate / 100) : 0;
        $total = $lines->isNotEmpty() ? $dpp + $ppn : round((float) $data['total']);
        $supplier = AccountingSupplier::findOrFail($data['supplier_id']);

        DB::transaction(function () use ($data, $total, $dpp, $ppn, $supplier, $lines) {
            $purchase = AccountingPurchase::create([
                'supplier_id' => $supplier->id,
                'tanggal' => $data['tanggal'], 'nomor_bukti' => $data['nomor_bukti'],
                'keterangan' => $data['keterangan'], 'akun_pembelian' => $lines->first()['akun'] ?? $data['akun_pembelian'],
                'dpp' => $dpp, 'ppn' => $ppn, 'total' => $total,
                'status' => $data['metode'] === 'tunai' ? 'tunai' : 'hutang',
                'cara_bayar' => $data['metode'] === 'tunai' ? $data['cara_bayar'] : null,
                'no_referensi' => $data['no_referensi'] ?? null,
                'termin_hari' => $data['termin_hari'] ?? null,
                'tanggal_terima_invoice' => $data['tanggal_terima_invoice'] ?? null,
                'jumlah_dibayar' => $data['metode'] === 'tunai' ? $total : 0,
                'jumlah_hutang' => $data['metode'] === 'hutang' ? $total : 0,
                'user_id' => auth()->id(),
            ]);

            foreach ($lines as $line) AccountingPurchaseLine::create(['purchase_id' => $purchase->id, 'inventory_item_id' => $line['inventory_item_id'] ?? null, 'deskripsi' => $line['deskripsi'], 'klasifikasi' => $line['klasifikasi'], 'akun' => $line['akun'], 'qty' => $line['qty'], 'satuan' => $line['satuan'], 'harga_satuan' => $line['harga_satuan'], 'subtotal' => $line['subtotal']]);
            $journalLines = $lines->isNotEmpty() ? $lines->groupBy('akun')->map(fn ($rows, $account) => ['akun' => $account, 'debet' => (float) $rows->sum('subtotal')])->values()->all() : [['akun' => $purchase->akun_pembelian, 'debet' => $dpp]];
            if ($ppn > 0) $journalLines[] = ['akun' => AccountingService::AKUN_PPN_MASUKAN, 'debet' => $ppn];
            $journalLines[] = $data['metode'] === 'tunai'
                ? ['akun' => AccountingService::akunKasFor($data['cara_bayar']), 'kredit' => $total]
                : ['akun' => AccountingService::AKUN_HUTANG_DAGANG, 'kredit' => $total, 'kd_bantu' => $supplier->kode_bantu];
            $purchase->update(['no_trans_jurnal' => $this->accounting->post($data['tanggal'], $data['nomor_bukti'], $data['keterangan'], $journalLines)]);
        });

        return redirect()->route('akuntansi.purchases.index')->with('status', 'Pembelian berhasil dicatat dan jurnal dibuat otomatis.');
    }

    public function pay(Request $request, AccountingPurchase $purchase): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'], 'jumlah' => ['required', 'numeric', 'min:1'],
            'cara_bayar' => ['required', 'in:tunai,qris,transfer'],
            'no_referensi' => ['nullable', 'string', 'max:50'],
        ]);
        if ($purchase->jumlah_hutang <= 0) return back()->with('error', 'Nota pembelian ini sudah lunas.');
        if ((float) $data['jumlah'] > (float) $purchase->jumlah_hutang) return back()->withInput()->with('error', 'Nominal pelunasan melebihi sisa hutang supplier.');
        if (PeriodeTutupBuku::isClosed($data['tanggal'])) return back()->withInput()->with('error', 'Periode pelunasan ini sudah ditutup.');

        DB::transaction(function () use ($data, $purchase) {
            $purchase->refresh()->load('supplier');
            $amount = round((float) $data['jumlah']);
            if ($amount > $purchase->jumlah_hutang) abort(422, 'Sisa hutang sudah berubah. Silakan ulangi.');
            $payment = AccountingPurchasePayment::create([
                'purchase_id' => $purchase->id, 'tanggal' => $data['tanggal'], 'jumlah' => $amount,
                'cara_bayar' => $data['cara_bayar'], 'no_referensi' => $data['no_referensi'] ?? null, 'user_id' => auth()->id(),
            ]);
            $noTrans = $this->accounting->post($data['tanggal'], 'BYR-'.$purchase->nomor_bukti, 'Pelunasan '.$purchase->nomor_bukti, [
                ['akun' => AccountingService::AKUN_HUTANG_DAGANG, 'debet' => $amount, 'kd_bantu' => $purchase->supplier->kode_bantu],
                ['akun' => AccountingService::akunKasFor($data['cara_bayar']), 'kredit' => $amount],
            ]);
            $remaining = round($purchase->jumlah_hutang - $amount);
            $purchase->update(['jumlah_dibayar' => $purchase->jumlah_dibayar + $amount, 'jumlah_hutang' => $remaining, 'status' => $remaining <= 0 ? 'lunas' : 'hutang']);
            $payment->update(['no_trans_jurnal' => $noTrans]);
        });

        return back()->with('status', 'Pelunasan hutang supplier berhasil dicatat.');
    }

    public function return(Request $request, AccountingPurchase $purchase): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', 'in:retur,batal'],
            'total' => ['nullable', 'numeric', 'min:1'],
            'nomor_bukti' => ['nullable', 'string', 'max:50', 'unique:accounting_purchase_returns,nomor_bukti'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'cara_refund' => ['nullable', 'in:tunai,qris,transfer'],
            'no_referensi' => ['nullable', 'string', 'max:50'],
        ]);
        if ($data['jenis'] === 'retur' && empty($data['total'])) return back()->withInput()->with('error', 'Nominal retur wajib diisi.');
        if (PeriodeTutupBuku::isClosed($data['tanggal'])) return back()->withInput()->with('error', 'Periode retur ini sudah ditutup.');

        DB::transaction(function () use ($data, $purchase) {
            $purchase->refresh()->load('supplier');
            $available = round($purchase->total - $purchase->jumlah_retur);
            $total = $data['jenis'] === 'batal' ? $available : round((float) $data['total']);
            if ($available <= 0) abort(422, 'Nota ini sudah diretur atau dibatalkan sepenuhnya.');
            if ($total > $available) abort(422, 'Nominal retur melebihi sisa nilai nota yang dapat diretur.');
            $dpp = round($total * $purchase->dpp / $purchase->total);
            $ppn = $total - $dpp;
            $offsetHutang = min($total, $purchase->jumlah_hutang);
            $refund = $total - $offsetHutang;
            $caraRefund = $refund > 0 ? ($data['cara_refund'] ?? $purchase->cara_bayar ?? 'transfer') : null;
            $bukti = $data['nomor_bukti'] ?: $this->nextReturnCode($data['tanggal']);
            $keterangan = $data['keterangan'] ?: ($data['jenis'] === 'batal' ? 'Pembatalan nota pembelian '.$purchase->nomor_bukti : 'Retur pembelian '.$purchase->nomor_bukti);

            $return = AccountingPurchaseReturn::create([
                'purchase_id' => $purchase->id, 'tanggal' => $data['tanggal'], 'nomor_bukti' => $bukti,
                'jenis' => $data['jenis'], 'keterangan' => $keterangan, 'dpp' => $dpp, 'ppn' => $ppn, 'total' => $total,
                'offset_hutang' => $offsetHutang, 'refund' => $refund, 'cara_refund' => $caraRefund,
                'no_referensi' => $data['no_referensi'] ?? null, 'user_id' => auth()->id(),
            ]);
            $lines = [];
            if ($offsetHutang > 0) $lines[] = ['akun' => AccountingService::AKUN_HUTANG_DAGANG, 'debet' => $offsetHutang, 'kd_bantu' => $purchase->supplier->kode_bantu];
            if ($refund > 0) $lines[] = ['akun' => AccountingService::akunKasFor($caraRefund), 'debet' => $refund];
            $lines[] = ['akun' => $purchase->akun_pembelian, 'kredit' => $dpp];
            if ($ppn > 0) $lines[] = ['akun' => AccountingService::AKUN_PPN_MASUKAN, 'kredit' => $ppn];
            $return->update(['no_trans_jurnal' => $this->accounting->post($data['tanggal'], $bukti, $keterangan, $lines)]);

            $newHutang = round($purchase->jumlah_hutang - $offsetHutang);
            $purchase->update([
                'jumlah_retur' => $purchase->jumlah_retur + $total,
                'jumlah_hutang' => $newHutang,
                'jumlah_dibayar' => max(0, $purchase->jumlah_dibayar - $refund),
                'status' => $newHutang <= 0 && $purchase->status === 'hutang' ? 'lunas' : $purchase->status,
            ]);
        });

        return back()->with('status', $data['jenis'] === 'batal' ? 'Nota pembelian dibatalkan dengan jurnal pembalik.' : 'Retur pembelian berhasil dicatat.');
    }

    private function nextReturnCode(string $date): string
    {
        $prefix = 'RT-'.str_replace('-', '', substr($date, 2));
        $count = AccountingPurchaseReturn::where('nomor_bukti', 'like', $prefix.'-%')->count() + 1;
        return $prefix.'-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }
}
