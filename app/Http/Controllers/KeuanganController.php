<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\CashDailyEntry;
use App\Models\Customer;
use App\Models\FinalSalesDiscount;
use App\Models\JurnalEntry;
use App\Models\LaporanPpnFinal;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\OrderReworkRequest;
use App\Models\PaymentMethodCorrection;
use App\Models\PengaturanKeuangan;
use App\Models\User;
use App\Services\OrderDocumentService;
use App\Services\ReceivableBatchPayment;
use App\Support\FinanceMenuAccess;
use App\Support\SimpleXlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeuanganController extends Controller
{
    /**
     * Cara bayar yang uangnya masuk/keluar lewat rekening bank, bukan laci
     * kas (jurnalnya ke Kas Bank 11101). Di laporan kas, transaksi dengan
     * cara bayar ini selalu dinetralkan agar saldo kas tunai tetap benar.
     */
    private const NON_CASH_METHODS = ['transfer', 'qris', 'debit'];

    /** Pusat pengajuan dan pemantauan pembatalan pre-order/nota Indoor dan Outdoor. */
    public function pembatalanOrder(): View
    {
        $models = [
            'indoor' => OrderIndoor::class,
            'outdoor' => OrderOutdoor::class,
        ];
        $preOrders = collect();
        $pending = collect();
        $history = collect();

        foreach ($models as $type => $model) {
            $decorate = function ($order) use ($type) {
                $order->order_type = $type;
                $order->jenis_pembatalan = $order->status_bayar === 'belum_bayar' ? 'Pre-Order' : 'Nota';

                return $order;
            };

            $model::query()->with('customer')
                ->where('status_bayar', 'belum_bayar')
                ->where('status', '!=', 'batal')
                ->whereNull('cancel_requested_at')
                ->latest('TglOrder')->limit(100)->get()
                ->each(fn ($order) => $preOrders->push($decorate($order)));

            $model::query()->with(['customer', 'cancelRequestedBy'])
                ->whereNotNull('cancel_requested_at')
                ->whereNull('cancel_approved_at')
                ->oldest('cancel_requested_at')->get()
                ->each(fn ($order) => $pending->push($decorate($order)));

            $model::query()->with(['customer', 'cancelRequestedBy', 'cancelApprovedBy'])
                ->whereNotNull('cancel_approved_at')
                ->latest('cancel_approved_at')->limit(100)->get()
                ->each(fn ($order) => $history->push($decorate($order)));
        }

        $reworkRequests = OrderReworkRequest::query()
            ->where('action', 'batal')
            ->whereIn('order_type', array_keys($models))
            ->whereIn('status', ['pending', 'approved'])
            ->with(['requestedBy', 'resolvedBy'])
            ->latest('requested_at')->limit(200)->get();
        $reworkOrders = collect();

        foreach ($reworkRequests->groupBy('order_type') as $type => $requests) {
            $models[$type]::query()->with('customer')
                ->whereIn('id', $requests->pluck('order_id')->unique())->get()
                ->each(fn ($order) => $reworkOrders->put($type.':'.$order->id, $order));
        }

        foreach ($reworkRequests as $request) {
            $order = $reworkOrders->get($request->order_type.':'.$request->order_id);
            if (! $order) {
                continue;
            }

            $order->order_type = $request->order_type;
            $order->jenis_pembatalan = 'Nota';
            $order->central_kind = 'rework_batal';
            $order->cancellation_request_id = $request->id;
            $order->cancel_reason = $request->reason;
            $order->cancel_requested_at = $request->requested_at;
            $order->setRelation('cancelRequestedBy', $request->requestedBy);
            $order->cancel_approved_at = $request->resolved_at;
            $order->setRelation('cancelApprovedBy', $request->resolvedBy);

            ($request->status === 'pending' ? $pending : $history)->push($order);
        }

        return view('keuangan.pembatalan-order', [
            'preOrders' => $preOrders->sortByDesc('TglOrder')->values(),
            'pending' => $pending->sortBy('cancel_requested_at')->values(),
            'history' => $history->sortByDesc(fn ($order) => $order->cancel_approved_at?->timestamp ?? 0)->values(),
        ]);
    }

    /**
     * Rekap harian per operator kasir. Nilai nota dicatat sebagai Debet;
     * pembayaran non-tunai mendapat pasangan Kredit sehingga selisih akhir
     * menunjukkan uang yang semestinya berada di laci kas.
     */
    public function kasHarian(Request $request): View
    {
        $filters = $request->validate([
            'tanggal' => ['nullable', 'date'],
            'kasir' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $tanggal = $filters['tanggal'] ?? now()->format('Y-m-d');
        $kasirId = isset($filters['kasir']) ? (int) $filters['kasir'] : null;

        $activityUserIds = $this->cashierActivityUserIds();

        $kasirUsers = User::query()
            ->where(function ($query) use ($activityUserIds) {
                $query->whereHas('role', fn ($role) => $role->where('name', 'kasir'))
                    ->orWhereIn('id', $activityUserIds);
            })
            ->orderBy('name')
            ->get(['id', 'name']);
        $selectedKasir = $kasirId ? $kasirUsers->firstWhere('id', $kasirId) : null;

        $payments = OrderPayment::with('user')
            ->whereDate('created_at', $tanggal)
            ->orderBy('created_at')
            ->get();
        $batchCounts = $payments->where('jenis', 'pelunasan_hutang')->whereNotNull('batch_id')->countBy('batch_id');

        // Resolve NoOrder/customer per order without N+1 — group ids by
        // type, one query per type, then map back onto each payment row.
        $models = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];
        $ordersByKey = collect();

        foreach ($payments->groupBy('order_type') as $type => $rows) {
            $model = $models[$type] ?? null;
            if (! $model) {
                continue;
            }

            $model::query()->with('customer', 'cancelApprovedBy')->whereIn('id', $rows->pluck('order_id')->unique())->get()
                ->each(function ($order) use (&$ordersByKey, $type) {
                    $ordersByKey["{$type}-{$order->id}"] = $order;
                });
        }

        $discountRefunds = $this->discountRefundKeys($payments);
        $paymentDiscounts = $this->discountsAttachedToPayments($payments);
        $cashDiscounts = $this->cashDiscountAllocations();
        $paymentRows = $payments->flatMap(function (OrderPayment $p) use ($ordersByKey, $discountRefunds, $paymentDiscounts, $cashDiscounts, $batchCounts) {
            $order = $ordersByKey["{$p->order_type}-{$p->order_id}"] ?? null;
            $jumlah = (float) $p->jumlah;
            $customer = $order?->customer?->NmCust
                ? mb_strtoupper($order->customer->NmCust)
                : '-';
            $jenis = OrderPayment::JENIS_LABELS[$p->jenis] ?? ucfirst($p->jenis);
            $baseSort = $p->created_at?->format('His').'-'.str_pad((string) $p->id, 10, '0', STR_PAD_LEFT);

            if ($jumlah < 0) {
                // Potongan nota tunai sudah tampil di bawah pembayaran notanya.
                $jumlah += $cashDiscounts['refund'][$p->id] ?? 0.0;
                if ($jumlah >= 0) {
                    return [];
                }

                $isCancellation = $this->refundKind($p, $order, $discountRefunds) === 'pembatalan';
                $activityUserId = $isCancellation ? ($order?->cancel_approved_by ?: $p->user_id) : $p->user_id;
                $activityUser = $isCancellation ? ($order?->cancelApprovedBy?->name ?: $p->user?->name) : $p->user?->name;

                return [[
                    'user_id' => $activityUserId,
                    'kasir' => $activityUser ?? '-',
                    'no_nota' => $order?->NoOrder,
                    'keterangan' => match ($this->refundKind($p, $order, $discountRefunds)) {
                        'potongan' => 'Potongan Penjualan Akhir · By system - ',
                        'pembatalan' => 'Refund pembatalan · disetujui oleh '.($activityUser ?? '-').' - ',
                        default => 'Refund - ',
                    }.$customer,
                    'debet' => 0,
                    'kredit' => abs($jumlah),
                    'sort' => '2-'.$baseSort.'-0',
                ], ...($this->refundKind($p, $order, $discountRefunds) === 'potongan' ? [] : $this->nonCashRefundOffset($p, fn (string $method) => [
                    'user_id' => $activityUserId,
                    'kasir' => $activityUser ?? '-',
                    'no_nota' => null,
                    'keterangan' => $this->nonCashOffsetLabel($this->refundKind($p, $order, $discountRefunds), $method).' - '.$customer,
                    'debet' => abs($jumlah),
                    'kredit' => 0,
                    'sort' => '2-'.$baseSort.'-1',
                ]))];
            }

            // Potongan penjualan tampil saat tagihannya dibayar: Debet = tagihan
            // sebelum potongan, Kredit = potongan; selisihnya = uang diterima.
            $discounts = $paymentDiscounts[$p->id] ?? [];
            $rows = [[
                'user_id' => $p->user_id,
                'kasir' => $p->user?->name ?? '-',
                'no_nota' => $order?->NoOrder,
                'keterangan' => $jenis.' - '.$customer.($discounts ? ' (tagihan sebelum potongan)' : ''),
                'debet' => $jumlah + array_sum(array_map(fn ($d) => (float) $d->receivable_offset, $discounts)),
                'kredit' => 0,
                'sort' => '2-'.$baseSort.'-0',
                ...$this->receivableBatchMeta($p, $batchCounts, $customer, 'receipt'),
            ]];
            foreach ($discounts as $i => $discount) {
                $rows[] = [
                    'user_id' => $p->user_id,
                    'kasir' => $p->user?->name ?? '-',
                    'no_nota' => null,
                    'keterangan' => $this->salesDiscountDescription($discount, $customer),
                    'debet' => 0,
                    'kredit' => (float) $discount->receivable_offset,
                    'sort' => '2-'.$baseSort.'-0p'.$i,
                ];
            }
            if ($tunaiTidakDiterima = $cashDiscounts['receipt'][$p->id] ?? 0.0) {
                $rows[] = [
                    'user_id' => $p->user_id,
                    'kasir' => $p->user?->name ?? '-',
                    'no_nota' => null,
                    'keterangan' => 'Potongan Penjualan Akhir · uang tidak diterima - '.$customer,
                    'debet' => 0,
                    'kredit' => $tunaiTidakDiterima,
                    'sort' => '2-'.$baseSort.'-0t',
                ];
            }

            // Pembayaran non-tunai mengurangi uang yang seharusnya berada
            // di laci kas. Karena itu transaksi ditampilkan berpasangan:
            // nilai nota di Debet dan pembayaran bank/QRIS di Kredit.
            // Uang yang masuk ditampilkan penuh; potongan dicatat terpisah (berpasangan).
            $diterima = $jumlah;
            if (in_array($p->cara_bayar, self::NON_CASH_METHODS, true) && $diterima > 0) {
                $method = OrderPayment::CARA_BAYAR_LABELS[$p->cara_bayar] ?? ucfirst($p->cara_bayar);
                $rows[] = [
                    'user_id' => $p->user_id,
                    'kasir' => $p->user?->name ?? '-',
                    'no_nota' => null,
                    'keterangan' => 'Bayar via '.$method.' - '.$customer,
                    'debet' => 0,
                    'kredit' => $diterima,
                    'sort' => '2-'.$baseSort.'-1',
                    ...$this->receivableBatchMeta($p, $batchCounts, $customer, 'non_cash'),
                ];
            }

            return $rows;
        });
        $paymentRows = $this->collapseReceivableBatchRows($paymentRows, 'debet', 'kredit', 'keterangan');

        $seededRows = CashDailyEntry::with('user')
            ->whereDate('tanggal', $tanggal)
            ->when($kasirId, fn ($query) => $query->where(fn ($scope) => $scope
                ->whereNull('user_id')->orWhere('user_id', $kasirId)))
            ->orderBy('urutan')
            ->get()
            ->map(fn (CashDailyEntry $entry) => [
                'user_id' => $entry->user_id,
                'kasir' => $entry->user?->name ?? '-',
                'no_nota' => $entry->no_nota,
                'keterangan' => $entry->keterangan,
                'debet' => (float) $entry->debet,
                'kredit' => (float) $entry->kredit,
                'sort' => '1-'.str_pad((string) $entry->urutan, 10, '0', STR_PAD_LEFT),
            ]);

        // Potongan penjualan tidak lagi tampil di hari input (tidak ada uang
        // bergerak); ditampilkan bersama pembayaran tagihannya.
        $discountRows = collect();

        $rows = $seededRows->concat($paymentRows)->concat($discountRows)
            ->when($kasirId, fn (Collection $rows) => $rows->filter(
                fn (array $row) => $row['user_id'] === null || (int) $row['user_id'] === $kasirId
            ))
            ->sortBy('sort')->values();
        $groups = collect();

        $openingRows = $rows->whereNull('user_id')->values();
        if ($openingRows->isNotEmpty()) {
            $groups->push($this->cashGroup('-', $openingRows));
        }

        $rows->whereNotNull('user_id')->groupBy('user_id')
            ->sortBy(fn (Collection $group) => mb_strtolower((string) $group->first()['kasir']))
            ->each(fn (Collection $group) => $groups->push($this->cashGroup(
                ucwords(mb_strtolower((string) $group->first()['kasir'])),
                $group->values()
            )));

        $totalDebet = (float) $rows->sum('debet');
        $totalKredit = (float) $rows->sum('kredit');

        return view('keuangan.kas-harian', [
            'tanggal' => $tanggal,
            'rows' => $rows,
            'groups' => $groups,
            'totalDebet' => $totalDebet,
            'totalKredit' => $totalKredit,
            'saldoKas' => $totalDebet - $totalKredit,
            'jumlahTransaksi' => $rows->whereNotNull('user_id')->count(),
            'kasirUsers' => $kasirUsers,
            'kasirId' => $kasirId,
            'selectedKasir' => $selectedKasir,
        ]);
    }

    public function exportKasHarianExcel(Request $request): BinaryFileResponse
    {
        $data = $this->kasHarian($request)->getData();
        $rows = [
            [['value' => 'REKAP KASIR HARIAN PER USER SPEKTRUM', 'style' => 1]],
            ['Tanggal', Carbon::parse($data['tanggal'])->translatedFormat('d F Y')],
            ['Kasir', $data['selectedKasir']?->name ?? 'Semua Kasir'],
            [],
        ];

        foreach ($data['groups'] as $group) {
            $rows[] = [['value' => 'User: '.$group['label'], 'style' => 4]];
            $rows[] = array_map(fn ($value) => ['value' => $value, 'style' => 2], ['No. Nota', 'Keterangan', 'Debet', 'Kredit']);
            foreach ($group['rows'] as $row) {
                $rows[] = [
                    $row['no_nota'] ?: '',
                    $row['keterangan'],
                    ['value' => (float) $row['debet'], 'style' => 3],
                    ['value' => (float) $row['kredit'], 'style' => 3],
                ];
            }
            $rows[] = [null, ['value' => 'Sub Total', 'style' => 4], ['value' => $group['subtotal_debet'], 'style' => 5], ['value' => $group['subtotal_kredit'], 'style' => 5]];
            $rows[] = [];
        }

        $rows[] = [null, ['value' => 'TOTAL', 'style' => 4], ['value' => $data['totalDebet'], 'style' => 5], ['value' => $data['totalKredit'], 'style' => 5]];
        $rows[] = [null, null, ['value' => 'SALDO KAS', 'style' => 4], ['value' => $data['saldoKas'], 'style' => 5]];

        return SimpleXlsx::download('kas-harian-'.$data['tanggal'].'.xlsx', 'Kas Harian', $rows);
    }

    /** Keterangan baris Potongan Penjualan (Kredit) di laporan kas. */
    private function salesDiscountDescription(FinalSalesDiscount $discount, string $customer): string
    {
        return 'Potongan Penjualan - '.$customer.($discount->userReason() ? ' · '.$discount->userReason() : '');
    }

    /**
     * Metadata sementara untuk meringkas beberapa nota yang dibayar lewat
     * satu kali klik "Proses Pembayaran". Data sumber tetap per nota.
     *
     * @return array<string, string>
     */
    private function receivableBatchMeta(
        OrderPayment $payment,
        Collection $batchCounts,
        string $customer,
        string $role,
    ): array {
        if ($payment->jenis !== 'pelunasan_hutang' || ! $payment->batch_id) {
            return [];
        }

        $method = OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? ucfirst($payment->cara_bayar);
        $reference = $payment->no_referensi ? ' · Ref '.$payment->no_referensi : '';
        $count = (int) ($batchCounts[$payment->batch_id] ?? 1);
        $description = $role === 'non_cash'
            ? "Bayar via {$method} - {$customer} · {$count} nota{$reference}"
            : "Penerimaan Piutang - {$customer} · {$count} nota · {$method}{$reference}";

        return [
            'receivable_batch_id' => $payment->batch_id,
            'receivable_batch_role' => $role,
            'receivable_batch_description' => $description,
        ];
    }

    /**
     * Gabungkan baris tampilan satu batch tanpa menyentuh OrderPayment atau
     * jurnal per nota. Baris lama tanpa batch_id dibiarkan apa adanya.
     */
    private function collapseReceivableBatchRows(
        Collection $rows,
        string $debitKey,
        string $creditKey,
        string $descriptionKey,
    ): Collection {
        $batched = $rows->filter(fn (array $row) => ! empty($row['receivable_batch_id']));
        if ($batched->isEmpty()) {
            return $rows;
        }

        $collapsed = $batched
            ->groupBy(fn (array $row) => $row['receivable_batch_id'].'|'.$row['receivable_batch_role'])
            ->map(function (Collection $group) use ($debitKey, $creditKey, $descriptionKey) {
                $row = $group->first();
                $row[$debitKey] = (float) $group->sum($debitKey);
                $row[$creditKey] = (float) $group->sum($creditKey);
                $row[$descriptionKey] = $row['receivable_batch_description'];
                $row['receivable_group_key'] = 'batch:'.$row['receivable_batch_id'];
                if ($group->contains(fn (array $item) => ! empty($item['order_numbers']))) {
                    $row['order_numbers'] = $group->flatMap(fn (array $item) => $item['order_numbers'] ?? [])
                        ->filter()->unique()->values()->all();
                }
                if (array_key_exists('no_nota', $row)) {
                    $row['no_nota'] = null;
                }
                unset($row['receivable_batch_id'], $row['receivable_batch_role'], $row['receivable_batch_description']);

                return $row;
            });

        return $rows->reject(fn (array $row) => ! empty($row['receivable_batch_id']))
            ->concat($collapsed)
            ->sortBy('sort')
            ->values();
    }

    /**
     * Potongan penjualan yang mengurangi tagihan (receivable_offset) ditampilkan
     * di laporan kas bersama PEMBAYARAN BERIKUTNYA nota tersebut: Debet =
     * tagihan sebelum potongan, Kredit = potongan, selisih = uang diterima.
     * Bagian potongan yang dikembalikan berupa uang sudah tampil sebagai Refund.
     *
     * @return array<int, array<int, FinalSalesDiscount>> id pembayaran → potongan
     */
    private function discountsAttachedToPayments(Collection $payments): array
    {
        $incoming = $payments->filter(fn (OrderPayment $payment) => (float) $payment->jumlah > 0);
        if ($incoming->isEmpty()) {
            return [];
        }

        $discounts = FinalSalesDiscount::query()
            ->where('receivable_offset', '>', 0)
            ->where(function ($query) use ($incoming) {
                foreach ($incoming->groupBy('order_type') as $type => $rows) {
                    $query->orWhere(fn ($scope) => $scope->where('order_type', $type)->whereIn('order_id', $rows->pluck('order_id')->unique()));
                }
            })
            ->orderBy('id')->get();

        $attached = [];
        $incomingIds = $incoming->pluck('id')->flip();
        foreach ($discounts as $discount) {
            // Pembayaran masuk pertama setelah potongan diinput (seluruh riwayat, bukan hanya periode laporan).
            $paymentId = OrderPayment::query()
                ->where('order_type', $discount->order_type)->where('order_id', $discount->order_id)
                ->where('jumlah', '>', 0)->where('created_at', '>=', $discount->created_at)
                ->orderBy('created_at')->orderBy('id')->value('id');
            if ($paymentId && isset($incomingIds[$paymentId])) {
                $attached[$paymentId][] = $discount;
            }
        }

        return $attached;
    }

    /**
     * Keterangan baris penyeimbang (Debet) untuk refund / Potongan Penjualan
     * Akhir non-tunai: uangnya tidak keluar dari laci kas, jadi Saldo Kas tetap.
     */
    private function nonCashOffsetLabel(?string $kind, string $method): string
    {
        return $kind === 'potongan'
            ? 'Potongan Penjualan Akhir · By system (bukan dari kas)'
            : "Refund via {$method} (keluar dari bank, bukan kas)";
    }

    /**
     * Baris penetral untuk refund / Potongan Penjualan Akhir yang dibayar lewat bank/QRIS/debit: baris
     * Refund tetap tampil (Kredit) sebagai catatan, lalu dinetralkan di
     * Debet karena uangnya keluar dari rekening bank, bukan dari laci kas.
     * Pola ini sama dengan pembayaran non-tunai ("Bayar via ...").
     */
    private function nonCashRefundOffset(OrderPayment $payment, callable $row): array
    {
        if (! in_array($payment->cara_bayar, self::NON_CASH_METHODS, true)) {
            return [];
        }

        return [$row(OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? ucfirst((string) $payment->cara_bayar))];
    }

    /**
     * Asal baris refund untuk label laporan kas: 'potongan' (Potongan
     * Penjualan), 'pembatalan' (order batal) atau null (refund lain).
     */
    private function refundKind(OrderPayment $payment, $order, array $discountRefunds): ?string
    {
        if (isset($discountRefunds[$payment->order_type.'-'.$payment->order_id.'-'.(int) round(abs((float) $payment->jumlah))])) {
            return 'potongan';
        }

        return $order?->status === 'batal' ? 'pembatalan' : null;
    }

    /**
     * User yang perlu tersedia pada filter laporan kasir. Approver pembatalan
     * ikut dimasukkan karena refund pembatalan menjadi aktivitas approver,
     * walaupun penerimaan awal tetap menjadi aktivitas kasir penerima uang.
     *
     * @return Collection<int, int>
     */
    private function cashierActivityUserIds(): Collection
    {
        return OrderPayment::query()->whereNotNull('user_id')->pluck('user_id')
            ->merge(CashDailyEntry::query()->whereNotNull('user_id')->pluck('user_id'))
            ->merge(FinalSalesDiscount::query()->whereNotNull('user_id')->pluck('user_id'))
            ->merge(OrderIndoor::query()->whereNotNull('cancel_approved_by')->pluck('cancel_approved_by'))
            ->merge(OrderOutdoor::query()->whereNotNull('cancel_approved_by')->pluck('cancel_approved_by'))
            ->merge(OrderArtwork::query()->whereNotNull('cancel_approved_by')->pluck('cancel_approved_by'))
            ->map(fn ($id) => (int) $id)
            ->unique()->values();
    }

    /**
     * Kunci "jenis-id-nominal" untuk refund yang berasal dari Potongan
     * Penjualan, agar barisnya diberi keterangan pasangannya.
     *
     * @param  Collection<int, OrderPayment>  $payments
     * @return array<string, true>
     */
    private function discountRefundKeys(Collection $payments): array
    {
        $refunds = $payments->filter(fn (OrderPayment $payment) => (float) $payment->jumlah < 0);
        if ($refunds->isEmpty()) {
            return [];
        }

        return FinalSalesDiscount::query()
            ->where('refund_amount', '>', 0)
            ->where(function ($query) use ($refunds) {
                foreach ($refunds->groupBy('order_type') as $type => $rows) {
                    $query->orWhere(fn ($scope) => $scope->where('order_type', $type)->whereIn('order_id', $rows->pluck('order_id')));
                }
            })
            ->get(['order_type', 'order_id', 'refund_amount'])
            ->mapWithKeys(fn ($discount) => [$discount->order_type.'-'.$discount->order_id.'-'.(int) round((float) $discount->refund_amount) => true])
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{label: string, rows: Collection, subtotal_debet: float, subtotal_kredit: float}
     */
    private function cashGroup(string $label, Collection $rows): array
    {
        return [
            'label' => $label,
            'rows' => $rows,
            'subtotal_debet' => (float) $rows->sum('debet'),
            'subtotal_kredit' => (float) $rows->sum('kredit'),
        ];
    }

    /** @var array{refund: array<int, float>, receipt: array<int, float>, receipt_at: array<int, string>}|null */
    private ?array $cashDiscountAllocations = null;

    /**
     * Potongan Penjualan Akhir pada nota yang dibayar TUNAI tidak mengeluarkan
     * uang dari laci: potongan disepakati sebelum customer membayar, jadi uang
     * yang diterima kasir memang sudah setelah potongan. Di laporan kas,
     * potongan ini dihitung pada pembayaran tunai nota tersebut (hari & kasir
     * yang menerima uang), bukan sebagai uang keluar di hari keuangan
     * menginputnya. Sisa yang tidak tertampung pembayaran tunai (jarang)
     * tetap tampil seperti refund biasa.
     *
     * @return array{refund: array<int, float>, receipt: array<int, float>, receipt_at: array<int, string>}
     *   refund: id pembayaran negatif → nominal yang dipindahkan;
     *   receipt: id pembayaran tunai → pengurang; receipt_at: id → created_at
     */
    private function cashDiscountAllocations(): array
    {
        if ($this->cashDiscountAllocations !== null) {
            return $this->cashDiscountAllocations;
        }

        $result = ['refund' => [], 'receipt' => [], 'receipt_at' => []];
        $discounts = FinalSalesDiscount::query()
            ->where('refund_amount', '>', 0)->where('refund_method', 'tunai')
            ->orderBy('id')->get(['order_type', 'order_id', 'refund_amount']);

        foreach ($discounts as $discount) {
            $refund = OrderPayment::query()
                ->where('order_type', $discount->order_type)->where('order_id', $discount->order_id)
                ->where('cara_bayar', 'tunai')->where('jumlah', -(float) $discount->refund_amount)
                ->whereNotIn('id', array_keys($result['refund']))
                ->orderBy('id')->first(['id']);
            if (! $refund) {
                continue;
            }

            $remaining = (float) $discount->refund_amount;
            $receipts = OrderPayment::query()
                ->where('order_type', $discount->order_type)->where('order_id', $discount->order_id)
                ->where('cara_bayar', 'tunai')->where('jumlah', '>', 0)->where('id', '<', $refund->id)
                ->orderByDesc('id')->get(['id', 'jumlah', 'created_at']);

            foreach ($receipts as $receipt) {
                if ($remaining <= 0) {
                    break;
                }
                $already = $result['receipt'][$receipt->id] ?? 0.0;
                $portion = min($remaining, max(0.0, (float) $receipt->jumlah - $already));
                if ($portion > 0) {
                    $result['receipt'][$receipt->id] = $already + $portion;
                    $result['receipt_at'][$receipt->id] = $receipt->created_at->format('Y-m-d H:i:s');
                    $remaining -= $portion;
                }
            }

            $moved = (float) $discount->refund_amount - $remaining;
            if ($moved > 0) {
                $result['refund'][$refund->id] = $moved;
            }
        }

        return $this->cashDiscountAllocations = $result;
    }

    /**
     * Rekap gabungan aktivitas kasir menurut jenis penerimaan. Filter kasir
     * tetap tersedia untuk audit satu operator tanpa mengubah susunan laporan.
     */
    public function rekapKasir(Request $request): View
    {
        $filters = $request->validate([
            'tanggal' => ['nullable', 'date'],
            'dari' => ['nullable', 'date'],
            'kasir' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        // Satu tanggal saja; 'dari' tetap diterima untuk tautan lama.
        $dari = $sampai = $filters['tanggal'] ?? $filters['dari'] ?? now()->format('Y-m-d');
        $kasirId = isset($filters['kasir']) ? (int) $filters['kasir'] : null;

        $activityUserIds = $this->cashierActivityUserIds();

        $kasirUsers = User::query()
            ->where(function ($query) use ($activityUserIds) {
                $query->whereHas('role', fn ($role) => $role->where('name', 'kasir'))
                    ->orWhereIn('id', $activityUserIds);
            })
            ->orderBy('name')
            ->get(['id', 'name']);
        $selectedKasir = $kasirId ? $kasirUsers->firstWhere('id', $kasirId) : null;

        $payments = OrderPayment::with('user')
            ->whereBetween('created_at', ["{$dari} 00:00:00", "{$sampai} 23:59:59"])
            ->orderBy('created_at')
            ->get();
        $batchCounts = $payments->where('jenis', 'pelunasan_hutang')->whereNotNull('batch_id')->countBy('batch_id');

        $models = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];
        $ordersByKey = collect();
        foreach ($payments->groupBy('order_type') as $type => $typePayments) {
            $model = $models[$type] ?? null;
            if (! $model) {
                continue;
            }
            $model::query()->with('customer', 'cancelApprovedBy')->whereIn('id', $typePayments->pluck('order_id')->unique())->get()
                ->each(fn ($order) => $ordersByKey->put("{$type}-{$order->id}", $order));
        }

        $details = CashDailyEntry::query()
            ->whereBetween('tanggal', [$dari, $sampai])
            ->where(fn ($query) => $query
                ->whereRaw('LOWER(keterangan) <> ?', ['saldo awal'])
                ->orWhereDate('tanggal', $dari))
            ->when($kasirId, fn ($query) => $query->where(fn ($scope) => $scope
                ->whereNull('user_id')->orWhere('user_id', $kasirId)))
            ->orderBy('tanggal')->orderBy('urutan')->get()
            ->map(function (CashDailyEntry $entry) {
                $description = $entry->keterangan;
                $category = match (true) {
                    $entry->user_id === null || strcasecmp($description, 'Saldo Awal') === 0 => 'opening',
                    // Semua penyesuaian kas digabung di Transaksi Lain-lain (paling bawah).
                    str_starts_with($entry->source_key, 'cash-adjustment:') => 'other_transaction',
                    (float) $entry->kredit > 0 && str_starts_with(mb_strtolower($description), 'bayar via') => 'non_cash',
                    str_starts_with(mb_strtolower($description), 'piutang ') => 'receivable',
                    str_starts_with(mb_strtolower($description), 'dp -') || str_starts_with((string) $entry->no_nota, 'UM-') => 'advance',
                    default => 'cash_note',
                };

                if ($category === 'advance' && $entry->no_nota) {
                    $customer = trim((string) preg_replace('/^DP\s*-\s*/i', '', $description));
                    $description = $entry->no_nota.' ('.$customer.')';
                }

                return [
                    'category' => $category,
                    'description' => $description,
                    'debit' => (float) $entry->debet,
                    'credit' => (float) $entry->kredit,
                    'user_id' => $entry->user_id,
                    'group_date' => Carbon::parse($entry->tanggal)->format('Y-m-d'),
                    'sort' => '1-'.str_pad((string) $entry->urutan, 10, '0', STR_PAD_LEFT),
                ];
            });

        // Potongan Penjualan Akhir tidak ditampilkan di Rekap Kasir: baris
        // penerimaan langsung berisi uang yang benar-benar diterima.
        $discountRefunds = $this->discountRefundKeys($payments);
        $cashDiscounts = $this->cashDiscountAllocations();
        // Hasil Penyesuaian Nota Order / Invoice (koreksi cara bayar) tampil di
        // Transaksi Lain-lain pada hari nota dibayar.
        $corrections = PaymentMethodCorrection::query()
            ->whereIn('order_payment_id', $payments->pluck('id'))
            ->orderBy('id')->get()->groupBy('order_payment_id');
        foreach ($payments as $payment) {
            $order = $ordersByKey["{$payment->order_type}-{$payment->order_id}"] ?? null;
            $customer = $order?->customer?->NmCust ? mb_strtoupper($order->customer->NmCust) : '-';
            $amount = (float) $payment->jumlah;
            $method = OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? ucfirst($payment->cara_bayar);
            $baseSort = '2-'.$payment->created_at?->format('YmdHis').'-'.str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT);
            // Semua uang masuk/keluar milik order yang DIBATALKAN dikumpulkan di
            // Transaksi Lain-lain, terpisah dari nota normal.
            $batal = $order?->status === 'batal' ? 'Pembatalan '.$order->NoOrder.' · ' : null;
            if ($batal) {
                // Baris satu order batal berurutan (uang masuk lalu refund), sesudah penyesuaian kas.
                $baseSort = '3-'.$order->NoOrder.'-'.substr($baseSort, 2);
            }

            if ($amount < 0) {
                $isPotongan = $this->refundKind($payment, $order, $discountRefunds) === 'potongan';
                if ($isPotongan) {
                    // Non-tunai: "Bayar via ..." tetap penuh, tidak ada uang laci bergerak.
                    // Tunai: sudah dikurangkan dari pembayaran tunai notanya.
                    $amount += $cashDiscounts['refund'][$payment->id] ?? 0.0;
                    if ($payment->cara_bayar !== 'tunai' || $amount >= 0) {
                        continue;
                    }
                }
                $isCancellation = ! $isPotongan && $this->refundKind($payment, $order, $discountRefunds) === 'pembatalan';
                $activityUserId = $isCancellation ? ($order?->cancel_approved_by ?: $payment->user_id) : $payment->user_id;
                $activityUser = $isCancellation ? ($order?->cancelApprovedBy?->name ?: $payment->user?->name) : $payment->user?->name;
                $approvalInfo = $isCancellation ? ' · disetujui oleh '.($activityUser ?? '-') : '';

                $details->push([
                    'category' => $isPotongan ? 'sales_discount' : ($batal ? 'other_transaction' : 'refund'),
                    'description' => $batal && ! $isPotongan ? "{$batal}refund via {$method}{$approvalInfo} - {$customer}" : match ($this->refundKind($payment, $order, $discountRefunds)) {
                        'potongan' => 'Potongan Penjualan Akhir · '.($order?->NoOrder ?? '-')." · By system - {$customer}",
                        'pembatalan' => 'Refund pembatalan · '.($order?->NoOrder ?? '-')." · via {$method}{$approvalInfo} - {$customer}",
                        default => "Refund via {$method} - {$customer}",
                    },
                    'debit' => 0.0,
                    'credit' => abs($amount),
                    'user_id' => $activityUserId,
                    'sort' => $baseSort.'-0',
                ]);
                foreach (($isPotongan ? [] : $this->nonCashRefundOffset($payment, fn (string $method) => [
                    'category' => $batal ? 'other_transaction' : 'non_cash_refund',
                    'description' => $batal
                        ? "{$batal}refund via {$method} (keluar dari bank, bukan kas) - {$customer}"
                        : $this->nonCashOffsetLabel($this->refundKind($payment, $order, $discountRefunds), $method)." - {$customer}",
                    'debit' => abs($amount),
                    'credit' => 0.0,
                    'user_id' => $activityUserId,
                    'sort' => $baseSort.'-1',
                ])) as $offset) {
                    $details->push($offset);
                }

                continue;
            }

            // Rekap Kasir Harian Spektrum hanya menampilkan aktivitas
            // pembatalannya (refund di Kredit). Uang masuk awal tetap ada
            // pada Rekap Kasir per User milik kasir yang menerimanya.
            if ($batal) {
                continue;
            }

            $category = match ($payment->jenis) {
                'dp' => 'advance',
                'pelunasan_hutang' => 'receivable',
                default => 'cash_note',
            };
            $description = match ($category) {
                'advance' => ($order?->NoOrder ?? 'DP').' ('.$customer.')',
                'receivable' => ($order?->NoOrder ?? '-').' '.$customer,
                default => 'Nota '.($order?->NoOrder ?? '-').' ('.$customer.')',
            };

            $diterima = $amount - ($cashDiscounts['receipt'][$payment->id] ?? 0.0);
            if ($diterima > 0) {
                $details->push([
                    'category' => $category,
                    'description' => $description,
                    'debit' => $diterima,
                    'credit' => 0.0,
                    'user_id' => $payment->user_id,
                    'sort' => $baseSort.'-0',
                ]);
            }

            if (in_array($payment->cara_bayar, self::NON_CASH_METHODS, true) && $diterima > 0) {
                $koreksi = $corrections[$payment->id] ?? null;
                $details->push([
                    'category' => $koreksi || $batal ? 'other_transaction' : 'non_cash',
                    'description' => $batal
                        ? "{$batal}dibayar via {$method} (masuk bank, bukan kas) - {$customer}"
                        : ($koreksi
                        ? 'Koreksi cara bayar '.($order?->NoOrder ?? '-').' · '
                            .(OrderPayment::CARA_BAYAR_LABELS[$koreksi->first()->old_method] ?? ucfirst($koreksi->first()->old_method))
                            ." → {$method} (dikoreksi ".$koreksi->last()->correction_date->format('d/m/Y').") - {$customer}"
                        : "Bayar via {$method} - {$customer}"),
                    'debit' => 0.0,
                    'credit' => $diterima,
                    'user_id' => $payment->user_id,
                    'order_numbers' => array_filter([$order?->NoOrder]),
                    'group_date' => $payment->created_at?->format('Y-m-d'),
                    'sort' => $baseSort.'-1',
                    ...$this->receivableBatchMeta($payment, $batchCounts, $customer, 'non_cash'),
                ]);
            }
        }

        $details = $this->collapseReceivableBatchRows($details, 'debit', 'credit', 'description')
            ->when($kasirId, fn (Collection $rows) => $rows->filter(
                fn (array $row) => $row['user_id'] === null || (int) $row['user_id'] === $kasirId
            ))
            ->sortBy('sort')->values();
        $sectionDefinitions = [
            'opening' => 'Saldo Awal',
            'cash_note' => 'Penerimaan Nota Tunai (Cash)',
            'receivable' => 'Penerimaan Piutang',
            'advance' => 'Uang Muka (DP)',
            'non_cash' => 'Penerimaan Non Tunai (Transfer, QRIS atau Debit)',
            'refund' => 'Refund / Pengeluaran Kas',
            'non_cash_refund' => 'Refund Non Tunai (keluar dari bank, bukan kas)',
            'sales_discount' => 'Potongan Penjualan (melebihi uang tunai yang diterima)',
            'other_transaction' => 'Transaksi Lain-lain',
        ];

        $sections = collect($sectionDefinitions)->map(function (string $label, string $key) use ($details) {
            $sectionRows = $details->where('category', $key);

            // Laporan lama merangkum piutang per customer. Baris transaksi
            // lain tetap dipertahankan agar nomor nota dan kanal bayar jelas.
            if ($key === 'receivable') {
                $sectionRows = $sectionRows->map(fn (array $row) => [
                    'description' => $row['description'],
                    'debit' => $row['debit'],
                    'credit' => $row['credit'],
                ])->values();
            } elseif ($key === 'non_cash') {
                // Transfer/QRIS customer yang sama pada tanggal yang sama
                // diringkas menjadi satu baris. Rincian OrderPayment tetap
                // tersimpan per nota/batch untuk audit.
                $sectionRows = $sectionRows
                    ->groupBy(function (array $row) {
                        $description = preg_replace('/ · \d+ nota(?: · Ref .*)?$/u', '', $row['description']);

                        return ($row['group_date'] ?? '-').'|'.$description;
                    })
                    ->map(function (Collection $rows) {
                        $description = preg_replace('/ · \d+ nota(?: · Ref .*)?$/u', '', $rows->first()['description']);
                        $numbers = $rows->flatMap(fn (array $row) => $row['order_numbers'] ?? [])->filter()->unique()->values();

                        return [
                            'description' => $description.($numbers->isNotEmpty() ? ' · Nota '.$numbers->implode(', ') : ''),
                            'debit' => (float) $rows->sum('debit'),
                            'credit' => (float) $rows->sum('credit'),
                        ];
                    })->sortBy('description')->values();
            } else {
                $sectionRows = $sectionRows->map(fn (array $row) => [
                    'description' => $row['description'],
                    'debit' => $row['debit'],
                    'credit' => $row['credit'],
                ])->values();
            }

            return [
                'key' => $key,
                'label' => $label,
                'rows' => $sectionRows,
                'debit' => (float) $sectionRows->sum('debit'),
                'credit' => (float) $sectionRows->sum('credit'),
            ];
        })->filter(fn (array $section) => $section['rows']->isNotEmpty())->values();

        $totalDebet = (float) $details->sum('debit');
        $totalKredit = (float) $details->sum('credit');
        $cashierCount = $details->whereNotNull('user_id')->pluck('user_id')->unique()->count();

        return view('keuangan.rekap-kasir', [
            'dari' => $dari,
            'sampai' => $sampai,
            'sections' => $sections,
            'totalDebet' => $totalDebet,
            'totalKredit' => $totalKredit,
            'saldoKas' => $totalDebet - $totalKredit,
            'jumlahTransaksi' => $details->whereNotNull('user_id')->count(),
            'cashierCount' => $cashierCount,
            'kasirUsers' => $kasirUsers,
            'kasirId' => $kasirId,
            'selectedKasir' => $selectedKasir,
        ]);
    }

    public function exportRekapKasirExcel(Request $request): BinaryFileResponse
    {
        $data = $this->rekapKasir($request)->getData();
        $period = Carbon::parse($data['dari'])->translatedFormat('d F Y');
        if ($data['dari'] !== $data['sampai']) {
            $period .= ' s/d '.Carbon::parse($data['sampai'])->translatedFormat('d F Y');
        }

        $rows = [
            [['value' => 'REKAP KASIR HARIAN SPEKTRUM', 'style' => 1]],
            ['Periode', $period],
            ['Kasir', $data['selectedKasir']?->name ?? 'Gabungan Semua Kasir'],
            [],
        ];

        foreach ($data['sections'] as $section) {
            $rows[] = [['value' => $section['label'], 'style' => 4]];
            $rows[] = array_map(fn ($value) => ['value' => $value, 'style' => 2], ['Keterangan', 'Debet', 'Kredit']);
            foreach ($section['rows'] as $row) {
                $rows[] = [
                    $row['description'],
                    ['value' => (float) $row['debit'], 'style' => 3],
                    ['value' => (float) $row['credit'], 'style' => 3],
                ];
            }
            if ($section['key'] !== 'opening') {
                $rows[] = [['value' => 'Sub Total', 'style' => 4], ['value' => $section['debit'], 'style' => 5], ['value' => $section['credit'], 'style' => 5]];
            }
            $rows[] = [];
        }

        $rows[] = [['value' => 'TOTAL', 'style' => 4], ['value' => $data['totalDebet'], 'style' => 5], ['value' => $data['totalKredit'], 'style' => 5]];
        $rows[] = [null, ['value' => 'SALDO KAS', 'style' => 4], ['value' => $data['saldoKas'], 'style' => 5]];

        return SimpleXlsx::download('rekap-kasir-'.$data['dari'].'-'.$data['sampai'].'.xlsx', 'Rekap Kasir', $rows);
    }

    /**
     * Jurnal kronologis seluruh aktivitas kasir pada satu hari. Baris nota
     * selalu mendahului pasangan pembayaran Transfer/QRIS pada waktu yang sama.
     */
    public function laporanKasirHarian(Request $request): View
    {
        $filters = $request->validate([
            'tanggal' => ['nullable', 'date'],
            'kasir' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $tanggal = $filters['tanggal'] ?? now()->format('Y-m-d');
        $kasirId = isset($filters['kasir']) ? (int) $filters['kasir'] : null;

        $activityUserIds = $this->cashierActivityUserIds();
        $kasirUsers = User::query()
            ->where(function ($query) use ($activityUserIds) {
                $query->whereHas('role', fn ($role) => $role->where('name', 'kasir'))
                    ->orWhereIn('id', $activityUserIds);
            })
            ->orderBy('name')->get(['id', 'name']);
        // Kasir (akses lewat izin kasir.laporan-kasir-harian.view) hanya melihat aktivitasnya
        // sendiri; pemegang menu keuangan (mis. Akuntansi) tetap melihat semua kasir.
        $lockedToSelf = ! FinanceMenuAccess::allows($request->user(), 'keuangan.laporan-kasir-harian');
        if ($lockedToSelf) {
            $kasirId = (int) $request->user()->id;
            $kasirUsers = User::query()->whereKey($kasirId)->get(['id', 'name']);
        }
        $selectedKasir = $kasirId ? $kasirUsers->firstWhere('id', $kasirId) : null;

        $manualRows = CashDailyEntry::with('user')
            ->whereDate('tanggal', $tanggal)
            ->when($kasirId, fn ($query) => $query->where(fn ($scope) => $scope
                ->whereNull('user_id')->orWhere('user_id', $kasirId)))
            ->orderBy('occurred_at')->orderBy('urutan')->get()
            ->map(function (CashDailyEntry $entry) {
                $occurredAt = $entry->occurred_at
                    ?? Carbon::parse($entry->tanggal)->startOfDay()->addSeconds($entry->urutan);

                return [
                    'occurred_at' => $occurredAt,
                    'no_nota' => $entry->no_nota,
                    'keterangan' => $entry->keterangan,
                    'debet' => (float) $entry->debet,
                    'kredit' => (float) $entry->kredit,
                    'kasir' => $entry->user?->name,
                    'user_id' => $entry->user_id,
                    'sort' => $occurredAt->format('YmdHis').'-0-'.str_pad((string) $entry->urutan, 10, '0', STR_PAD_LEFT),
                ];
            });

        $payments = OrderPayment::with('user')
            ->whereDate('created_at', $tanggal)
            ->when($kasirId, fn ($query) => $query->where('user_id', $kasirId))
            ->orderBy('created_at')->orderBy('id')->get();
        $batchCounts = $payments->where('jenis', 'pelunasan_hutang')->whereNotNull('batch_id')->countBy('batch_id');
        $models = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];
        $ordersByKey = collect();

        foreach ($payments->groupBy('order_type') as $type => $typePayments) {
            $model = $models[$type] ?? null;
            if (! $model) {
                continue;
            }

            $model::query()->with('customer')->whereIn('id', $typePayments->pluck('order_id')->unique())->get()
                ->each(fn ($order) => $ordersByKey->put("{$type}-{$order->id}", $order));
        }

        $discountRefunds = $this->discountRefundKeys($payments);
        $paymentDiscounts = $this->discountsAttachedToPayments($payments);
        $cashDiscounts = $this->cashDiscountAllocations();
        $paymentRows = $payments->flatMap(function (OrderPayment $payment) use ($ordersByKey, $discountRefunds, $paymentDiscounts, $cashDiscounts, $batchCounts) {
            $order = $ordersByKey["{$payment->order_type}-{$payment->order_id}"] ?? null;
            $customer = $order?->customer?->NmCust ? mb_strtoupper($order->customer->NmCust) : '-';
            $amount = (float) $payment->jumlah;
            $method = OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? ucfirst($payment->cara_bayar);
            $kind = OrderPayment::JENIS_LABELS[$payment->jenis] ?? ucfirst($payment->jenis);
            $baseSort = $payment->created_at->format('YmdHis').'-1-'.str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT);

            if ($amount < 0) {
                // Potongan nota tunai sudah tampil di bawah pembayaran notanya.
                $amount += $cashDiscounts['refund'][$payment->id] ?? 0.0;
                if ($amount >= 0) {
                    return [];
                }

                return [[
                    'occurred_at' => $payment->created_at,
                    'no_nota' => $order?->NoOrder,
                    'keterangan' => match ($this->refundKind($payment, $order, $discountRefunds)) {
                        'potongan' => 'Potongan Penjualan Akhir · By system',
                        'pembatalan' => "Refund pembatalan via {$method}",
                        default => "Refund via {$method}",
                    }." - {$customer}",
                    'debet' => 0.0,
                    'kredit' => abs($amount),
                    'kasir' => $payment->user?->name,
                    'user_id' => $payment->user_id,
                    'sort' => $baseSort.'-0',
                ], ...($this->refundKind($payment, $order, $discountRefunds) === 'potongan' ? [] : $this->nonCashRefundOffset($payment, fn (string $method) => [
                    'occurred_at' => $payment->created_at,
                    'no_nota' => null,
                    'keterangan' => $this->nonCashOffsetLabel($this->refundKind($payment, $order, $discountRefunds), $method)." - {$customer}",
                    'debet' => abs($amount),
                    'kredit' => 0.0,
                    'kasir' => $payment->user?->name,
                    'user_id' => $payment->user_id,
                    'sort' => $baseSort.'-1',
                ]))];
            }

            // Potongan penjualan tampil saat tagihannya dibayar: Debet = tagihan
            // sebelum potongan, Kredit = potongan; selisihnya = uang diterima.
            $discounts = $paymentDiscounts[$payment->id] ?? [];
            $rows = [[
                'occurred_at' => $payment->created_at,
                'no_nota' => $order?->NoOrder,
                'keterangan' => "{$kind} - {$customer}".($discounts ? ' (tagihan sebelum potongan)' : ''),
                'debet' => $amount + array_sum(array_map(fn ($d) => (float) $d->receivable_offset, $discounts)),
                'kredit' => 0.0,
                'kasir' => $payment->user?->name,
                'user_id' => $payment->user_id,
                'sort' => $baseSort.'-0',
                ...$this->receivableBatchMeta($payment, $batchCounts, $customer, 'receipt'),
            ]];
            foreach ($discounts as $i => $discount) {
                $rows[] = [
                    'occurred_at' => $payment->created_at,
                    'no_nota' => null,
                    'keterangan' => $this->salesDiscountDescription($discount, $customer),
                    'debet' => 0.0,
                    'kredit' => (float) $discount->receivable_offset,
                    'kasir' => $payment->user?->name,
                    'user_id' => $payment->user_id,
                    'sort' => $baseSort.'-0p'.$i,
                ];
            }
            if ($tunaiTidakDiterima = $cashDiscounts['receipt'][$payment->id] ?? 0.0) {
                $rows[] = [
                    'occurred_at' => $payment->created_at,
                    'no_nota' => null,
                    'keterangan' => "Potongan Penjualan Akhir · uang tidak diterima - {$customer}",
                    'debet' => 0.0,
                    'kredit' => $tunaiTidakDiterima,
                    'kasir' => $payment->user?->name,
                    'user_id' => $payment->user_id,
                    'sort' => $baseSort.'-0t',
                ];
            }

            // Uang yang masuk ditampilkan penuh; potongan dicatat terpisah (berpasangan).
            $diterima = $amount;
            if (in_array($payment->cara_bayar, self::NON_CASH_METHODS, true) && $diterima > 0) {
                $rows[] = [
                    'occurred_at' => $payment->created_at,
                    'no_nota' => null,
                    'keterangan' => "Bayar via {$method} - {$customer}",
                    'debet' => 0.0,
                    'kredit' => $diterima,
                    'kasir' => $payment->user?->name,
                    'user_id' => $payment->user_id,
                    'sort' => $baseSort.'-1',
                    ...$this->receivableBatchMeta($payment, $batchCounts, $customer, 'non_cash'),
                ];
            }

            return $rows;
        });
        $paymentRows = $this->collapseReceivableBatchRows($paymentRows, 'debet', 'kredit', 'keterangan');

        // Potongan penjualan ditampilkan bersama pembayaran tagihannya (lihat di atas).
        $discountRows = collect();

        $rows = $manualRows->concat($paymentRows)->concat($discountRows)->sortBy('sort')->values();
        $totalDebet = (float) $rows->sum('debet');
        $totalKredit = (float) $rows->sum('kredit');

        return view('keuangan.laporan-kasir-harian', [
            'tanggal' => $tanggal,
            'rows' => $rows,
            'totalDebet' => $totalDebet,
            'totalKredit' => $totalKredit,
            'saldoKas' => $totalDebet - $totalKredit,
            'jumlahTransaksi' => $rows->whereNotNull('user_id')->count(),
            'kasirUsers' => $kasirUsers,
            'kasirId' => $kasirId,
            'lockedToSelf' => $lockedToSelf,
            'selectedKasir' => $selectedKasir,
        ]);
    }

    public function exportLaporanKasirHarianExcel(Request $request): BinaryFileResponse
    {
        $data = $this->laporanKasirHarian($request)->getData();
        $rows = [
            [['value' => 'LAPORAN KASIR HARIAN SPEKTRUM', 'style' => 1]],
            ['Tanggal', Carbon::parse($data['tanggal'])->translatedFormat('d F Y')],
            ['Kasir', $data['selectedKasir']?->name ?? 'Semua Kasir'],
            [],
            array_map(fn ($value) => ['value' => $value, 'style' => 2], ['Tanggal & Jam', 'No. Nota', 'Keterangan', 'Debet', 'Kredit']),
        ];

        foreach ($data['rows'] as $row) {
            $rows[] = [
                $row['occurred_at']->format('d/m/Y H:i'),
                $row['no_nota'] ?: '',
                $row['keterangan'],
                ['value' => (float) $row['debet'], 'style' => 3],
                ['value' => (float) $row['kredit'], 'style' => 3],
            ];
        }

        $rows[] = [null, null, ['value' => 'TOTAL', 'style' => 4], ['value' => $data['totalDebet'], 'style' => 5], ['value' => $data['totalKredit'], 'style' => 5]];
        $rows[] = [null, null, null, ['value' => 'SALDO KAS', 'style' => 4], ['value' => $data['saldoKas'], 'style' => 5]];

        return SimpleXlsx::download('laporan-kasir-harian-'.$data['tanggal'].'.xlsx', 'Laporan Kasir', $rows);
    }

    /**
     * Drill-down from rekapKasir(): every customer a given kasir operator
     * personally processed payment for in the period, split by order type —
     * "kasir A layani customer siapa aja, indoor berapa outdoor berapa" —
     * plus how much of what they took in came in tunai/QRIS/transfer.
     *
     * Order counts are keyed off each order's own kasir_user_id/dibayar_at
     * (who actually pressed "Proses Pembayaran"), so a DP + separate
     * pelunasan on the same order still counts as one order, not two. The
     * money breakdown, however, is sourced from the OrderPayment ledger
     * instead — a single payment can now be split across methods (e.g. DP
     * paid as half QRIS half transfer), which the order's own cara_bayar
     * column can no longer represent (it just says 'campuran'), so the
     * ledger is the only accurate source for "how much came in via which
     * method". Hutang orders contribute to the indoor/outdoor counts but
     * nothing to the cara_bayar totals, since no cash has actually moved.
     */
    public function rekapKasirCustomer(Request $request, int $kasir): View
    {
        $dari = $request->filled('dari') ? $request->string('dari')->toString() : now()->format('Y-m-d');
        $sampai = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->format('Y-m-d');

        $kasirUser = User::findOrFail($kasir);

        $models = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class];

        $customers = collect();

        foreach ($models as $type => $model) {
            $model::query()
                ->with('customer')
                ->where('kasir_user_id', $kasir)
                ->whereBetween('dibayar_at', ["{$dari} 00:00:00", "{$sampai} 23:59:59"])
                ->get()
                ->each(function ($order) use (&$customers, $type) {
                    $key = $order->KdCust ?: '-';

                    if (! $customers->has($key)) {
                        $customers->put($key, [
                            'kode' => $order->KdCust,
                            'nama' => $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : ($order->KdCust ?: 'Tanpa customer'),
                            'indoor' => 0,
                            'outdoor' => 0,
                            'tunai' => 0.0,
                            'qris' => 0.0,
                            'transfer' => 0.0,
                            'no_order' => [],
                        ]);
                    }

                    $entry = $customers->get($key);
                    $entry[$type]++;
                    $entry['no_order'][] = $order->NoOrder;
                    $customers->put($key, $entry);
                });
        }

        $payments = OrderPayment::query()
            ->whereIn('order_type', array_keys($models))
            ->where('user_id', $kasir)
            ->whereBetween('created_at', ["{$dari} 00:00:00", "{$sampai} 23:59:59"])
            ->get();

        // Resolve each payment's customer without N+1 — one query per order
        // type for the handful of orders referenced this period.
        $kdCustByOrder = collect();
        foreach ($payments->groupBy('order_type') as $type => $rows) {
            $models[$type]::query()
                ->whereIn('id', $rows->pluck('order_id')->unique())
                ->get(['id', 'KdCust'])
                ->each(function ($order) use (&$kdCustByOrder, $type) {
                    $kdCustByOrder["{$type}-{$order->id}"] = $order->KdCust;
                });
        }

        // Potongan nota tunai mengurangi pembayaran tunai notanya, bukan baris refund terpisah.
        $cashDiscounts = $this->cashDiscountAllocations();
        foreach ($payments as $payment) {
            if (! in_array($payment->cara_bayar, ['tunai', 'qris', 'transfer'], true)) {
                continue;
            }

            $kdCust = $kdCustByOrder["{$payment->order_type}-{$payment->order_id}"] ?? null;
            $key = $kdCust ?: '-';

            if (! $customers->has($key)) {
                $customers->put($key, [
                    'kode' => $kdCust,
                    'nama' => $kdCust ?: 'Tanpa customer',
                    'indoor' => 0,
                    'outdoor' => 0,
                    'tunai' => 0.0,
                    'qris' => 0.0,
                    'transfer' => 0.0,
                    'no_order' => [],
                ]);
            }

            $entry = $customers->get($key);
            $entry[$payment->cara_bayar] += (float) $payment->jumlah
                + ($cashDiscounts['refund'][$payment->id] ?? 0.0)
                - ($cashDiscounts['receipt'][$payment->id] ?? 0.0);
            $customers->put($key, $entry);
        }

        $rows = $customers
            ->map(fn ($row) => $row + [
                'total' => $row['indoor'] + $row['outdoor'],
                'total_dibayar' => $row['tunai'] + $row['qris'] + $row['transfer'],
            ])
            ->sortByDesc('total_dibayar')
            ->values();

        return view('keuangan.rekap-kasir-customer', [
            'kasirUser' => $kasirUser,
            'dari' => $dari,
            'sampai' => $sampai,
            'rows' => $rows,
            'totalTunai' => $rows->sum('tunai'),
            'totalQris' => $rows->sum('qris'),
            'totalTransfer' => $rows->sum('transfer'),
        ]);
    }

    /**
     * Total order per customer — search-driven rather than listing every
     * customer, since with hundreds of customers a full list would just be
     * noise. Nothing is shown until a name/kode is searched (mirrors the
     * search pattern already used on CustomerController::index()).
     */
    public function rekapCustomer(Request $request): View
    {
        $search = $request->filled('search') ? $request->string('search')->toString() : null;

        $rows = collect();

        if ($search) {
            $customers = Customer::query()
                ->where('NmCust', 'like', "%{$search}%")
                ->orWhere('KdCust', 'like', "%{$search}%")
                ->orderBy('NmCust')
                ->limit(20)
                ->get();

            $models = ['Indoor' => OrderIndoor::class, 'Outdoor' => OrderOutdoor::class, 'Artwork' => OrderArtwork::class];
            $customerCodes = $customers->pluck('KdCust');
            $totalsByType = collect();

            foreach ($models as $label => $model) {
                $totalsByType->put($label, $model::query()
                    ->whereIn('KdCust', $customerCodes)
                    ->selectRaw('KdCust, COUNT(*) as jumlah_order, COALESCE(SUM(total), 0) as total_nilai, COALESCE(SUM(jumlah_dibayar), 0) as total_dibayar, COALESCE(SUM(jumlah_piutang), 0) as total_piutang')
                    ->groupBy('KdCust')
                    ->get()
                    ->keyBy('KdCust'));
            }

            $rows = $customers->map(function (Customer $customer) use ($models, $totalsByType) {
                $jumlahOrder = 0;
                $totalNilai = 0.0;
                $totalDibayar = 0.0;
                $totalPiutang = 0.0;
                $perTipe = [];

                foreach ($models as $label => $model) {
                    $totals = $totalsByType->get($label)->get($customer->KdCust);

                    if (! $totals) {
                        continue;
                    }

                    $jumlahOrder += (int) $totals->jumlah_order;
                    $totalNilai += (float) $totals->total_nilai;
                    $totalDibayar += (float) $totals->total_dibayar;
                    $totalPiutang += (float) $totals->total_piutang;
                    $perTipe[] = "{$label} {$totals->jumlah_order}";
                }

                return [
                    'kode' => $customer->KdCust,
                    'nama' => $customer->NmCust,
                    'jumlah_order' => $jumlahOrder,
                    'per_tipe' => implode(' · ', $perTipe),
                    'total_nilai' => $totalNilai,
                    'total_dibayar' => $totalDibayar,
                    'total_piutang' => $totalPiutang,
                ];
            })->sortByDesc('total_nilai')->values();
        }

        return view('keuangan.rekap-customer', [
            'search' => $search,
            'rows' => $rows,
        ]);
    }

    /**
     * Everyone who still owes money — hutang VIP and outstanding DP,
     * combined in one place. Neither had a single "who owes what" view
     * before this; hutang lived only as a running total on CustomerLimit,
     * DP was scattered across the Kasir DP tab.
     */
    public function piutang(): View
    {
        $rows = collect();

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $model::query()
                ->with('customer')
                ->whereIn('status_bayar', ['hutang', 'dp'])
                ->where('jumlah_piutang', '>', 0)
                ->orderBy('dibayar_at')
                ->get()
                ->each(function ($order) use (&$rows, $type) {
                    $rows->push([
                        'type' => $type,
                        'id' => $order->id,
                        'tipe' => ucfirst($type),
                        'no_order' => $order->NoOrder,
                        'customer' => $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-',
                        'jenis' => $order->status_bayar === 'hutang' ? 'Hutang VIP' : 'DP',
                        'status_bayar' => $order->status_bayar,
                        'total' => (float) $order->total,
                        'jumlah_piutang' => (float) $order->jumlah_piutang,
                        'sejak' => $order->dibayar_at,
                    ]);
                });
        }

        $rows = $rows->sortBy('sejak')->values();

        return view('keuangan.piutang', [
            'rows' => $rows,
            'totalHutang' => $rows->where('status_bayar', 'hutang')->sum('jumlah_piutang'),
            'totalDp' => $rows->where('status_bayar', 'dp')->sum('jumlah_piutang'),
        ]);
    }

    public function totalTagihanPiutang(Request $request): View
    {
        $asOf = $request->filled('tanggal')
            ? $request->string('tanggal')->toString()
            : now()->format('Y-m-d');
        $byCustomer = collect();

        foreach ([OrderIndoor::class, OrderOutdoor::class, OrderArtwork::class] as $model) {
            $model::query()->with('customer.limit')
                ->whereHas('customer.limit')
                ->whereDate('TglOrder', '<=', $asOf)
                ->where('status', '!=', 'batal')
                ->where('jumlah_piutang', '>', 0)
                ->get()
                ->each(function ($order) use (&$byCustomer) {
                    $key = $order->KdCust ?: 'tanpa-customer';
                    $current = $byCustomer->get($key, [
                        'name' => $order->customer?->NmCust ?: ($order->KdCust ?: 'Tanpa Customer'),
                        'phone' => $order->customer?->Telp ?: '-',
                        'date' => $order->TglOrder,
                        'receivable' => 0.0,
                    ]);
                    if ((string) $order->TglOrder < (string) $current['date']) {
                        $current['date'] = $order->TglOrder;
                    }
                    $current['receivable'] += (float) $order->jumlah_piutang;
                    $byCustomer->put($key, $current);
                });
        }

        $rows = $byCustomer->sortBy(fn (array $row) => mb_strtolower($row['name']))->values();

        return view('keuangan.total-tagihan-piutang', [
            'asOf' => $asOf,
            'rows' => $rows,
            'grandTotal' => (float) $rows->sum('receivable'),
        ]);
    }

    public function creditLimits(): View
    {
        $rows = Customer::query()->whereHas('limit')->with('limit')->orderBy('NmCust')->get()
            ->map(fn (Customer $customer) => (object) [
                'customer' => $customer->NmCust,
                'receivable' => max(0, (float) $customer->limit->Total),
                'limit' => (float) $customer->limit->Batas,
            ]);

        return view('keuangan.credit-limits', [
            'rows' => $rows,
            'totalReceivable' => (float) $rows->sum('receivable'),
            'totalLimit' => (float) $rows->sum('limit'),
        ]);
    }

    public function globalCustomerReceivables(Request $request): View
    {
        $asOf = $request->filled('tanggal')
            ? $request->string('tanggal')->toString()
            : now()->format('Y-m-d');
        $customers = collect();

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $settlementOrderIds = OrderPayment::query()->where('order_type', $type)
                ->where('jenis', 'pelunasan_hutang')->pluck('order_id');
            $orders = $model::query()->with('customer')
                ->whereDate('TglOrder', '<=', $asOf)
                ->where('status', '!=', 'batal')
                ->where(function ($query) use ($settlementOrderIds) {
                    $query->where('status_bayar', 'hutang');
                    if ($settlementOrderIds->isNotEmpty()) {
                        $query->orWhereIn('id', $settlementOrderIds);
                    }
                })->get();
            $paymentsByOrder = OrderPayment::query()->where('order_type', $type)
                ->where('jenis', 'pelunasan_hutang')->whereIn('order_id', $orders->pluck('id'))
                ->where('created_at', '<=', "{$asOf} 23:59:59")
                ->get()->groupBy('order_id');
            $finalDiscountsByOrder = FinalSalesDiscount::query()
                ->where('order_type', $type)->whereIn('order_id', $orders->pluck('id'))
                ->whereDate('transaction_date', '<=', $asOf)
                ->selectRaw('order_id, SUM(discount_amount) AS total_discount')
                ->groupBy('order_id')->pluck('total_discount', 'order_id');

            foreach ($orders as $order) {
                $gross = (float) $order->total;
                $initialDiscount = $order->diskon_approved_at && $order->diskon_approved_at->format('Y-m-d') <= $asOf
                    ? $order->diskonAwalNominal() : 0.0;
                $discount = $initialDiscount + (float) ($finalDiscountsByOrder[$order->id] ?? 0);
                $net = max(0, $gross - $discount);
                $paid = min($net, max(0, (float) ($paymentsByOrder[$order->id] ?? collect())->sum('jumlah')));
                $remaining = max(0, $net - $paid);
                if ($remaining <= 0) {
                    continue;
                }

                $key = $order->KdCust ?: 'tanpa-customer';
                $row = $customers->get($key, [
                    'code' => $order->KdCust,
                    'customer' => $order->customer?->NmCust ?: ($order->KdCust ?: 'Tanpa Customer'),
                    'receivable' => 0.0, 'discount' => 0.0, 'paid' => 0.0, 'remaining' => 0.0,
                ]);
                $row['receivable'] += $gross;
                $row['discount'] += $discount;
                $row['paid'] += $paid;
                $row['remaining'] += $remaining;
                $customers->put($key, $row);
            }
        }

        $rows = $customers->sortBy(fn (array $row) => mb_strtolower($row['customer']))->values();
        $totals = (object) collect(['receivable', 'discount', 'paid', 'remaining'])
            ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all();

        return view('keuangan.global-customer-receivables', compact('asOf', 'rows', 'totals'));
    }

    /**
     * Rekap Piutang Customer: semua piutang, VIP (nota hutang) maupun
     * Reguler (sisa DP), posisi sampai tanggal tertentu.
     */
    public function customerReceivablesRecap(Request $request): View
    {
        $asOf = $request->date('tanggal')?->format('Y-m-d') ?? now()->format('Y-m-d');
        $segment = in_array($request->query('jenis'), ['vip', 'reguler'], true) ? $request->query('jenis') : 'semua';
        $customers = collect();

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            // Order yang pernah berstatus piutang: hutang VIP, DP, atau sudah dilunasi setelah tanggal posisi.
            $settledIds = OrderPayment::query()->where('order_type', $type)
                ->whereIn('jenis', ['pelunasan_hutang', 'pelunasan_dp'])->pluck('order_id');
            $orders = $model::query()->with('customer')
                ->whereDate('TglOrder', '<=', $asOf)
                ->where('status', '!=', 'batal')
                ->where(function ($query) use ($settledIds) {
                    $query->whereIn('status_bayar', ['hutang', 'dp']);
                    if ($settledIds->isNotEmpty()) {
                        $query->orWhereIn('id', $settledIds);
                    }
                })->get();
            if ($orders->isEmpty()) {
                continue;
            }

            $paymentsByOrder = OrderPayment::query()->where('order_type', $type)
                ->whereIn('order_id', $orders->pluck('id'))
                ->where('jumlah', '>', 0)->where('jenis', '!=', 'refund')
                ->where('created_at', '<=', "{$asOf} 23:59:59")
                ->get(['order_id', 'jenis', 'jumlah'])->groupBy('order_id');
            $finalDiscountsByOrder = FinalSalesDiscount::query()
                ->where('order_type', $type)->whereIn('order_id', $orders->pluck('id'))
                ->whereDate('transaction_date', '<=', $asOf)
                ->selectRaw('order_id, SUM(discount_amount) AS total_discount')
                ->groupBy('order_id')->pluck('total_discount', 'order_id');

            foreach ($orders as $order) {
                $payments = $paymentsByOrder[$order->id] ?? collect();
                $isRegular = $order->status_bayar === 'dp' || $payments->contains('jenis', 'dp');
                if (($segment === 'vip' && $isRegular) || ($segment === 'reguler' && ! $isRegular)) {
                    continue;
                }

                $gross = (float) $order->total;
                $initialDiscount = $order->diskon_approved_at && $order->diskon_approved_at->format('Y-m-d') <= $asOf
                    ? $order->diskonAwalNominal() : 0.0;
                $discount = $initialDiscount + (float) ($finalDiscountsByOrder[$order->id] ?? 0);
                $net = max(0, $gross - $discount);
                // VIP: hanya pelunasan hutang; Reguler: DP + pelunasan DP.
                $paidJenis = $isRegular ? ['dp', 'pelunasan_dp'] : ['pelunasan_hutang'];
                $paid = min($net, max(0, (float) $payments->whereIn('jenis', $paidJenis)->sum('jumlah')));
                $remaining = max(0, $net - $paid);
                if ($remaining <= 0) {
                    continue;
                }

                $key = $order->KdCust ?: 'tanpa-customer';
                $row = $customers->get($key, [
                    'code' => $order->KdCust,
                    'customer' => $order->customer?->NmCust ?: ($order->KdCust ?: 'Tanpa Customer'),
                    'has_vip' => false,
                    'receivable' => 0.0, 'discount' => 0.0, 'paid' => 0.0, 'remaining' => 0.0,
                ]);
                $row['has_vip'] = $row['has_vip'] || ! $isRegular;
                $row['receivable'] += $gross;
                $row['discount'] += $discount;
                $row['paid'] += $paid;
                $row['remaining'] += $remaining;
                $customers->put($key, $row);
            }
        }

        $rows = $customers->sortBy(fn (array $row) => mb_strtolower($row['customer']))->values();
        $totals = (object) collect(['receivable', 'discount', 'paid', 'remaining'])
            ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all();

        return view('keuangan.customer-receivables-recap', compact('asOf', 'segment', 'rows', 'totals'));
    }

    public function customerReceivableDetails(Request $request): View
    {
        // Piutang tidak dibatasi periode: tampilkan seluruh nota yang belum lunas.
        $customerSearch = $request->string('customer')->trim()->toString();
        // Laporan Piutang per Customer: pilihan hanya customer VIP (ber-plafon), karena hanya mereka yang berpiutang.
        $customers = Customer::query()
            ->when($request->input('mode') === 'report', fn ($query) => $query->whereHas('limit'))
            ->orderBy('NmCust')->get(['KdCust', 'NmCust']);
        $needle = mb_strtolower($customerSearch);
        $selectedCustomer = $customerSearch !== ''
            ? ($customers->firstWhere('KdCust', $customerSearch)
                ?? $customers->first(fn ($customer) => mb_strtolower((string) $customer->NmCust) === $needle)
                ?? $customers->first(fn ($customer) => str_starts_with(mb_strtolower((string) $customer->NmCust), $needle)))
            : null;
        $customerCode = (string) ($selectedCustomer?->KdCust ?? '');
        // Rentang Tgl. Order hanya untuk laporan Piutang per Customer; halaman pembayaran tetap semua nota.
        $reportMode = $request->input('mode') === 'report';
        $dari = $reportMode && $request->filled('dari') ? $request->date('dari')?->format('Y-m-d') : null;
        $sampai = $reportMode && $request->filled('sampai') ? $request->date('sampai')?->format('Y-m-d') : null;
        $rows = collect();

        if ($selectedCustomer) {
            foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
                $orders = $model::query()->where('KdCust', $customerCode)
                    ->where('status', '!=', 'batal')
                    ->where('status_bayar', 'hutang')
                    ->where('jumlah_piutang', '>', 0)
                    // Hutang boleh dibayar walau barang belum diambil (belum ber-DO/INV).
                    ->when($dari, fn ($query) => $query->whereDate('TglOrder', '>=', $dari))
                    ->when($sampai, fn ($query) => $query->whereDate('TglOrder', '<=', $sampai))
                    ->orderBy('TglOrder')->orderBy('NoOrder')->get();
                // Menu Pembayaran Piutang juga menampilkan SO DP yang masih bersisa
                // (belum tentu ber-DO); laporan Piutang per Customer tetap hutang saja.
                if (! $reportMode) {
                    $orders = $orders->concat($model::query()->where('KdCust', $customerCode)
                        ->where('status', '!=', 'batal')
                        ->whereNull('invoice_voided_at')
                        ->where('status_bayar', 'dp')
                        ->where('jumlah_piutang', '>', 0)
                        ->get());
                }
                $finalDiscountsByOrder = FinalSalesDiscount::query()
                    ->where('order_type', $type)->whereIn('order_id', $orders->pluck('id'))
                    ->selectRaw('order_id, SUM(discount_amount) AS total_discount')
                    ->groupBy('order_id')->pluck('total_discount', 'order_id');
                $invoiceNumbers = DB::table('order_documents')->where('kind', 'inv')->where('order_type', $type)
                    ->whereIn('order_id', $orders->pluck('id'))->orderByDesc('sequence')->get(['order_id', 'number'])
                    ->unique('order_id')->pluck('number', 'order_id');
                // Cicilan yang benar-benar diterima (sumber sama dengan laporan kas & jurnal).
                $receivedByOrder = OrderPayment::query()->where('order_type', $type)->where('jenis', 'pelunasan_hutang')
                    ->whereIn('order_id', $orders->pluck('id'))
                    ->selectRaw('order_id, SUM(jumlah) AS total')->groupBy('order_id')->pluck('total', 'order_id');

                foreach ($orders as $order) {
                    if ($order->status_bayar === 'dp') {
                        // Sisa DP dihitung dari total berjalan − DP diterima, sama dengan kasir.
                        $paid = (float) $order->jumlah_dibayar;
                        $rows->push((object) [
                            'type' => $type,
                            'id' => $order->id,
                            'date' => $order->TglOrder,
                            'order_number' => $order->NoOrder,
                            'invoice_number' => null,
                            'is_dp' => true,
                            'received' => $paid,
                            'invoice' => $order->NoOrder,
                            'receivable' => (float) $order->total, 'discount' => $order->diskonStatus() === 'approved' ? $order->diskonNominal() : 0.0,
                            'paid' => $paid, 'remaining' => max(0, $order->hutangAmount() - $paid),
                        ]);

                        continue;
                    }
                    $gross = (float) $order->total;
                    $initialDiscount = $order->diskon_approved_at ? $order->diskonAwalNominal() : 0.0;
                    $discount = $initialDiscount + (float) ($finalDiscountsByOrder[$order->id] ?? 0);
                    $net = max(0, $gross - $discount);
                    $remaining = max(0, (float) $order->jumlah_piutang);
                    $paid = max(0, $net - $remaining);
                    $rows->push((object) [
                        'type' => $type,
                        'id' => $order->id,
                        'date' => $order->TglOrder,
                        'order_number' => $order->NoOrder,
                        'invoice_number' => $invoiceNumbers[$order->id] ?? null,
                        'is_dp' => false,
                        'received' => (float) ($receivedByOrder[$order->id] ?? 0),
                        'invoice' => $invoiceNumbers[$order->id] ?? $order->NoOrder,
                        'receivable' => $gross, 'discount' => $discount,
                        'paid' => $paid, 'remaining' => $remaining,
                    ]);
                }
            }
        }

        // SO DP di atas, lalu nota hutang; masing-masing urut Tgl. Order terlama.
        $rows = $rows->sortBy(fn ($row) => ($row->is_dp ? '0' : '1').'|'.$row->date.'|'.$row->invoice)->values();
        $totals = (object) collect(['receivable', 'discount', 'paid', 'remaining'])
            ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all();

        return view('keuangan.customer-receivable-details', compact(
            'customers', 'selectedCustomer', 'customerCode', 'rows', 'totals', 'dari', 'sampai'
        ));
    }

    /**
     * Proses Pembayaran Piutang per Customer: beberapa nota sekaligus, satu
     * cara bayar. Pencatatan per nota sama dengan tombol Lunasi kasir
     * (lihat App\Services\ReceivableBatchPayment).
     */
    public function storeCustomerReceivablePayments(Request $request, ReceivableBatchPayment $payments): RedirectResponse
    {
        $data = $request->validate([
            'customer' => ['required', 'string', 'exists:customers,KdCust'],
            'cara_bayar' => ['required', Rule::in(array_keys(ReceivableBatchPayment::METHODS))],
            'no_referensi' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn () => $request->input('cara_bayar') !== 'tunai')],
            'keterangan' => ['nullable', 'string', 'max:150'],
            'bayar' => ['required', 'array'],
            'bayar.*' => ['nullable', 'string', 'max:20'],
        ], [
            'no_referensi.required' => 'No. referensi wajib diisi untuk pembayaran QRIS/Transfer.',
        ]);

        $lines = collect($data['bayar'])
            ->map(fn ($amount, $key) => [
                'key' => (string) $key,
                'amount' => (float) preg_replace('/\D/', '', (string) $amount),
            ])
            ->filter(fn ($line) => $line['amount'] > 0 && preg_match('/^(indoor|outdoor|artwork)-\d+$/', $line['key']))
            ->map(function ($line) {
                [$type, $id] = explode('-', $line['key']);

                return ['type' => $type, 'id' => (int) $id, 'amount' => $line['amount']];
            })
            ->values()->all();

        if ($lines === []) {
            throw ValidationException::withMessages(['pembayaran' => 'Isi nominal pembayaran minimal pada satu nota.']);
        }

        $method = $data['cara_bayar'];
        $reference = $method === 'tunai' ? null : trim((string) $data['no_referensi']);
        $result = $payments->pay($data['customer'], $lines, $method, $reference, trim((string) ($data['keterangan'] ?? '')) ?: null);

        return redirect()->route('keuangan.customer-receivable-details', ['customer' => $data['customer']])
            ->with('receivable_payment', [
                'count' => $result['count'],
                'settled' => $result['settled'],
                'total' => $result['total'],
                'method' => ReceivableBatchPayment::METHODS[$method].($reference ? ' · Ref '.$reference : ''),
                'date' => now()->format('Y-m-d'),
            ]);
    }

    public function dailyReceivableCollections(Request $request): View
    {
        $date = $request->filled('tanggal') ? $request->string('tanggal')->toString() : now()->format('Y-m-d');
        $rows = collect();

        // Aktivitas SO pada tanggal laporan: lunas, DP, hutang VIP, dan DO/Invoice terbit.
        // Cicilan piutang lama dan nota tanpa aktivitas hari itu tidak ditampilkan.
        $dayStart = "{$date} 00:00:00";
        $dayEnd = "{$date} 23:59:59";
        $paymentLabels = ['lunas' => 'Lunas', 'pelunasan_dp' => 'Lunas', 'dp' => 'DP'];

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $todayPayments = OrderPayment::query()->where('order_type', $type)
                ->whereIn('jenis', array_keys($paymentLabels))
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->orderBy('created_at')->get()->groupBy('order_id');
            $hutangIds = $model::query()->where('metode_bayar', 'hutang')
                ->whereBetween('dibayar_at', [$dayStart, $dayEnd])->pluck('id');
            $documentsToday = DB::table('order_documents')->where('order_type', $type)
                ->whereIn('kind', ['do', 'inv'])->whereBetween('issued_at', [$dayStart, $dayEnd])
                ->get(['order_id', 'kind'])->groupBy('order_id');

            $orderIds = $todayPayments->keys()->merge($hutangIds)->merge($documentsToday->keys())->unique();
            if ($orderIds->isEmpty()) {
                continue;
            }
            $orders = $model::query()->with('customer')->whereIn('id', $orderIds)->where('status', '!=', 'batal')->get();
            $paidToDate = OrderPayment::query()->where('order_type', $type)->whereIn('order_id', $orders->pluck('id'))
                ->where('jumlah', '>', 0)->where('created_at', '<=', $dayEnd)
                ->selectRaw('order_id, SUM(jumlah) AS total')->groupBy('order_id')->pluck('total', 'order_id');

            foreach ($orders as $order) {
                $payments = $todayPayments[$order->id] ?? collect();
                $gross = (float) $order->total;
                $discount = $order->diskon_approved_at && $order->diskon_approved_at->format('Y-m-d') <= $date
                    ? $order->diskonNominal() : 0.0;
                $net = max(0, $gross - $discount);

                $labels = $payments->map(fn (OrderPayment $payment) => $paymentLabels[$payment->jenis])->unique()->values();
                if ($hutangIds->contains($order->id)) {
                    $labels->push('Hutang VIP');
                }
                foreach (($documentsToday[$order->id] ?? collect())->pluck('kind')->unique() as $kind) {
                    $labels->push($kind === 'do' ? 'DO' : 'Invoice');
                }
                $methods = $payments->map(function (OrderPayment $payment) {
                    $method = OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? ucfirst((string) $payment->cara_bayar);

                    return $method.($payment->no_referensi ? ' '.$payment->no_referensi : '');
                })->unique()->implode(', ');

                $rows->push((object) [
                    'customer_code' => $order->KdCust ?: '-',
                    'customer' => $order->customer?->NmCust ?: ($order->KdCust ?: 'Tanpa Customer'),
                    'date' => $order->TglOrder, 'order' => $order->NoOrder,
                    'receivable' => $gross, 'paid' => max(0, (float) $payments->sum('jumlah')),
                    'discount' => $discount,
                    'remaining' => max(0, $net - (float) ($paidToDate[$order->id] ?? 0)),
                    'notes' => $labels->implode(' · ').($methods !== '' ? ' — '.$methods : ''),
                ]);
            }
        }

        $groups = $rows->sortBy(fn ($row) => mb_strtolower($row->customer).'|'.$row->date.'|'.$row->order)
            ->groupBy('customer_code')->map(function ($customerRows) {
                return (object) [
                    'customer' => $customerRows->first()->customer,
                    'rows' => $customerRows->values(),
                    'totalPaid' => (float) $customerRows->sum('paid'),
                ];
            })->sortBy(fn ($group) => mb_strtolower($group->customer))->values();

        return view('keuangan.daily-receivable-collections', [
            'date' => $date, 'groups' => $groups,
            'grandTotalPaid' => (float) $rows->sum('paid'),
        ]);
    }

    /**
     * Laba Rugi (income statement) — every posting the app has made via
     * AccountingService lands in `am` (JurnalEntry) tagged to an account in
     * `am__` (Akun). This just sums Debet/Kredit per L-account (TipeNL='L')
     * within the period and arranges them into the standard Pendapatan −
     * HPP − Biaya Usaha (+ pendapatan/biaya lain-lain) waterfall.
     */
    public function labaRugi(Request $request): View
    {
        $dari = $request->filled('dari') ? $request->string('dari')->toString() : now()->startOfMonth()->format('Y-m-d');
        $sampai = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->format('Y-m-d');

        $akunLabaRugi = Akun::query()->where('TipeNL', 'L')->orderBy('NoAkun')->get();

        $saldo = JurnalEntry::query()
            ->whereIn('NoAkun', $akunLabaRugi->pluck('NoAkun'))
            ->whereBetween('TgTrans', [$dari, $sampai])
            ->select('NoAkun', DB::raw('SUM(Debet) as debet'), DB::raw('SUM(Kredit) as kredit'))
            ->groupBy('NoAkun')
            ->get()
            ->keyBy('NoAkun');

        $biayaLainKodes = ['74000'];

        $buildGroup = function (string $prefix) use ($akunLabaRugi, $saldo, $biayaLainKodes) {
            return $akunLabaRugi->filter(fn (Akun $a) => str_starts_with($a->NoAkun, $prefix))
                ->map(function (Akun $a) use ($saldo, $biayaLainKodes) {
                    $s = $saldo->get($a->NoAkun);
                    $debet = (float) ($s->debet ?? 0);
                    $kredit = (float) ($s->kredit ?? 0);
                    $isExpenseLike = $a->TipeDK === 'D' || in_array($a->NoAkun, $biayaLainKodes, true);

                    return [
                        'akun' => $a->NoAkun,
                        'nama' => $a->NmAkun,
                        'debet' => $debet,
                        'kredit' => $kredit,
                        'saldo' => $isExpenseLike ? $debet - $kredit : $kredit - $debet,
                    ];
                })
                ->values();
        };

        $pendapatan = $buildGroup('4');
        $hpp = $buildGroup('5');
        $biayaUsaha = $buildGroup('6');
        $pendapatanLain = $buildGroup('7')->reject(fn ($r) => in_array($r['akun'], $biayaLainKodes, true))->values();
        $biayaLain = $buildGroup('7')->filter(fn ($r) => in_array($r['akun'], $biayaLainKodes, true))->values();

        $totalPendapatan = $pendapatan->sum('saldo');
        $totalHpp = $hpp->sum('saldo');
        $labaKotor = $totalPendapatan - $totalHpp;
        $totalBiayaUsaha = $biayaUsaha->sum('saldo');
        $labaUsaha = $labaKotor - $totalBiayaUsaha;
        $totalPendapatanLain = $pendapatanLain->sum('saldo');
        $totalBiayaLain = $biayaLain->sum('saldo');
        $labaBersih = $labaUsaha + $totalPendapatanLain - $totalBiayaLain;

        return view('keuangan.laba-rugi', [
            'dari' => $dari,
            'sampai' => $sampai,
            'pendapatan' => $pendapatan,
            'hpp' => $hpp,
            'biayaUsaha' => $biayaUsaha,
            'pendapatanLain' => $pendapatanLain,
            'biayaLain' => $biayaLain,
            'totalPendapatan' => $totalPendapatan,
            'totalHpp' => $totalHpp,
            'labaKotor' => $labaKotor,
            'totalBiayaUsaha' => $totalBiayaUsaha,
            'labaUsaha' => $labaUsaha,
            'totalPendapatanLain' => $totalPendapatanLain,
            'totalBiayaLain' => $totalBiayaLain,
            'labaBersih' => $labaBersih,
        ]);
    }

    /**
     * PPN Keluaran — only active issued invoices in the invoice period.
     * Orders without an invoice, including paid orders and VIP credit
     * pickups with only a delivery order, never appear. Prices include PPN
     * (standard retail practice here), so DPP = total / (1 + rate) and
     * PPN = total - DPP. Rate is adjustable in case the applicable rate
     * changes or differs per case; nothing about which rows appear is
     * user-selectable — this is a complete report of realized sales.
     */
    public function laporanPpn(Request $request): View
    {
        [$dari, $sampai, $rate, $rows] = $this->ppnData($request);
        $periode = substr($dari, 0, 7);
        $laporanFinal = LaporanPpnFinal::with('items')->where('periode', $periode)->first();

        // A locked report is a historical snapshot: later edits to an order
        // or changing the default PPN rate must never alter its contents.
        if ($laporanFinal?->status === 'final') {
            $rate = (float) $laporanFinal->tarif_ppn;
            $rows = $laporanFinal->items->map(fn ($item) => [
                'id' => $item->order_id,
                'type_key' => $item->order_type,
                'key' => $item->order_type.'-'.$item->order_id,
                'tanggal' => $item->tanggal_lunas,
                'tipe' => ucfirst($item->order_type),
                'no_order' => $item->no_order,
                'customer' => $item->customer ?: '-',
                'total' => $item->total,
                'dpp' => $item->dpp,
                'ppn' => $item->ppn,
            ])->values();
        }

        return view('keuangan.laporan-ppn', [
            'dari' => $dari,
            'sampai' => $sampai,
            'rate' => $rate,
            'rows' => $rows,
            'totalOmzet' => $rows->sum('total'),
            'totalDpp' => $rows->sum('dpp'),
            'totalPpn' => $rows->sum('ppn'),
            'periode' => $periode,
            'laporanFinal' => $laporanFinal,
            'selectedKeys' => $laporanFinal?->items->map(fn ($item) => $item->order_type.'-'.$item->order_id)->all() ?? [],
        ]);
    }

    public function simpanDraftPpn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dari' => ['required', 'date'],
            'sampai' => ['required', 'date'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'selected' => ['nullable', 'array'],
            'selected.*' => ['string'],
        ]);

        $periode = substr($data['dari'], 0, 7);
        abort_unless(substr($data['sampai'], 0, 7) === $periode, 422, 'Draft PPN harus dibuat untuk satu bulan yang sama.');

        [$dari, $sampai, $rate, $rows] = $this->ppnData(Request::create('/', 'GET', $data));
        $rowsByKey = $rows->keyBy('key');
        $selectedRows = collect($data['selected'] ?? [])->unique()->map(fn ($key) => $rowsByKey->get($key))->filter();

        $report = LaporanPpnFinal::firstOrCreate(
            ['periode' => $periode],
            ['tarif_ppn' => $rate, 'status' => 'draft', 'created_by' => auth()->id()],
        );
        abort_if($report->status === 'final', 422, 'Laporan PPN Final sudah dikunci dan tidak dapat diubah.');

        DB::transaction(function () use ($report, $rate, $selectedRows) {
            $report = LaporanPpnFinal::lockForUpdate()->findOrFail($report->id);
            abort_if($report->status === 'final', 422, 'Laporan sudah final.');
            $report->update(['tarif_ppn' => $rate]);
            $report->items()->delete();
            $report->items()->createMany($selectedRows->map(fn ($row) => [
                'order_type' => $row['type_key'], 'order_id' => $row['id'],
                'tanggal_lunas' => $row['tanggal'], 'no_order' => $row['no_order'],
                'customer' => $row['customer'], 'total' => $row['total'], 'dpp' => $row['dpp'], 'ppn' => $row['ppn'],
            ])->all());
        });

        return redirect()->route('keuangan.laporan-ppn', compact('dari', 'sampai', 'rate'))->with('status', 'Draft Laporan PPN disimpan.');
    }

    public function finalkanPpn(LaporanPpnFinal $laporanPpnFinal): RedirectResponse
    {
        DB::transaction(function () use ($laporanPpnFinal) {
            $report = LaporanPpnFinal::lockForUpdate()->findOrFail($laporanPpnFinal->id);
            abort_if($report->status === 'final', 422, 'Laporan ini sudah dikunci.');
            abort_unless($report->items()->exists(), 422, 'Pilih minimal satu transaksi.');

            $report->load('items');
            $invoices = app(OrderDocumentService::class)->invoiceQuery()
                ->whereIn('order_type', $report->items->pluck('order_type')->unique())
                ->whereIn('order_id', $report->items->pluck('order_id')->unique())
                ->get()
                ->keyBy(fn ($invoice) => $invoice->order_type.'-'.$invoice->order_id);

            foreach ($report->items as $item) {
                $invoice = $invoices->get($item->order_type.'-'.$item->order_id);
                abort_unless($invoice && $invoice->number === $item->no_order
                    && substr($invoice->issued_at, 0, 7) === $report->periode
                    && abs((float) $invoice->total - (float) $item->total) < 0.01,
                    422, 'Draft tidak sesuai invoice aktif. Simpan ulang draft sebelum finalisasi.');
            }
            $report->update(['status' => 'final', 'finalized_at' => now(), 'finalized_by' => auth()->id()]);
        });

        return redirect()->route('keuangan.laporan-ppn', ['dari' => $laporanPpnFinal->periode.'-01', 'sampai' => Carbon::parse($laporanPpnFinal->periode.'-01')->endOfMonth()->format('Y-m-d'), 'rate' => $laporanPpnFinal->tarif_ppn])->with('status', 'Laporan PPN Final dikunci sebagai histori.');
    }

    /**
     * CSV recap of the same PPN report — a plain accounting export (No,
     * Tanggal, No Order, Customer, DPP, PPN, Total) meant to hand to an
     * accountant or paste into a spreadsheet for SPT Masa PPN prep. This is
     * NOT a DJP e-Faktur bulk-import file — that format requires each
     * buyer's NPWP/address, which this app doesn't collect.
     */
    public function exportPpn(Request $request): StreamedResponse
    {
        [$dari, $sampai, $rate, $rows] = $this->ppnData($request);
        $laporanFinal = LaporanPpnFinal::with('items')->where('periode', substr($dari, 0, 7))->where('status', 'final')->first();

        if ($laporanFinal) {
            $rate = (float) $laporanFinal->tarif_ppn;
            $rows = $laporanFinal->items->map(fn ($item) => [
                'tanggal' => $item->tanggal_lunas, 'tipe' => ucfirst($item->order_type),
                'no_order' => $item->no_order, 'customer' => $item->customer ?: '-',
                'total' => $item->total, 'dpp' => $item->dpp, 'ppn' => $item->ppn,
            ]);
        }

        $filename = "laporan-ppn_{$dari}_{$sampai}.csv";

        return response()->streamDownload(function () use ($dari, $sampai, $rate, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel renders "Rp"/accented names correctly

            fputcsv($out, ["Laporan PPN Keluaran {$dari} s/d {$sampai} (tarif {$rate}%)"]);
            fputcsv($out, []);
            fputcsv($out, ['No', 'Tanggal Invoice', 'Tipe', 'No Invoice', 'Customer', 'Total', 'DPP', 'PPN']);

            foreach ($rows as $i => $row) {
                fputcsv($out, [
                    $i + 1,
                    $row['tanggal']?->format('Y-m-d'),
                    $row['tipe'],
                    $row['no_order'],
                    $row['customer'],
                    number_format($row['total'], 2, '.', ''),
                    number_format($row['dpp'], 2, '.', ''),
                    number_format($row['ppn'], 2, '.', ''),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['', '', '', '', 'Total', number_format($rows->sum('total'), 2, '.', ''), number_format($rows->sum('dpp'), 2, '.', ''), number_format($rows->sum('ppn'), 2, '.', '')]);

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array{0: string, 1: string, 2: float, 3: Collection}
     */
    private function ppnData(Request $request): array
    {
        $periode = $request->string('periode')->toString();
        $isMonthlyPeriod = preg_match('/^\d{4}-\d{2}$/', $periode) === 1;
        $dari = $isMonthlyPeriod ? $periode.'-01' : ($request->filled('dari') ? $request->string('dari')->toString() : now()->startOfMonth()->format('Y-m-d'));
        $sampai = $isMonthlyPeriod ? Carbon::parse($dari)->endOfMonth()->format('Y-m-d') : ($request->filled('sampai') ? $request->string('sampai')->toString() : now()->format('Y-m-d'));
        $rate = $request->filled('rate') ? (float) $request->input('rate') : PengaturanKeuangan::current()->tarif_ppn_default;

        $rows = collect();

        app(OrderDocumentService::class)->invoiceQuery()
            ->whereBetween('issued_at', ["{$dari} 00:00:00", "{$sampai} 23:59:59"])
            ->get()->each(function ($invoice) use (&$rows, $rate) {
                $snapshot = json_decode($invoice->snapshot, true);
                $total = (float) $invoice->total;
                $dpp = $rate > 0 ? $total / (1 + $rate / 100) : $total;
                $rows->push([
                    'id' => $invoice->order_id, 'type_key' => $invoice->order_type,
                    'key' => $invoice->order_type.'-'.$invoice->order_id,
                    'tanggal' => Carbon::parse($invoice->issued_at), 'tipe' => ucfirst($invoice->order_type),
                    'no_order' => $invoice->number, 'customer' => ($snapshot['customer'] ?? null) ?: '-',
                    'total' => $total, 'dpp' => $dpp, 'ppn' => $total - $dpp,
                ]);
            });

        return [$dari, $sampai, $rate, $rows->sortBy('tanggal')->values()];
    }
}
