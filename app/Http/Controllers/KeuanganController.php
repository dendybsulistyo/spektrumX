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
use App\Models\PengaturanKeuangan;
use App\Models\User;
use App\Services\OrderDocumentService;
use App\Support\SimpleXlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KeuanganController extends Controller
{
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

        $activityUserIds = OrderPayment::query()->whereNotNull('user_id')->pluck('user_id')
            ->merge(CashDailyEntry::query()->whereNotNull('user_id')->pluck('user_id'))
            ->merge(FinalSalesDiscount::query()->whereNotNull('user_id')->pluck('user_id'))
            ->unique();

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
            ->when($kasirId, fn ($query) => $query->where('user_id', $kasirId))
            ->orderBy('created_at')
            ->get();

        // Resolve NoOrder/customer per order without N+1 — group ids by
        // type, one query per type, then map back onto each payment row.
        $models = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];
        $ordersByKey = collect();

        foreach ($payments->groupBy('order_type') as $type => $rows) {
            $model = $models[$type] ?? null;
            if (! $model) {
                continue;
            }

            $model::query()->with('customer')->whereIn('id', $rows->pluck('order_id')->unique())->get()
                ->each(function ($order) use (&$ordersByKey, $type) {
                    $ordersByKey["{$type}-{$order->id}"] = $order;
                });
        }

        $paymentRows = $payments->flatMap(function (OrderPayment $p) use ($ordersByKey) {
            $order = $ordersByKey["{$p->order_type}-{$p->order_id}"] ?? null;
            $jumlah = (float) $p->jumlah;
            $customer = $order?->customer?->NmCust
                ? mb_strtoupper($order->customer->NmCust)
                : '-';
            $jenis = OrderPayment::JENIS_LABELS[$p->jenis] ?? ucfirst($p->jenis);
            $baseSort = $p->created_at?->format('His').'-'.str_pad((string) $p->id, 10, '0', STR_PAD_LEFT);

            if ($jumlah < 0) {
                return [[
                    'user_id' => $p->user_id,
                    'kasir' => $p->user?->name ?? '-',
                    'no_nota' => $order?->NoOrder,
                    'keterangan' => 'Refund - '.$customer,
                    'debet' => 0,
                    'kredit' => abs($jumlah),
                    'sort' => '2-'.$baseSort.'-0',
                ]];
            }

            $rows = [[
                'user_id' => $p->user_id,
                'kasir' => $p->user?->name ?? '-',
                'no_nota' => $order?->NoOrder,
                'keterangan' => $jenis.' - '.$customer,
                'debet' => $jumlah,
                'kredit' => 0,
                'sort' => '2-'.$baseSort.'-0',
            ]];

            // Pembayaran non-tunai mengurangi uang yang seharusnya berada
            // di laci kas. Karena itu transaksi ditampilkan berpasangan:
            // nilai nota di Debet dan pembayaran bank/QRIS di Kredit.
            if (in_array($p->cara_bayar, ['transfer', 'qris'], true)) {
                $method = OrderPayment::CARA_BAYAR_LABELS[$p->cara_bayar] ?? ucfirst($p->cara_bayar);
                $rows[] = [
                    'user_id' => $p->user_id,
                    'kasir' => $p->user?->name ?? '-',
                    'no_nota' => null,
                    'keterangan' => 'Bayar via '.$method.' - '.$customer,
                    'debet' => 0,
                    'kredit' => $jumlah,
                    'sort' => '2-'.$baseSort.'-1',
                ];
            }

            return $rows;
        });

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

        $discountRows = FinalSalesDiscount::with(['user', 'customer'])
            ->whereDate('transaction_date', $tanggal)
            ->when($kasirId, fn ($query) => $query->where('user_id', $kasirId))
            ->orderBy('id')
            ->get()
            ->map(fn (FinalSalesDiscount $discount) => [
                'user_id' => $discount->user_id,
                'kasir' => $discount->user?->name ?? '-',
                'no_nota' => $discount->order_number,
                'keterangan' => 'Potongan Penjualan Rp '.number_format($discount->discount_amount, 0, ',', '.')
                    .' - '.($discount->customer?->NmCust ?? 'Customer tidak tersedia').' - '.$discount->reason,
                'debet' => 0.0,
                'kredit' => 0.0,
                'sort' => '3-'.str_pad((string) $discount->id, 10, '0', STR_PAD_LEFT),
            ]);

        if (! $seededRows->contains(fn (array $row) => $row['user_id'] === null)) {
            $openingBalance = $this->openingCashBalance($tanggal);
            $seededRows->prepend([
                'user_id' => null,
                'kasir' => '-',
                'no_nota' => null,
                'keterangan' => 'Saldo Awal',
                'debet' => max(0, $openingBalance),
                'kredit' => max(0, -$openingBalance),
                'sort' => '0-opening',
            ]);
        }

        $rows = $seededRows->concat($paymentRows)->concat($discountRows)->sortBy('sort')->values();
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

    /**
     * Membawa saldo penutupan terakhir sebagai saldo awal tanggal berikutnya.
     * Baris Saldo Awal manual menjadi checkpoint; setelah itu hanya mutasi
     * tunai yang menambah/mengurangi uang di laci kas.
     */
    private function openingCashBalance(string $date): float
    {
        $checkpointDate = CashDailyEntry::query()
            ->whereNull('user_id')
            ->whereRaw('LOWER(keterangan) = ?', ['saldo awal'])
            ->whereDate('tanggal', '<', $date)
            ->max('tanggal');

        if (! $checkpointDate) {
            return 0.0;
        }

        $checkpoint = (float) CashDailyEntry::query()
            ->whereNull('user_id')
            ->whereRaw('LOWER(keterangan) = ?', ['saldo awal'])
            ->whereDate('tanggal', $checkpointDate)
            ->sum(DB::raw('debet - kredit'));

        $manualMovement = (float) CashDailyEntry::query()
            ->whereDate('tanggal', '>=', $checkpointDate)
            ->whereDate('tanggal', '<', $date)
            ->where(fn ($query) => $query->whereNotNull('user_id')
                ->orWhereRaw('LOWER(keterangan) <> ?', ['saldo awal']))
            ->sum(DB::raw('debet - kredit'));

        $paymentMovement = (float) OrderPayment::query()
            ->where('created_at', '>=', $checkpointDate.' 00:00:00')
            ->where('created_at', '<', $date.' 00:00:00')
            ->get(['jumlah', 'cara_bayar'])
            ->sum(function (OrderPayment $payment) {
                $amount = (float) $payment->jumlah;

                if ($amount < 0) {
                    return $amount;
                }

                return $payment->cara_bayar === 'tunai' ? $amount : 0.0;
            });

        return $checkpoint + $manualMovement + $paymentMovement;
    }

    /**
     * Rekap gabungan aktivitas kasir menurut jenis penerimaan. Filter kasir
     * tetap tersedia untuk audit satu operator tanpa mengubah susunan laporan.
     */
    public function rekapKasir(Request $request): View
    {
        $filters = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'kasir' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $dari = $filters['dari'] ?? now()->format('Y-m-d');
        $sampai = $filters['sampai'] ?? now()->format('Y-m-d');
        $kasirId = isset($filters['kasir']) ? (int) $filters['kasir'] : null;

        if ($dari > $sampai) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        $activityUserIds = OrderPayment::query()->whereNotNull('user_id')->pluck('user_id')
            ->merge(CashDailyEntry::query()->whereNotNull('user_id')->pluck('user_id'))
            ->merge(FinalSalesDiscount::query()->whereNotNull('user_id')->pluck('user_id'))
            ->unique();

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
            ->when($kasirId, fn ($query) => $query->where('user_id', $kasirId))
            ->orderBy('created_at')
            ->get();

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
                    'sort' => '1-'.str_pad((string) $entry->urutan, 10, '0', STR_PAD_LEFT),
                ];
            });

        if (! $details->contains(fn (array $row) => $row['category'] === 'opening')) {
            $openingBalance = $this->openingCashBalance($dari);
            $details->prepend([
                'category' => 'opening',
                'description' => 'Saldo Awal',
                'debit' => max(0, $openingBalance),
                'credit' => max(0, -$openingBalance),
                'user_id' => null,
                'sort' => '0-opening',
            ]);
        }

        foreach ($payments as $payment) {
            $order = $ordersByKey["{$payment->order_type}-{$payment->order_id}"] ?? null;
            $customer = $order?->customer?->NmCust ? mb_strtoupper($order->customer->NmCust) : '-';
            $amount = (float) $payment->jumlah;
            $method = OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? ucfirst($payment->cara_bayar);
            $baseSort = '2-'.$payment->created_at?->format('YmdHis').'-'.str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT);

            if ($amount < 0) {
                $details->push([
                    'category' => 'refund',
                    'description' => "Refund via {$method} - {$customer}",
                    'debit' => 0.0,
                    'credit' => abs($amount),
                    'user_id' => $payment->user_id,
                    'sort' => $baseSort.'-0',
                ]);

                continue;
            }

            $category = match ($payment->jenis) {
                'dp' => 'advance',
                'pelunasan_hutang' => 'receivable',
                default => 'cash_note',
            };
            $description = match ($category) {
                'advance' => ($order?->NoOrder ?? 'DP').' ('.$customer.')',
                'receivable' => 'Piutang '.$customer,
                default => 'Nota '.($order?->NoOrder ?? '-').' ('.$customer.')',
            };

            $details->push([
                'category' => $category,
                'description' => $description,
                'debit' => $amount,
                'credit' => 0.0,
                'user_id' => $payment->user_id,
                'sort' => $baseSort.'-0',
            ]);

            if (in_array($payment->cara_bayar, ['transfer', 'qris'], true)) {
                $details->push([
                    'category' => 'non_cash',
                    'description' => "Bayar via {$method} - {$customer}",
                    'debit' => 0.0,
                    'credit' => $amount,
                    'user_id' => $payment->user_id,
                    'sort' => $baseSort.'-1',
                ]);
            }
        }

        FinalSalesDiscount::with('customer')
            ->whereBetween('transaction_date', [$dari, $sampai])
            ->when($kasirId, fn ($query) => $query->where('user_id', $kasirId))
            ->orderBy('transaction_date')->orderBy('id')->get()
            ->each(function (FinalSalesDiscount $discount) use ($details) {
                $details->push([
                    'category' => 'other_transaction',
                    'description' => $discount->order_number.' · '.($discount->customer?->NmCust ?? 'Customer tidak tersedia')
                        .' · Potongan Penjualan Rp '.number_format($discount->discount_amount, 0, ',', '.').' - '.$discount->reason,
                    'debit' => 0.0,
                    'credit' => 0.0,
                    'user_id' => $discount->user_id,
                    'sort' => '3-'.$discount->transaction_date->format('Ymd').'-'.str_pad((string) $discount->id, 10, '0', STR_PAD_LEFT),
                ]);
            });

        $details = $details->sortBy('sort')->values();
        $sectionDefinitions = [
            'opening' => 'Saldo Awal',
            'cash_note' => 'Penerimaan Nota Tunai (Cash)',
            'receivable' => 'Penerimaan Piutang',
            'advance' => 'Uang Muka (DP)',
            'non_cash' => 'Penerimaan Non Tunai (Transfer atau QRIS)',
            'refund' => 'Refund / Pengeluaran Kas',
            'other_transaction' => 'Transaksi Lain-lain',
        ];

        $sections = collect($sectionDefinitions)->map(function (string $label, string $key) use ($details) {
            $sectionRows = $details->where('category', $key);

            // Laporan lama merangkum piutang per customer. Baris transaksi
            // lain tetap dipertahankan agar nomor nota dan kanal bayar jelas.
            if ($key === 'receivable') {
                $sectionRows = $sectionRows->groupBy('description')->map(fn (Collection $rows) => [
                    'description' => $rows->first()['description'],
                    'debit' => (float) $rows->sum('debit'),
                    'credit' => (float) $rows->sum('credit'),
                ])->sortBy('description')->values();
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

        $activityUserIds = OrderPayment::query()->whereNotNull('user_id')->pluck('user_id')
            ->merge(CashDailyEntry::query()->whereNotNull('user_id')->pluck('user_id'))
            ->merge(FinalSalesDiscount::query()->whereNotNull('user_id')->pluck('user_id'))
            ->unique();
        $kasirUsers = User::query()
            ->where(function ($query) use ($activityUserIds) {
                $query->whereHas('role', fn ($role) => $role->where('name', 'kasir'))
                    ->orWhereIn('id', $activityUserIds);
            })
            ->orderBy('name')->get(['id', 'name']);
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

        if (! $manualRows->contains(fn (array $row) => $row['user_id'] === null && strcasecmp($row['keterangan'], 'Saldo Awal') === 0)) {
            $opening = $this->openingCashBalance($tanggal);
            $occurredAt = Carbon::parse($tanggal)->startOfDay();
            $manualRows->prepend([
                'occurred_at' => $occurredAt,
                'no_nota' => null,
                'keterangan' => 'Saldo Awal',
                'debet' => max(0, $opening),
                'kredit' => max(0, -$opening),
                'kasir' => null,
                'user_id' => null,
                'sort' => $occurredAt->format('YmdHis').'-0-0000000000',
            ]);
        }

        $payments = OrderPayment::with('user')
            ->whereDate('created_at', $tanggal)
            ->when($kasirId, fn ($query) => $query->where('user_id', $kasirId))
            ->orderBy('created_at')->orderBy('id')->get();
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

        $paymentRows = $payments->flatMap(function (OrderPayment $payment) use ($ordersByKey) {
            $order = $ordersByKey["{$payment->order_type}-{$payment->order_id}"] ?? null;
            $customer = $order?->customer?->NmCust ? mb_strtoupper($order->customer->NmCust) : '-';
            $amount = (float) $payment->jumlah;
            $method = OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? ucfirst($payment->cara_bayar);
            $kind = OrderPayment::JENIS_LABELS[$payment->jenis] ?? ucfirst($payment->jenis);
            $baseSort = $payment->created_at->format('YmdHis').'-1-'.str_pad((string) $payment->id, 10, '0', STR_PAD_LEFT);

            if ($amount < 0) {
                return [[
                    'occurred_at' => $payment->created_at,
                    'no_nota' => $order?->NoOrder,
                    'keterangan' => "Refund via {$method} - {$customer}",
                    'debet' => 0.0,
                    'kredit' => abs($amount),
                    'kasir' => $payment->user?->name,
                    'user_id' => $payment->user_id,
                    'sort' => $baseSort.'-0',
                ]];
            }

            $rows = [[
                'occurred_at' => $payment->created_at,
                'no_nota' => $order?->NoOrder,
                'keterangan' => "{$kind} - {$customer}",
                'debet' => $amount,
                'kredit' => 0.0,
                'kasir' => $payment->user?->name,
                'user_id' => $payment->user_id,
                'sort' => $baseSort.'-0',
            ]];

            if (in_array($payment->cara_bayar, ['transfer', 'qris'], true)) {
                $rows[] = [
                    'occurred_at' => $payment->created_at,
                    'no_nota' => null,
                    'keterangan' => "Bayar via {$method} - {$customer}",
                    'debet' => 0.0,
                    'kredit' => $amount,
                    'kasir' => $payment->user?->name,
                    'user_id' => $payment->user_id,
                    'sort' => $baseSort.'-1',
                ];
            }

            return $rows;
        });

        $discountRows = FinalSalesDiscount::with(['user', 'customer'])
            ->whereDate('transaction_date', $tanggal)
            ->when($kasirId, fn ($query) => $query->where('user_id', $kasirId))
            ->orderBy('id')->get()
            ->map(function (FinalSalesDiscount $discount) {
                $occurredAt = $discount->created_at
                    ? Carbon::parse($discount->transaction_date->format('Y-m-d').' '.$discount->created_at->format('H:i:s'))
                    : $discount->transaction_date->copy()->setTime(12, 0);

                return [
                    'occurred_at' => $occurredAt,
                    'no_nota' => $discount->order_number,
                    'keterangan' => 'Potongan Penjualan Rp '.number_format($discount->discount_amount, 0, ',', '.')
                        .' - '.($discount->customer?->NmCust ?? 'Customer tidak tersedia').' - '.$discount->reason,
                    'debet' => 0.0,
                    'kredit' => 0.0,
                    'kasir' => $discount->user?->name,
                    'user_id' => $discount->user_id,
                    'sort' => $occurredAt->format('YmdHis').'-2-'.str_pad((string) $discount->id, 10, '0', STR_PAD_LEFT),
                ];
            });

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
            $entry[$payment->cara_bayar] += (float) $payment->jumlah;
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
            $model::query()->with('customer')
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

    public function customerReceivableDetails(Request $request): View
    {
        $from = $request->filled('dari') ? $request->string('dari')->toString() : now()->startOfMonth()->format('Y-m-d');
        $to = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $customerCode = $request->string('customer')->trim()->toString();
        $customers = Customer::query()->orderBy('NmCust')->get(['KdCust', 'NmCust']);
        $selectedCustomer = $customerCode !== '' ? $customers->firstWhere('KdCust', $customerCode) : null;
        $rows = collect();

        if ($selectedCustomer) {
            foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
                $settledIds = OrderPayment::query()->where('order_type', $type)->where('jenis', 'pelunasan_hutang')->pluck('order_id');
                $orders = $model::query()->where('KdCust', $customerCode)
                    ->whereBetween('TglOrder', [$from, $to])->where('status', '!=', 'batal')
                    ->where(function ($query) use ($settledIds) {
                        $query->where('status_bayar', 'hutang');
                        if ($settledIds->isNotEmpty()) {
                            $query->orWhereIn('id', $settledIds);
                        }
                    })->orderBy('TglOrder')->orderBy('NoOrder')->get();
                $payments = OrderPayment::query()->where('order_type', $type)->where('jenis', 'pelunasan_hutang')
                    ->whereIn('order_id', $orders->pluck('id'))->where('created_at', '<=', "{$to} 23:59:59")
                    ->get()->groupBy('order_id');
                $finalDiscountsByOrder = FinalSalesDiscount::query()
                    ->where('order_type', $type)->whereIn('order_id', $orders->pluck('id'))
                    ->whereDate('transaction_date', '<=', $to)
                    ->selectRaw('order_id, SUM(discount_amount) AS total_discount')
                    ->groupBy('order_id')->pluck('total_discount', 'order_id');
                $invoiceNumbers = DB::table('order_documents')->where('kind', 'inv')->where('order_type', $type)
                    ->whereIn('order_id', $orders->pluck('id'))->orderByDesc('sequence')->get(['order_id', 'number'])
                    ->unique('order_id')->pluck('number', 'order_id');

                foreach ($orders as $order) {
                    $gross = (float) $order->total;
                    $initialDiscount = $order->diskon_approved_at && $order->diskon_approved_at->format('Y-m-d') <= $to
                        ? $order->diskonAwalNominal() : 0.0;
                    $discount = $initialDiscount + (float) ($finalDiscountsByOrder[$order->id] ?? 0);
                    $net = max(0, $gross - $discount);
                    $paid = min($net, max(0, (float) ($payments[$order->id] ?? collect())->sum('jumlah')));
                    $remaining = max(0, $net - $paid);
                    if ($remaining <= 0) {
                        continue;
                    }
                    $rows->push((object) [
                        'date' => $order->TglOrder,
                        'invoice' => $invoiceNumbers[$order->id] ?? $order->NoOrder,
                        'receivable' => $gross, 'discount' => $discount,
                        'paid' => $paid, 'remaining' => $remaining,
                    ]);
                }
            }
        }

        $rows = $rows->sortBy(fn ($row) => $row->date.'|'.$row->invoice)->values();
        $totals = (object) collect(['receivable', 'discount', 'paid', 'remaining'])
            ->mapWithKeys(fn ($column) => [$column => (float) $rows->sum($column)])->all();

        return view('keuangan.customer-receivable-details', compact(
            'from', 'to', 'customers', 'selectedCustomer', 'customerCode', 'rows', 'totals'
        ));
    }

    public function dailyReceivableCollections(Request $request): View
    {
        $date = $request->filled('tanggal') ? $request->string('tanggal')->toString() : now()->format('Y-m-d');
        $rows = collect();

        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $type => $model) {
            $settledIds = OrderPayment::query()->where('order_type', $type)->where('jenis', 'pelunasan_hutang')->pluck('order_id');
            $orders = $model::query()->with('customer')->whereDate('TglOrder', '<=', $date)
                ->where('status', '!=', 'batal')->where(function ($query) use ($settledIds) {
                    $query->where('status_bayar', 'hutang');
                    if ($settledIds->isNotEmpty()) {
                        $query->orWhereIn('id', $settledIds);
                    }
                })->get();
            $allPayments = OrderPayment::query()->where('order_type', $type)->where('jenis', 'pelunasan_hutang')
                ->whereIn('order_id', $orders->pluck('id'))->where('created_at', '<=', "{$date} 23:59:59")
                ->orderBy('created_at')->get()->groupBy('order_id');

            foreach ($orders as $order) {
                $payments = $allPayments[$order->id] ?? collect();
                $todayPayments = $payments->filter(fn (OrderPayment $payment) => $payment->created_at?->format('Y-m-d') === $date);
                $gross = (float) $order->total;
                $discount = $order->diskon_approved_at && $order->diskon_approved_at->format('Y-m-d') <= $date
                    ? $order->diskonNominal() : 0.0;
                $net = max(0, $gross - $discount);
                $paidToDate = min($net, max(0, (float) $payments->sum('jumlah')));
                $paidToday = max(0, (float) $todayPayments->sum('jumlah'));
                $remaining = max(0, $net - $paidToDate);
                if ($remaining <= 0 && $paidToday <= 0) {
                    continue;
                }
                $notes = $todayPayments->map(function (OrderPayment $payment) {
                    $method = OrderPayment::CARA_BAYAR_LABELS[$payment->cara_bayar] ?? ucfirst($payment->cara_bayar);

                    return $method.($payment->no_referensi ? ' '.$payment->no_referensi : '');
                })->unique()->implode(', ');

                $rows->push((object) [
                    'customer_code' => $order->KdCust ?: '-',
                    'customer' => $order->customer?->NmCust ?: ($order->KdCust ?: 'Tanpa Customer'),
                    'date' => $order->TglOrder, 'order' => $order->NoOrder,
                    'receivable' => $gross, 'paid' => $paidToday,
                    'discount' => $discount, 'remaining' => $remaining,
                    'notes' => $notes ?: '-',
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
