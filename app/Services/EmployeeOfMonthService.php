<?php

namespace App\Services;

use App\Http\Controllers\MonitoringKinerjaController;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Employee of the Month (versi santai, 100% data sistem, tanpa presensi).
 * Satu pemenang per divisi; skor dibandingkan dengan rekan satu divisi:
 * - Produktivitas (qty/order/job sheet), relatif terhadap yang tertinggi = 100.
 * - Ketepatan waktu: % order yang tidak telat di tahap itu (History Telat).
 * - Kualitas: % order yang tidak perlu diulang dari tahap itu (ulang proses disetujui).
 * Bobot 50/30/20 bila ketiganya tersedia; divisi tanpa data waktu/kualitas memakai produktivitas saja.
 */
class EmployeeOfMonthService
{
    public const MIN_ACTIVE_DAYS = 10;

    /** Divisi produksi: kunci = nilai order_status_notes.stage. */
    public const PRODUCTION_DIVISIONS = [
        'desain' => 'Layout',
        'cetak' => 'Cetak',
        'finishing' => 'Finishing',
        'qc' => 'Back Office',
        'bungkus' => 'Bungkus',
        'siap_diambil' => 'Pengambilan Barang',
    ];

    /** Tahap yang punya kolom <tahap>_by di tabel order (untuk ketepatan waktu & kualitas). */
    private const STAGES_WITH_OPERATOR = ['desain', 'cetak', 'finishing', 'qc', 'bungkus'];

    private const ORDER_TABLES = ['indoor' => 'order_indoor', 'outdoor' => 'order_outdoor', 'artwork' => 'order_artwork'];

    /** Catatan otomatis CTcP: operator Cetak ikut tercatat di tahap berikutnya, bukan prestasinya. */
    private const AUTO_CTCP_NOTE = 'Otomatis selesai bersama proses Cetak CTcP.';

