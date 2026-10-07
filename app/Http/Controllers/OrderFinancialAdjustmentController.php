<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderDocument;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\OrderStatusNote;
use App\Models\PaymentMethodCorrection;
use App\Models\PeriodeTutupBuku;
use App\Services\AccountingService;
use App\Services\OrderDocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderFinancialAdjustmentController extends Controller
{
    private const ORDER_MODELS = [
        'indoor' => OrderIndoor::class,
        'outdoor' => OrderOutdoor::class,
        'artwork' => OrderArtwork::class,
    ];

    public function __construct(
        private readonly AccountingService $accounting,
        private readonly OrderDocumentService $documents,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'tanggal' => ['nullable', 'date'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);
        $search = trim($filters['q'] ?? '');
        $date = $filters['tanggal'] ?? null;
        $from = $filters['dari'] ?? now()->subMonth()->format('Y-m-d');
        $to = $filters['sampai'] ?? now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $rows = collect();
        if ($search !== '' || $date) {
            foreach (self::ORDER_MODELS as $type => $model) {
                $orders = $model::query()
                    ->with('customer')
                    ->whereIn('status_bayar', ['dp', 'lunas'])
                    ->where('status', '!=', 'batal')
                    ->whereNull('invoice_voided_at')
                    ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search, $type): void {
                        $table = $query->getModel()->getTable();
                        $query->where('NoOrder', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($customer) => $customer->where('NmCust', 'like', "%{$search}%"))
                            ->orWhereExists(fn ($document) => $document->selectRaw('1')
                                ->from('order_documents')
                                ->whereColumn('order_documents.order_id', $table.'.id')
                                ->where('order_documents.order_type', $type)
                                ->where('order_documents.kind', 'inv')
                                ->where('order_documents.number', 'like', "%{$search}%"));
                    }))
                    ->when($date, fn ($query) => $query->whereExists(fn ($payment) => $payment->selectRaw('1')
                        ->from('order_payments')
                        ->whereColumn('order_payments.order_id', $query->getModel()->getTable().'.id')
                        ->where('order_payments.order_type', $type)
                        ->where('order_payments.jumlah', '>', 0)
                        ->whereDate('order_payments.created_at', $date)))
                    ->latest('TglOrder')
                    ->limit(100)
                    ->get();
                if ($orders->isEmpty()) {
                    continue;
                }

                $invoices = OrderDocument::query()
                    ->where('order_type', $type)->where('kind', 'inv')->whereIn('order_id', $orders->pluck('id'))
                    ->orderByDesc('sequence')->get(['order_id', 'number'])->unique('order_id')->pluck('number', 'order_id');
                $payments = OrderPayment::query()
                    ->where('order_type', $type)->whereIn('order_id', $orders->pluck('id'))
                    ->where('jumlah', '>', 0)
                    ->when($date, fn ($query) => $query->whereDate('created_at', $date))
                    ->oldest('created_at')->oldest('id')->get();

                foreach ($payments as $payment) {
                    $order = $orders->firstWhere('id', $payment->order_id);
                    $payment->customer_name = $order->customer?->NmCust ?: '-';
                    $payment->order_number = $order->NoOrder;
                    // Pembayaran DP merujuk ke SO; selain itu ke invoice.
                    $payment->document_number = $payment->jenis === 'dp'
                        ? $this->documents->number($order, 'so')
                        : ($invoices[$order->id] ?? $this->documents->number($order, 'so'));
                    $rows->push($payment);
                }
            }

            $rows = $rows->sortBy(fn ($payment) => $payment->created_at?->timestamp)->values();
        }

        $history = PaymentMethodCorrection::query()->with('user')
            ->whereBetween('correction_date', [$from, $to])
            ->latest('correction_date')->latest('id')->limit(100)->get();

        return view('keuangan.order-financial-adjustments', compact('rows', 'history', 'search', 'date', 'from', 'to'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'payments' => ['required', 'array'],
            'payments.*.method' => ['nullable', 'in:debit,qris,transfer'],
            'payments.*.reference' => ['nullable', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:100'],
            'tanggal' => ['nullable', 'date'],
        ]);
        $correctionDate = now()->format('Y-m-d');
        if (PeriodeTutupBuku::isClosed($correctionDate)) {
            return back()->withInput()->with('error', 'Periode koreksi metode pembayaran sudah ditutup.');
        }

        $changes = [];
        foreach ($request->input('payments') as $paymentId => $input) {
            // Kosong = tidak diganti; metode kasir tetap.
            $method = $input['method'] ?? null;
            if (! $method) {
                continue;
            }
            $reference = trim((string) ($input['reference'] ?? '')) ?: null;
            $payment = OrderPayment::query()->find((int) $paymentId);
            if (! $payment || ($payment->cara_bayar === $method && ($payment->no_referensi ?: null) === $reference)) {
                continue;
            }
            if (in_array($method, ['qris', 'transfer'], true) && $reference === null) {
                throw ValidationException::withMessages(["payments.{$paymentId}.reference" => 'No. referensi wajib diisi untuk QRIS/Transfer.']);
            }
            $changes[] = [
                'payment_id' => (int) $paymentId,
                'correction_date' => $correctionDate,
                'new_method' => $method,
                'new_reference' => $reference,
                'reason' => 'Koreksi metode pembayaran',
            ];
        }

        if ($changes === []) {
            return back()->withInput()->with('error', 'Tidak ada metode pembayaran yang diubah.');
        }

        DB::transaction(function () use ($changes): void {
            foreach ($changes as $data) {
                $this->correct($data);
            }
        }, attempts: 3);

        return redirect()->route('keuangan.order-adjustments.index', array_filter($request->only('q', 'tanggal')))
            ->with('status', count($changes).' metode pembayaran berhasil dikoreksi dan jejak akuntansinya sudah dicatat.');
    }

    /**
     * @param  array{payment_id:int, correction_date:string, new_method:string, new_reference:?string, reason:string}  $data
     */
    private function correct(array $data): void
    {
        $payment = OrderPayment::query()->lockForUpdate()->findOrFail($data['payment_id']);
        $model = self::ORDER_MODELS[$payment->order_type] ?? null;
        abort_unless($model, 422, 'Jenis order pada pembayaran tidak dikenali.');

        /** @var Model $order */
        $order = $model::query()->with('customer')->lockForUpdate()->findOrFail($payment->order_id);
        abort_unless(in_array($order->status_bayar, ['dp', 'lunas'], true), 422, 'Hanya nota berstatus DP atau Lunas yang dapat dikoreksi.');
        abort_if($order->status === 'batal' || $order->invoice_voided_at, 422, 'Nota batal atau hangus tidak dapat dikoreksi.');
        abort_unless((float) $payment->jumlah > 0, 422, 'Pembayaran refund tidak dapat dikoreksi melalui halaman ini.');

        $oldMethod = $payment->cara_bayar;
        $oldReference = $payment->no_referensi;
        if ($oldMethod === $data['new_method'] && ($oldReference ?: null) === $data['new_reference']) {
            throw ValidationException::withMessages(['new_method' => 'Metode dan nomor referensi baru masih sama dengan data lama.']);
        }

        $amount = (float) $payment->jumlah;
        $oldAccount = AccountingService::akunKasFor($oldMethod);
        $newAccount = AccountingService::akunKasFor($data['new_method']);
        $journalNumber = null;
        if ($oldAccount !== $newAccount) {
            $journalNumber = $this->accounting->post(
                $data['correction_date'], $order->NoOrder,
                'Koreksi metode bayar '.$order->NoOrder,
                [
                    ['akun' => $newAccount, 'debet' => $amount],
                    ['akun' => $oldAccount, 'kredit' => $amount],
                ]
            );
        }

        $payment->update(['cara_bayar' => $data['new_method'], 'no_referensi' => $data['new_reference']]);

        $positivePayments = OrderPayment::query()->forOrder($payment->order_type, $payment->order_id)
            ->where('jumlah', '>', 0)->get();
        $methods = $positivePayments->pluck('cara_bayar')->unique()->values();
        $order->update([
            'cara_bayar' => $methods->count() === 1 ? $methods->first() : 'campuran',
            'no_referensi' => $positivePayments->count() === 1 ? $positivePayments->first()->no_referensi : null,
        ]);

        $invoiceNumber = OrderDocument::query()
            ->where('order_type', $payment->order_type)->where('order_id', $payment->order_id)
            ->where('kind', 'inv')->latest('sequence')->value('number');

        PaymentMethodCorrection::create([
            'order_payment_id' => $payment->id,
            'order_type' => $payment->order_type,
            'order_id' => $payment->order_id,
            'order_number' => $order->NoOrder,
            'invoice_number' => $invoiceNumber,
            'correction_date' => $data['correction_date'],
            'amount' => $amount,
            'old_method' => $oldMethod,
            'new_method' => $data['new_method'],
            'old_reference' => $oldReference,
            'new_reference' => $data['new_reference'],
            'reason' => $data['reason'],
            'user_id' => auth()->id(),
            'journal_transaction_number' => $journalNumber,
        ]);

        OrderStatusNote::create([
            'order_type' => $payment->order_type,
            'order_id' => $payment->order_id,
            'stage' => 'akuntansi',
            'action' => 'koreksi_metode_bayar',
            'catatan' => 'Metode pembayaran Rp '.number_format($amount, 0, ',', '.').' dikoreksi dari '.strtoupper($oldMethod).' menjadi '.strtoupper($data['new_method']).'. '.$data['reason'],
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
    }
}
