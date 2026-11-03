<?php

namespace App\Services;

use App\Models\OrderLateLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pencatat order telat. Aturan sama dengan kartu telat dashboard: lewat 24 jam
 * sejak dibuat dan belum Siap Diambil / Selesai / Batal.
 */
class OrderLateLogService
{
    /** Status yang berarti order sudah tidak (bisa) telat lagi. */
    public const DONE_STATUSES = ['siap_diambil', 'selesai', 'batal'];

    /** Urutan tahap untuk menebak posisi order saat batas 24 jam (backfill). */
    private const STAGE_COLUMNS = ['desain' => 'desain_at', 'cetak' => 'cetak_at', 'finishing' => 'finishing_at', 'qc' => 'qc_at', 'bungkus' => 'bungkus_at'];

    private const CHUNK = 500;

    public function isAvailable(): bool
    {
        return Schema::hasTable('order_late_logs');
    }

    /**
     * Catat order yang sedang telat dan tutup log order yang sudah siap.
     *
     * @return array{opened: int, resolved: int}
     */
    public function record(?Carbon $now = null): array
    {
        $now ??= now();
        $cutoff = $now->copy()->subHours(OrderLateLog::LATE_AFTER_HOURS);
        $opened = 0;

        foreach (OrderDocumentService::MODELS as $type => $model) {
            DB::table((new $model)->getTable())
                ->whereNotNull('created_at')
                ->where('created_at', '<', $cutoff)
                ->whereNotIn('status', self::DONE_STATUSES)
                ->select(['id', 'created_at', 'status'])
                ->chunkById(self::CHUNK, function (Collection $orders) use ($type, $now, &$opened) {
                    $rows = $orders->map(fn ($order) => $this->row($type, $order->id, Carbon::parse($order->created_at), $order->status, false, $now))->all();
                    $opened += DB::table('order_late_logs')->insertOrIgnore($rows);
                });
        }

        return ['opened' => $opened, 'resolved' => $this->resolve($now)];
    }

    /**
     * Isi log perkiraan untuk order lama yang sudah siap: telat bila Bungkus selesai
     * lebih dari 24 jam setelah order dibuat. Order tanpa waktu Bungkus dilewati.
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
                ->select(['id', 'created_at', 'status', 'dibayar_at', ...array_values(self::STAGE_COLUMNS)])
                ->chunkById(self::CHUNK, function (Collection $orders) use ($type, $now, $progress, &$inserted) {
                    $rows = [];
                    foreach ($orders as $order) {
                        $createdAt = Carbon::parse($order->created_at);
                        $lateAt = $createdAt->copy()->addHours(OrderLateLog::LATE_AFTER_HOURS);
                        $readyAt = Carbon::parse($order->bungkus_at);
                        if ($readyAt->lte($lateAt)) {
                            continue;
                        }
                        $row = $this->row($type, $order->id, $createdAt, $this->stageAt($order, $lateAt), true, $now);
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
                    ->get(['id', 'status', 'bungkus_at'])
                    ->keyBy('id');

                foreach ($typeLogs as $log) {
                    $order = $orders->get($log->order_id);
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
    private function row(string $type, int $orderId, Carbon $createdAt, ?string $stage, bool $estimated, Carbon $now): array
    {
        return [
            'order_type' => $type,
            'order_id' => $orderId,
            'order_created_at' => $createdAt,
            'late_at' => $createdAt->copy()->addHours(OrderLateLog::LATE_AFTER_HOURS),
            'stage' => $stage,
            'resolved_at' => null,
            'resolved_status' => null,
            'is_estimated' => $estimated,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
