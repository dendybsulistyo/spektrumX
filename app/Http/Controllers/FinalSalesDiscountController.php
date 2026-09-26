<?php

namespace App\Http\Controllers;

use App\Models\FinalSalesDiscount;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\OrderStatusNote;
use App\Models\PengaturanKeuangan;
use App\Models\PeriodeTutupBuku;
use App\Services\AccountingService;
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
            $rows = $model::query()
                ->with('customer')
                ->whereIn('status_bayar', ['lunas', 'dp', 'hutang'])
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

            foreach ($rows as $order) {
                $orders->push((object) [
                    'key' => $type.':'.$order->id,
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_key' => ['required', 'regex:/^(indoor|outdoor|artwork):[0-9]+$/'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'discount_amount' => ['required', 'numeric', 'min:100', 'multiple_of:100'],
            'reason' => ['required', 'string', 'max:255'],
            'refund_method' => ['nullable', 'in:tunai,qris,transfer'],
            'reference_number' => ['nullable', 'string', 'max:50'],
        ]);

        if (PeriodeTutupBuku::isClosed($data['transaction_date'])) {
            return back()->withInput()->with('error', 'Periode potongan penjualan ini sudah ditutup.');
        }

        [$type, $id] = explode(':', $data['order_key'], 2);
        $model = self::ORDER_MODELS[$type];

        DB::transaction(function () use ($data, $type, $id, $model) {
            /** @var Model $order */
            $order = $model::query()->with('customer.limit')->lockForUpdate()->findOrFail((int) $id);

            abort_unless(in_array($order->status_bayar, ['lunas', 'dp', 'hutang'], true) && $order->dibayar_at, 422, 'SO belum memiliki transaksi keuangan yang dapat dikoreksi.');
            abort_if($order->status === 'batal' || $order->invoice_voided_at, 422, 'SO batal atau hangus tidak dapat diberi potongan akhir.');

            $initialAmount = Rupiah::bulatkan((float) $order->total);
            $discountBefore = $order->diskonNominal();
            $discountAmount = Rupiah::bulatkan((float) $data['discount_amount']);
            $currentAmount = Rupiah::bulatkan($initialAmount - $discountBefore);

            if ($discountAmount >= $currentAmount) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'Potongan harus lebih kecil dari nilai transaksi berjalan Rp '.number_format($currentAmount, 0, ',', '.').'.',
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

            if ($refundAmount > 0 && empty($data['refund_method'])) {
                throw ValidationException::withMessages([
                    'refund_method' => 'Pilih cara refund untuk potongan yang mengembalikan uang kepada customer.',
                ]);
            }
            if ($refundAmount > 0 && $data['refund_method'] !== 'tunai' && empty($data['reference_number'])) {
                throw ValidationException::withMessages([
                    'reference_number' => 'Nomor referensi wajib diisi untuk refund QRIS atau transfer.',
                ]);
            }

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

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'akuntansi',
                'action' => 'potongan_akhir',
                'catatan' => 'Potongan akhir Rp '.number_format($discountAmount, 0, ',', '.').' — '.$data['reason'],
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        return back()->with('status', 'Potongan penjualan akhir dan jurnal koreksinya berhasil dicatat.');
    }
}
