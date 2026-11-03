<?php

namespace App\Services;

use App\Models\OrderLateLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pencatat order telat. Telat = lewat 24 jam sejak pertama diproses kasir (dibayar/hutang)
 * dan belum Siap Diambil / Selesai / Batal. Order belum bayar tidak dihitung.
 */
class OrderLateLogService
{
    /** Status yang berarti order sudah tidak (bisa) telat lagi. */
    public const DONE_STATUSES = ['siap_diambil', 'selesai', 'batal'];

    /** Status sebelum kasir: belum dibayar, jam telat belum berjalan. */
    public const UNPAID_STATUSES = ['baru'];

    /** Urutan tahap untuk menebak posisi order saat batas 24 jam (backfill). */
    private const STAGE_COLUMNS = ['desain' => 'desain_at', 'cetak' => 'cetak_at', 'finishing' => 'finishing_at', 'qc' => 'qc_at', 'bungkus' => 'bungkus_at'];

    private const CHUNK = 500;

    public function isAvailable(): bool
    {
        return Schema::hasTable('order_late_logs');
    }

    /**
     * Catat order yang sedang telat dan tutup log order yang sudah siap.
     * Hitungan dimulai sejak order pertama kali diproses kasir (dibayar/hutang),
     * bukan sejak dibuat: order yang belum dibayar tidak pernah dihitung telat.
     *
     * @return array{opened: int, resolved: int}
     */
    public function record(?Carbon $now = null): array
    {
        $now ??= now();
        $opened = 0;

        foreach (OrderDocumentService::MODELS as $type => $model) {
            DB::table((new $model)->getTable())
                ->whereNotNull('created_at')
                ->whereNotIn('status', [...self::DONE_STATUSES, ...self::UNPAID_STATUSES])
                ->where('status_bayar', '!=', 'belum_bayar')
                ->select(['id', 'created_at', 'status', 'dibayar_at', 'diproses_kasir_at'])
                ->chunkById(self::CHUNK, function (Collection $orders) use ($type, $now, &$opened) {
                    $deadlines = $this->deadlines($type, $orders);
                    $rows = [];
                    foreach ($orders as $order) {
                        $lateAt = $deadlines[$order->id];
                        if ($lateAt && $lateAt->lt($now)) {
                            $rows[] = $this->row($type, $order->id, Carbon::parse($order->created_at), $lateAt, $order->status, false, $now);
                        }
                    }
                    $opened += $rows ? DB::table('order_late_logs')->insertOrIgnore($rows) : 0;
                });
        }

        return ['opened' => $opened, 'resolved' => $this->resolve($now)];
    }

    /**
     * Isi log perkiraan untuk order lama yang sudah siap: telat bila Bungkus selesai
     * lebih dari 24 jam setelah pertama diproses kasir. Order tanpa waktu Bungkus dilewati.
     *
     * @param  callable(string, int): void|null  $progress
     */
    public function backfill(?callable $progress = null): int
    {
        $now = now();
        $inserted = 0;

        foreach (OrderDocumentService::MODELS as $type => $model) {
            DB::table((new $model)->getTable())
                ->whereIn('status', ['siap_diambil', 'selesai'])
                ->whereNotNull('created_at')
                ->whereNotNull('bungkus_at')
                ->select(['id', 'created_at', 'status', 'dibayar_at', 'diproses_kasir_at', ...array_values(self::STAGE_COLUMNS)])
                ->chunkById(self::CHUNK, function (Collection $orders) use ($type, $now, $progress, &$inserted) {
                    $deadlines = $this->deadlines($type, $orders);
                    $rows = [];
                    foreach ($orders as $order) {
                        $lateAt = $deadlines[$order->id];
                        $readyAt = Carbon::parse($order->bungkus_at);
                        if (! $lateAt || $readyAt->lte($lateAt)) {
                            continue;
                        }
                        $row = $this->row($type, $order->id, Carbon::parse($order->created_at), $lateAt, $this->stageAt($order, $lateAt), true, $now);
                        $rows[] = ['resolved_at' => $readyAt, 'resolved_status' => $order->status] + $row;
                    }
                    $count = $rows ? DB::table('order_late_logs')->insertOrIgnore($rows) : 0;
                    $inserted += $count;
                    if ($progress) {
                        $progress($type, $count);
                    }
                });
        }

        return $inserted;
    }

    /** Hapus semua log (dipakai sebelum backfill ulang setelah aturan berubah). */
    public function reset(): int
    {
        return DB::table('order_late_logs')->delete();
    }

