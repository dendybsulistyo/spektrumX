<?php

namespace App\Http\Controllers;

use App\Models\OrderFinancialAdjustment;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\OrderStatusNote;
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

class OrderFinancialAdjustmentController extends Controller
{
    private const ORDER_MODELS = [
        'indoor' => OrderIndoor::class,
        'outdoor' => OrderOutdoor::class,
    ];

    public function __construct(private readonly AccountingService $accounting) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);
        $search = trim($filters['q'] ?? '');
        $from = $filters['dari'] ?? now()->subMonth()->format('Y-m-d');
        $to = $filters['sampai'] ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $orders = collect();
        foreach (self::ORDER_MODELS as $type => $model) {
            $model::query()->with('customer')
                ->where('status_bayar', 'dp')
                ->where('jumlah_piutang', '>', 0)
                ->where('status', '!=', 'batal')
                ->whereNull('invoice_voided_at')
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('NoOrder', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('NmCust', 'like', "%{$search}%"));
                }))
                ->latest('dibayar_at')->limit(100)->get()
                ->each(function ($order) use (&$orders, $type) {
                    $order->order_type = $type;
                    $orders->push($order);
                });
        }

        $history = OrderFinancialAdjustment::query()->with(['user', 'customer'])
            ->whereBetween('transaction_date', [$from, $to])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('NmCust', 'like', "%{$search}%"));
            }))
            ->latest('transaction_date')->latest('id')->get();

        return view('keuangan.order-financial-adjustments', compact('orders', 'history', 'search', 'from', 'to'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['amount' => preg_replace('/\D/', '', (string) $request->input('amount'))]);
        $data = $request->validate([
            'order_key' => ['required', 'regex:/^(indoor|outdoor):[0-9]+$/'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'adjustment_type' => ['required', 'in:dp_tambah,dp_refund'],
            'amount' => ['required', 'numeric', 'min:100', 'multiple_of:100'],
            'payment_method' => ['required', 'in:tunai,qris,transfer'],
            'reference_number' => ['nullable', 'string', 'max:50'],
            'reason' => ['required', 'string', 'max:255'],
        ]);
        $data['reason'] = trim($data['reason']);

        if ($data['payment_method'] !== 'tunai' && blank($data['reference_number'])) {
            throw ValidationException::withMessages(['reference_number' => 'Nomor referensi wajib untuk QRIS atau transfer.']);
        }
        if (PeriodeTutupBuku::isClosed($data['transaction_date'])) {
            return back()->withInput()->with('error', 'Periode penyesuaian sudah ditutup.');
        }

        [$type, $id] = explode(':', $data['order_key'], 2);
        $model = self::ORDER_MODELS[$type];

        DB::transaction(function () use ($data, $type, $id, $model): void {
            /** @var Model $order */
            $order = $model::query()->with('customer')->lockForUpdate()->findOrFail((int) $id);
            abort_unless($order->status_bayar === 'dp' && (float) $order->jumlah_piutang > 0, 422, 'Order bukan DP aktif yang dapat disesuaikan.');
            abort_if($order->status === 'batal' || $order->invoice_voided_at, 422, 'Order batal atau nota hangus tidak dapat disesuaikan.');

            $amount = Rupiah::bulatkan((float) $data['amount']);
            $paidBefore = max(0, (float) $order->jumlah_dibayar);
            $receivableBefore = max(0, (float) $order->jumlah_piutang);
            $isAddition = $data['adjustment_type'] === 'dp_tambah';

            if ($isAddition && $amount >= $receivableBefore) {
                throw ValidationException::withMessages(['amount' => 'Tambahan DP harus lebih kecil dari sisa tagihan. Gunakan pelunasan untuk pembayaran penuh.']);
            }
            if (! $isAddition && $amount >= $paidBefore) {
                throw ValidationException::withMessages(['amount' => 'Refund sebagian harus lebih kecil dari DP yang diterima. Untuk refund penuh gunakan Pembatalan Order.']);
            }

            $paidAfter = $isAddition ? $paidBefore + $amount : $paidBefore - $amount;
            $receivableAfter = $isAddition ? $receivableBefore - $amount : $receivableBefore + $amount;
            $kdBantu = AccountingService::kodeBantuCustomer($order->customer?->KdCust);
            $cashAccount = AccountingService::akunKasFor($data['payment_method']);
            $journal = $this->accounting->post(
                $data['transaction_date'],
                $order->NoOrder,
                ($isAddition ? 'Tambahan DP ' : 'Refund DP ').$order->NoOrder,
                $isAddition
                    ? [
                        ['akun' => $cashAccount, 'debet' => $amount, 'kd_bantu' => $kdBantu],
                        ['akun' => AccountingService::AKUN_UANG_MUKA_PENJUALAN, 'kredit' => $amount, 'kd_bantu' => $kdBantu],
                    ]
                    : [
                        ['akun' => AccountingService::AKUN_UANG_MUKA_PENJUALAN, 'debet' => $amount, 'kd_bantu' => $kdBantu],
                        ['akun' => $cashAccount, 'kredit' => $amount, 'kd_bantu' => $kdBantu],
                    ]
            );

            OrderPayment::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'jenis' => $isAddition ? 'dp' : 'refund',
                'jumlah' => $isAddition ? $amount : -$amount,
                'cara_bayar' => $data['payment_method'],
                'no_referensi' => $data['reference_number'] ?: null,
                'user_id' => auth()->id(),
                'created_at' => Carbon::parse($data['transaction_date'])->setTime(12, 0),
            ]);

            $order->update(['jumlah_dibayar' => $paidAfter, 'jumlah_piutang' => $receivableAfter]);

            OrderFinancialAdjustment::create([
                'order_type' => $type, 'order_id' => $order->id, 'order_number' => $order->NoOrder,
                'customer_code' => $order->KdCust, 'transaction_date' => $data['transaction_date'],
                'adjustment_type' => $data['adjustment_type'], 'amount' => $amount,
                'paid_before' => $paidBefore, 'paid_after' => $paidAfter,
                'receivable_before' => $receivableBefore, 'receivable_after' => $receivableAfter,
                'payment_method' => $data['payment_method'], 'reference_number' => $data['reference_number'] ?: null,
                'reason' => $data['reason'], 'user_id' => auth()->id(), 'journal_transaction_number' => $journal,
            ]);

            OrderStatusNote::create([
                'order_type' => $type, 'order_id' => $order->id, 'stage' => 'akuntansi',
                'action' => $data['adjustment_type'],
                'catatan' => ($isAddition ? 'Tambahan DP ' : 'Refund DP ').'Rp '.number_format($amount, 0, ',', '.').' — '.$data['reason'],
                'user_id' => auth()->id(), 'created_at' => now(),
            ]);
        }, attempts: 3);

        return back()->with('status', 'Penyesuaian DP dan jurnal koreksinya berhasil dicatat.');
    }
}