    /**
     * @return array{month: Carbon, provisional: bool, min_days: int, divisions: list<array<string, mixed>>}
     */
    public function compute(Carbon $month): array
    {
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $provisional = now()->lt($to);
        // Bulan berjalan: batas hari aktif disesuaikan dengan hari yang sudah lewat.
        $minDays = $provisional
            ? max(1, min(self::MIN_ACTIVE_DAYS, intdiv(max(1, (int) $from->diffInDays(now()) + 1), 2)))
            : self::MIN_ACTIVE_DAYS;

        $activeDays = $this->activeDays($from, $to);
        $divisions = [];

        foreach (self::PRODUCTION_DIVISIONS as $stage => $label) {
            $rows = $this->productionRows($stage, $from, $to);
            $hasTimeAndQuality = in_array($stage, self::STAGES_WITH_OPERATOR, true);
            if ($hasTimeAndQuality) {
                $late = $this->lateByOperator($stage, $from, $to);
                $rework = $this->reworkByOperator($stage, $from, $to);
                $rows = $rows->map(fn (array $row) => $row + [
                    'late' => $late[$row['user_id']] ?? 0,
                    'rework' => $rework[$row['user_id']] ?? 0,
                ]);
            }
            $divisions[] = $this->rank($label, 'qty', $rows, $activeDays, $minDays, $hasTimeAndQuality);
        }

        $divisions[] = $this->rank('Kasir', 'order dibayar', $this->cashierRows($from, $to), $activeDays, $minDays, false);
        $divisions[] = $this->rank('Customer Service', 'job sheet', $this->csRows($from, $to), $activeDays, $minDays, false);

        return ['month' => $from, 'provisional' => $provisional, 'min_days' => $minDays, 'divisions' => $divisions];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<int, int>  $activeDays
     * @return array<string, mixed>
     */
    private function rank(string $label, string $unit, Collection $rows, array $activeDays, int $minDays, bool $hasTimeAndQuality): array
    {
        $maxOutput = max(1, (float) $rows->max('output'));
        $names = $rows->isEmpty() ? collect() : DB::table('users')->whereIn('id', $rows->pluck('user_id'))->pluck('name', 'id');

        $ranked = $rows->map(function (array $row) use ($maxOutput, $activeDays, $minDays, $hasTimeAndQuality, $names) {
            $productivity = round($row['output'] / $maxOutput * 100, 1);
            $handled = max(1, (int) ($row['orders'] ?? 0));
            $onTime = $hasTimeAndQuality ? round(max(0, 1 - ($row['late'] / $handled)) * 100, 1) : null;
            $quality = $hasTimeAndQuality ? round(max(0, 1 - ($row['rework'] / $handled)) * 100, 1) : null;
            $score = $hasTimeAndQuality ? round($productivity * 0.5 + $onTime * 0.3 + $quality * 0.2, 1) : $productivity;
            $days = $activeDays[$row['user_id']] ?? 0;

            return $row + [
                'name' => $names[$row['user_id']] ?? 'User #'.$row['user_id'],
                'active_days' => $days,
                'eligible' => $days >= $minDays,
                'productivity' => $productivity,
                'on_time' => $onTime,
                'quality' => $quality,
                'score' => $score,
            ];
        })->sort(fn (array $a, array $b) => [$b['eligible'], $b['score'], $b['output']] <=> [$a['eligible'], $a['score'], $a['output']])->values();

        return [
            'label' => $label,
            'unit' => $unit,
            'weighted' => $hasTimeAndQuality,
            'winner' => $ranked->first(fn (array $row) => $row['eligible']),
            'rows' => $ranked,
        ];
    }

    /**
     * Qty yang diselesaikan per operator di satu tahap, tanpa catatan otomatis CTcP,
     * item tanpa proses, dan produk non-prestasi (sama dengan Rekap Operator).
     *
     * @return Collection<int, array{user_id: int, output: float, orders: int}>
     */
    private function productionRows(string $stage, Carbon $from, Carbon $to): Collection
    {
        $excluded = MonitoringKinerjaController::EXCLUDED_RECAP_PRODUCTS;

        return DB::table('order_status_notes as n')
            ->leftJoin('order_indoor_detail as di', fn ($join) => $join->on('di.id', '=', 'n.order_detail_id')->where('n.order_type', 'indoor'))
            ->leftJoin('order_artwork_detail as da', fn ($join) => $join->on('da.id', '=', 'n.order_detail_id')->where('n.order_type', 'artwork'))
            ->where('n.stage', $stage)
            ->whereIn('n.action', ['progress', 'selesai'])
            ->whereNotNull('n.user_id')
            ->whereBetween('n.created_at', [$from, $to])
            ->where(fn ($q) => $q->whereNull('n.catatan')->orWhere('n.catatan', '!=', self::AUTO_CTCP_NOTE))
            ->where(fn ($q) => $q->whereNull('di.id')->orWhere(fn ($q) => $q->where('di.tanpa_proses', false)->whereNotIn('di.KdProd', $excluded)))
            ->where(fn ($q) => $q->whereNull('da.id')->orWhere(fn ($q) => $q->where('da.tanpa_proses', false)->whereNotIn('da.KdProd', $excluded)))
            ->groupBy('n.user_id', 'n.order_type', 'n.order_id')
            ->selectRaw('n.user_id, COALESCE(SUM(n.qty), 0) as output')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $orders, $userId) => ['user_id' => (int) $userId, 'output' => (float) $orders->sum('output'), 'orders' => $orders->count()])
            ->values();
    }

    /**
     * Order yang telat di tahap ini bulan ini, dikaitkan ke operator yang menyelesaikan tahapnya.
     *
     * @return array<int, int>
     */
    private function lateByOperator(string $stage, Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('order_late_logs')) {
            return [];
        }

        return $this->countByStageOperator(
            DB::table('order_late_logs')->where('stage', $stage)->whereBetween('late_at', [$from, $to]),
            $stage
        );
    }

    /**
     * Ulang proses yang disetujui dan harus dikerjakan ulang mulai tahap ini,
     * dikaitkan ke operator yang dulu menyelesaikan tahap tersebut.
     *
     * @return array<int, int>
     */
    private function reworkByOperator(string $stage, Carbon $from, Carbon $to): array
    {
        return $this->countByStageOperator(
            DB::table('order_rework_requests')->where('action', 'ulang')->where('status', 'approved')
                ->where('target_stage', $stage)->whereBetween('requested_at', [$from, $to]),
            $stage
        );
    }

    /**
     * @return array<int, int> user_id => jumlah order
     */
    private function countByStageOperator(Builder $events, string $stage): array
    {
        $counts = [];
        foreach ($events->get(['order_type', 'order_id'])->groupBy('order_type') as $type => $rows) {
            $table = self::ORDER_TABLES[$type] ?? null;
            if (! $table || ! Schema::hasColumn($table, "{$stage}_by")) {
                continue;
            }
            DB::table($table)->whereIn('id', $rows->pluck('order_id')->unique())->whereNotNull("{$stage}_by")
                ->pluck("{$stage}_by")
                ->each(function ($userId) use (&$counts) {
                    $counts[(int) $userId] = ($counts[(int) $userId] ?? 0) + 1;
                });
        }

        return $counts;
    }

    /** @return Collection<int, array{user_id: int, output: float, orders: int}> */
    private function cashierRows(Carbon $from, Carbon $to): Collection
    {
        return DB::table('order_payments')
            ->where('jenis', '!=', 'refund')->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->distinct()->get(['user_id', 'order_type', 'order_id'])
            ->groupBy('user_id')
            ->map(fn (Collection $orders, $userId) => ['user_id' => (int) $userId, 'output' => (float) $orders->count(), 'orders' => $orders->count()])
            ->values();
    }

    /** @return Collection<int, array{user_id: int, output: float, orders: int}> */
    private function csRows(Carbon $from, Carbon $to): Collection
    {
        return DB::table('customer_service_job_sheets')
            ->whereNotNull('created_by')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('created_by')
            ->selectRaw('created_by as user_id, COUNT(*) as sheets')
            ->get()
            ->map(fn ($row) => ['user_id' => (int) $row->user_id, 'output' => (float) $row->sheets, 'orders' => (int) $row->sheets]);
    }

    /**
     * Hari aktif per user = tanggal berbeda dengan aktivitas apa pun (proses tahap, pembayaran, job sheet).
     *
     * @return array<int, int>
     */
    private function activeDays(Carbon $from, Carbon $to): array
    {
        $dates = DB::table('order_status_notes')->whereNotNull('user_id')->whereBetween('created_at', [$from, $to])
            ->selectRaw('user_id, DATE(created_at) as day')
            ->union(DB::table('order_payments')->whereNotNull('user_id')->whereBetween('created_at', [$from, $to])->selectRaw('user_id, DATE(created_at) as day'))
            ->union(DB::table('customer_service_job_sheets')->whereNotNull('created_by')->whereBetween('created_at', [$from, $to])->selectRaw('created_by as user_id, DATE(created_at) as day'));

        return DB::query()->fromSub($dates, 'd')->groupBy('user_id')->selectRaw('user_id, COUNT(DISTINCT day) as days')
            ->pluck('days', 'user_id')->map(fn ($days) => (int) $days)->all();
    }
}