    /**
     * Batas estimasi per order, sama dengan HasOperatorEstimate: sejak pertama
     * diproses kasir + Progress Hari Lembar Kerja CS (default 1 hari).
     * Awal hitungan: diproses_kasir_at, lalu catatan status kasir paling awal,
     * lalu dibayar_at (untuk data sebelum kolom itu ada).
     *
     * @param  Collection<int, object>  $orders
     * @return array<int, Carbon|null>
     */
    private function deadlines(string $type, Collection $orders): array
    {
        $ids = $orders->pluck('id');
        $notes = Schema::hasTable('order_status_notes')
            ? DB::table('order_status_notes')->where('order_type', $type)->where('stage', 'kasir')
                ->whereIn('order_id', $ids)
                ->groupBy('order_id')->selectRaw('order_id, MIN(created_at) as first_at')
                ->pluck('first_at', 'order_id')
            : collect();
        $progressDays = Schema::hasTable('customer_service_job_sheets')
            ? DB::table('customer_service_job_sheets')->where('order_type', $type)->whereIn('order_id', $ids)
                ->where('progress_hari', '>', 0)
                ->groupBy('order_id')->selectRaw('order_id, MAX(progress_hari) as days')
                ->pluck('days', 'order_id')
            : collect();

        return $orders->mapWithKeys(function ($order) use ($notes, $progressDays) {
            $start = ($order->diproses_kasir_at ?? null) ?? $notes->get($order->id) ?? $order->dibayar_at;

            return [$order->id => $start
                ? Carbon::parse($start)->addDays(max(1, (int) $progressDays->get($order->id, 1)))
                : null];
        })->all();
    }

    /**
     * Log telat untuk sekumpulan order, dikunci "tipe-id".
     *
     * @param  Collection<int, array{type: string, id: int}>  $orders
     * @return Collection<string, OrderLateLog>
     */
    public function logsFor(Collection $orders): Collection
    {
        if ($orders->isEmpty() || ! $this->isAvailable()) {
            return collect();
        }

        return OrderLateLog::query()
            ->where(function ($query) use ($orders) {
                foreach ($orders->groupBy('type') as $type => $rows) {
                    $query->orWhere(fn ($q) => $q->where('order_type', $type)->whereIn('order_id', $rows->pluck('id')));
                }
            })
            ->get()
            ->keyBy(fn (OrderLateLog $log) => $log->order_type.'-'.$log->order_id);
    }

    /** Tutup log yang ordernya sudah Siap Diambil / Selesai / Batal. */
    private function resolve(Carbon $now): int
    {
        $resolved = 0;

        OrderLateLog::query()->whereNull('resolved_at')->chunkById(self::CHUNK, function (Collection $logs) use ($now, &$resolved) {
            foreach ($logs->groupBy('order_type') as $type => $typeLogs) {
                $model = OrderDocumentService::MODELS[$type] ?? null;
                if (! $model) {
                    continue;
                }
                $orders = DB::table((new $model)->getTable())
                    ->whereIn('id', $typeLogs->pluck('order_id'))
                    ->get(['id', 'status', 'status_bayar', 'bungkus_at'])
                    ->keyBy('id');

                foreach ($typeLogs as $log) {
                    $order = $orders->get($log->order_id);
                    // Tercatat dengan aturan lama padahal belum dibayar: bukan telat.
                    if ($order && (in_array($order->status, self::UNPAID_STATUSES, true) || $order->status_bayar === 'belum_bayar')) {
                        $log->delete();

                        continue;
                    }
                    if ($order && ! in_array($order->status, self::DONE_STATUSES, true)) {
                        continue;
                    }
                    // Waktu Bungkus lebih tepat daripada jam scheduler, asal masuk akal.
                    $bungkusAt = $order?->bungkus_at ? Carbon::parse($order->bungkus_at) : null;
                    $resolvedAt = $bungkusAt && $bungkusAt->gte($log->late_at) && $bungkusAt->lte($now) ? $bungkusAt : $now;
                    $log->update(['resolved_at' => $resolvedAt, 'resolved_status' => $order->status ?? 'dihapus']);
                    $resolved++;
                }
            }
        });

        return $resolved;
    }

    /** Tahap yang belum selesai saat batas 24 jam lewat. */
    private function stageAt(object $order, Carbon $lateAt): ?string
    {
        if ($order->dibayar_at && Carbon::parse($order->dibayar_at)->gt($lateAt)) {
            return 'baru';
        }
        $columns = array_values(self::STAGE_COLUMNS);
        foreach (array_keys(self::STAGE_COLUMNS) as $index => $stage) {
            $value = $order->{$columns[$index]};
            // Tahap kosong tapi tahap sesudahnya terisi = dilewati (mis. stand banner tanpa Layout & Cetak).
            $skipped = ! $value && collect(array_slice($columns, $index + 1))->contains(fn ($later) => (bool) $order->{$later});
            if ($skipped) {
                continue;
            }
            if (! $value || Carbon::parse($value)->gt($lateAt)) {
                return $stage;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $type, int $orderId, Carbon $createdAt, Carbon $lateAt, ?string $stage, bool $estimated, Carbon $now): array
    {
        return [
            'order_type' => $type,
            'order_id' => $orderId,
            'order_created_at' => $createdAt,
            'late_at' => $lateAt,
            'stage' => $stage,
            'resolved_at' => null,
            'resolved_status' => null,
            'is_estimated' => $estimated,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
