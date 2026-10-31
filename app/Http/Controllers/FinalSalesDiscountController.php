<?php

namespace App\Http\Controllers;

use App\Models\FinalSalesDiscount;
use App\Models\OrderArtwork;
use App\Models\OrderDocument;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\OrderStatusNote;
use App\Models\PengaturanKeuangan;
use App\Models\PeriodeTutupBuku;
use App\Services\AccountingService;
use App\Services\OrderDocumentService;
use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinalSalesDiscountController extends Controller
{
    private const ORDER_MODELS = [
        'indoor' => OrderIndoor::class,
        'outdoor' => OrderOutdoor::class,
        'artwork' => OrderArtwork::class,
    ];

    public function __construct(private readonly AccountingService $accounting) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $from = $filters['dari'] ?? now()->subMonth()->format('Y-m-d');
        $to = $filters['sampai'] ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $search = trim($filters['q'] ?? '');

        $orders = collect();
        foreach (self::ORDER_MODELS as $type => $model) {
            $table = (new $model)->getTable();
            $rows = $model::query()
                ->with('customer')
                // SO DP yang masih bersisa boleh langsung (belum ber-DO/INV).
                // Selain itu wajib sudah ber-DO dan ber-INV: Reguler wajib
                // lunas; VIP boleh lunas atau hutang yang masih di dalam plafon.
                ->where(fn ($q) => $q
                    ->where(fn ($dp) => $dp->where('status_bayar', 'dp')->where('jumlah_piutang', '>', 0))
                    ->orWhere(fn ($dokumen) => $dokumen
                        ->where(fn ($status) => $status
                            ->where(fn ($lunas) => $lunas->where('status_bayar', 'lunas')->where('jumlah_piutang', '<=', 0))
                            ->orWhere(fn ($vip) => $vip
                                ->where('status_bayar', 'hutang')
                                ->where('jumlah_piutang', '>', 0)
                                ->whereNull('hutang_approved_at')
                                ->whereHas('customer.limit', fn ($limit) => $limit
                                    ->whereColumn('customer_limits.Total', '<=', 'customer_limits.Batas'))))
                        ->whereExists($this->adaDokumen($type, $table, 'do'))
                        ->whereExists($this->adaDokumen($type, $table, 'inv'))))
                ->whereNotNull('dibayar_at')
                ->whereBetween('dibayar_at', [$from.' 00:00:00', $to.' 23:59:59'])
                ->where('status', '!=', 'batal')
                ->whereNull('invoice_voided_at')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('NoOrder', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($customer) => $customer->where('NmCust', 'like', "%{$search}%"));
                    });
                })
                ->orderByDesc('dibayar_at')
                ->limit(150)
                ->get();

            $invoiceNumbers = OrderDocument::query()->where('kind', 'inv')->where('order_type', $type)
                ->whereIn('order_id', $rows->pluck('id'))->orderBy('sequence')->pluck('number', 'order_id');

            foreach ($rows as $order) {
                $orders->push((object) [
                    'key' => $type.':'.$order->id,
                    'invoice_number' => $invoiceNumbers[$order->id] ?? null,
                    'invoice_otomatis' => false,
                    'type' => $type,
                    'type_label' => ucfirst($type),
                    'number' => $order->NoOrder,
                    'customer' => $order->customer?->NmCust ?? '-',
                    'paid_at' => $order->dibayar_at,
                    'payment_status' => $order->status_bayar,
                    'initial_amount' => (float) $order->total,
                    'discount_amount' => $order->diskonNominal(),
                    'final_amount' => $order->totalSetelahDiskon(),
                    'paid_amount' => (float) $order->jumlah_dibayar,
                    'receivable_amount' => (float) $order->jumlah_piutang,
                ]);
            }
        }

        $orders = $orders->sortByDesc(fn ($order) => $order->paid_at?->timestamp)->take(200)->values();

        // Cara bayar penerimaan terakhir per nota: potongan pada nota yang
        // sudah dibayar mengurangi penerimaan di cara bayar ini.
        $receiptMethods = $orders->groupBy('type')->flatMap(fn ($rows, $type) => OrderPayment::query()
            ->where('order_type', $type)
            ->whereIn('order_id', $rows->map(fn ($order) => (int) explode(':', $order->key)[1]))
            ->where('jumlah', '>', 0)
            ->orderBy('id')
            ->get(['order_type', 'order_id', 'cara_bayar'])
            ->mapWithKeys(fn ($payment) => [$payment->order_type.':'.$payment->order_id => $payment->cara_bayar]));
        $orders->each(fn ($order) => $order->receipt_method = $receiptMethods[$order->key] ?? 'tunai');

        $history = FinalSalesDiscount::with(['user', 'customer'])
            ->whereBetween('transaction_date', [$from, $to])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('NmCust', 'like', "%{$search}%"));
            }))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        return view('keuangan.final-sales-discounts', compact('orders', 'history', 'from', 'to', 'search'));
    }

    private function adaDokumen(string $type, string $table, string $kind): \Closure
    {
        return fn ($q) => $q->selectRaw('1')->from('order_documents')
            ->where('order_documents.kind', $kind)
            ->where('order_documents.order_type', $type)
            ->whereColumn('order_documents.order_id', $table.'.id');
    }

    private function caraBayarPenerimaan(string $type, int $orderId): string
    {
        return OrderPayment::query()
            ->where('order_type', $type)
            ->where('order_id', $orderId)
            ->where('jumlah', '>', 0)
            ->orderByDesc('id')
            ->value('cara_bayar') ?? 'tunai';
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_key' => ['required', 'regex:/^(indoor|outdoor|artwork):[0-9]+$/'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'discount_amount' => ['required', 'numeric', 'min:100', 'multiple_of:100'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $data['reason'] = trim((string) ($data['reason'] ?? '')) ?: FinalSalesDiscount::DEFAULT_REASON;

        if (PeriodeTutupBuku::isClosed($data['transaction_date'])) {
            return back()->withInput()->with('error', 'Periode potongan penjualan ini sudah ditutup.');
        }

        [$type, $id] = explode(':', $data['order_key'], 2);
        $model = self::ORDER_MODELS[$type];

        DB::transaction(function () use ($data, $type, $id, $model) {
            /** @var Model $order */
            $order = $model::query()->with('customer.limit')->lockForUpdate()->findOrFail((int) $id);

            abort_unless($order->dibayar_at, 422, 'SO belum memiliki transaksi keuangan yang dapat dikoreksi.');

            $isDp = $order->status_bayar === 'dp' && (float) $order->jumlah_piutang > 0;
            $hasDeliveryOrder = OrderDocument::query()->where('kind', 'do')->where('order_type', $type)
                ->where('order_id', $order->id)->exists();
            $hasInvoice = OrderDocument::query()->where('kind', 'inv')->where('order_type', $type)
                ->where('order_id', $order->id)->exists();
            if (! $isDp && (! $hasDeliveryOrder || ! $hasInvoice)) {
                throw ValidationException::withMessages([
                    'discount_amount' => "SO {$order->NoOrder} wajib sudah ber-DO dan ber-INV sebelum diberi Potongan Penjualan Akhir.",
                ]);
            }
            $isVip = (bool) $order->customer?->isVip;
            $isLunas = $order->status_bayar === 'lunas' && (float) $order->jumlah_piutang <= 0;
            $isTagihanVip = $isVip
                && $order->status_bayar === 'hutang'
                && (float) $order->jumlah_piutang > 0
                && ! $order->hutang_approved_at
                && (float) $order->customer->limit->Total <= (float) $order->customer->limit->Batas;
            if ($isTagihanVip && Rupiah::bulatkan((float) $data['discount_amount']) > Rupiah::bulatkan((float) $order->jumlah_piutang)) {
                throw ValidationException::withMessages([
                    'discount_amount' => "Potongan untuk tagihan VIP {$order->NoOrder} maksimal sebesar sisa piutang Rp "
                        .number_format((float) $order->jumlah_piutang, 0, ',', '.').'.',
                ]);
            }
            // Potongan SO DP maksimal sebesar sisa tagihan: uang DP yang sudah
            // diterima tidak dikembalikan lewat potongan.
            if ($isDp && Rupiah::bulatkan((float) $data['discount_amount']) > Rupiah::bulatkan((float) $order->jumlah_piutang)) {
                throw ValidationException::withMessages([
                    'discount_amount' => "Potongan untuk SO DP {$order->NoOrder} maksimal sebesar sisa tagihan Rp "
                        .number_format((float) $order->jumlah_piutang, 0, ',', '.').'.',
                ]);
            }
            if (! $isLunas && ! $isTagihanVip && ! $isDp) {
                $sisa = max(0, (float) $order->jumlah_piutang);
                throw ValidationException::withMessages([
                    'discount_amount' => "Nota {$order->NoOrder} belum lunas"
                        .($sisa > 0 ? ' (sisa tagihan Rp '.number_format($sisa, 0, ',', '.').')' : '')
                        .'. Lunasi dulu sebelum memberi Potongan Penjualan Akhir.',
                ]);
            }
            abort_if($order->status === 'batal' || $order->invoice_voided_at, 422, 'SO batal atau hangus tidak dapat diberi potongan akhir.');

            $initialAmount = Rupiah::bulatkan((float) $order->total);
            $discountBefore = $order->diskonNominal();
            $discountAmount = Rupiah::bulatkan((float) $data['discount_amount']);
            $currentAmount = Rupiah::bulatkan($initialAmount - $discountBefore);

            // Potongan boleh sampai 100% (keputusan manajemen), tapi tidak melebihi nilai nota.
            if ($discountAmount > $currentAmount) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'Potongan tidak boleh melebihi nilai transaksi berjalan Rp '.number_format($currentAmount, 0, ',', '.').'.',
                ]);
            }

            $receivableBefore = max(0, (float) $order->jumlah_piutang);
            $originalPaymentStatus = $order->status_bayar;
            $wasHutang = $originalPaymentStatus === 'hutang';
            $wasDp = $originalPaymentStatus === 'dp';
            $paidBefore = max(0, (float) $order->jumlah_dibayar);
            $receivableOffset = min($discountAmount, $receivableBefore);
            $refundAmount = $discountAmount - $receivableOffset;
            $newReceivable = max(0, $receivableBefore - $receivableOffset);

            // Potongan tidak pernah berupa uang keluar: customer menawar setelah
            // SO tercetak sebagai lunas, lalu membayar lebih sedikit. Bagian yang
            // melebihi piutang mengurangi penerimaan yang sudah tercatat, di cara
            // bayar penerimaan terakhir nota itu (tunai → Kas, QRIS/transfer → Bank).
            $data['refund_method'] = $refundAmount > 0 ? $this->caraBayarPenerimaan($type, $order->id) : null;
            $data['reference_number'] = null;

            $rate = (float) PengaturanKeuangan::current()->tarif_ppn_default;
            $dpp = $rate > 0 ? round($discountAmount / (1 + $rate / 100)) : $discountAmount;
            $tax = $discountAmount - $dpp;
            $finalAmount = $currentAmount - $discountAmount;
            $kdBantu = AccountingService::kodeBantuCustomer($order->customer?->KdCust);

            $adjustment = FinalSalesDiscount::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'order_number' => $order->NoOrder,
                'customer_code' => $order->customer?->KdCust,
                'transaction_date' => $data['transaction_date'],
                'initial_amount' => $initialAmount,
                'discount_before' => $discountBefore,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
                'dpp_adjustment' => $dpp,
                'tax_adjustment' => $tax,
                'receivable_offset' => $receivableOffset,
                'refund_amount' => $refundAmount,
                'refund_method' => $refundAmount > 0 ? $data['refund_method'] : null,
                'reference_number' => $refundAmount > 0 ? ($data['reference_number'] ?? null) : null,
                'reason' => $data['reason'],
                'user_id' => auth()->id(),
            ]);

            $journalNumber = null;
            if ($wasDp) {
                // DP is still a liability (Uang Muka Penjualan), so reducing
                // its remaining operational balance does not touch revenue
                // or receivables yet. If the discount settles the DP in full,
                // recognize the final net sale and refund any excess deposit.
                if ($newReceivable <= 0) {
                    $lines = [
                        ['akun' => AccountingService::AKUN_UANG_MUKA_PENJUALAN, 'debet' => $paidBefore, 'kd_bantu' => $kdBantu],
                        ...$this->accounting->salesCreditLines($finalAmount),
                    ];
                    if ($refundAmount > 0) {
                        $lines[] = ['akun' => AccountingService::akunKasFor($data['refund_method']), 'kredit' => $refundAmount, 'kd_bantu' => $kdBantu];
                    }
                    $journalNumber = $this->accounting->post(
                        $data['transaction_date'], $order->NoOrder, 'Penyelesaian DP via potongan '.$order->NoOrder, $lines
                    );
                }
            } else {
                $creditLines = [];
                if ($receivableOffset > 0) {
                    $creditLines[] = ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'kredit' => $receivableOffset, 'kd_bantu' => $kdBantu];
                }
                if ($refundAmount > 0) {
                    $creditLines[] = ['akun' => AccountingService::akunKasFor($data['refund_method']), 'kredit' => $refundAmount, 'kd_bantu' => $kdBantu];
                }
                $journalNumber = $this->accounting->post(
                    $data['transaction_date'],
                    $order->NoOrder,
                    'Potongan akhir '.$order->NoOrder,
                    [...$this->accounting->finalSalesDiscountDebitLines($discountAmount), ...$creditLines]
                );
            }
            $adjustment->update(['journal_transaction_number' => $journalNumber]);

            if ($refundAmount > 0) {
                OrderPayment::create([
                    'order_type' => $type,
                    'order_id' => $order->id,
                    'jenis' => 'refund',
                    'jumlah' => -$refundAmount,
                    'cara_bayar' => $data['refund_method'],
                    'no_referensi' => $data['reference_number'] ?? null,
                    'user_id' => auth()->id(),
                    'created_at' => Carbon::parse($data['transaction_date'])->setTime(12, 0),
                ]);
            }

            $newPaid = max(0, $paidBefore - $refundAmount);
            $order->update([
                'diskon_akhir_nominal' => (float) ($order->diskon_akhir_nominal ?? 0) + $discountAmount,
                'jumlah_piutang' => $newReceivable,
                'jumlah_dibayar' => min($newPaid, $finalAmount),
                'status_bayar' => $newReceivable <= 0 ? 'lunas' : $order->status_bayar,
            ]);

            if ($receivableOffset > 0 && $order->customer?->limit && $wasHutang) {
                $order->customer->limit->decrement('Total', $receivableOffset);
            }

            // Tagihan VIP yang lunas karena potongan: invoice-nya (terbit bersamaan DO)
            // ditandai lunas di tanggal koreksi ini.
            // SO DP yang lunas karena potongan mendapat invoice seperti pelunasan biasa.
            if (($wasHutang || $wasDp) && $newReceivable <= 0) {
                app(OrderDocumentService::class)->issueInvoice($order->fresh(), $data['transaction_date']);
                app(\App\Services\TanpaProsesService::class)->afterPayment($order->fresh());
            }

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'akuntansi',
                'action' => 'potongan_akhir',
                'catatan' => 'Potongan akhir Rp '.number_format($discountAmount, 0, ',', '.')
                    .($data['reason'] !== FinalSalesDiscount::DEFAULT_REASON ? ' — '.$data['reason'] : ''),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        return back()->with('status', 'Potongan penjualan akhir dan jurnal koreksinya berhasil dicatat.');
    }
}
