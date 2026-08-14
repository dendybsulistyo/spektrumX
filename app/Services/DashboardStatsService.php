<?php

namespace App\Services;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\Support\Collection;

class DashboardStatsService
{
    /**
     * @return array<string, mixed>
     */
    public function stats(?string $from = null, ?string $to = null): array
    {
        $all = $this->loadOrders($from, $to);

        return [
            'total' => $all->count(),
            'belum_bayar' => $all->where('status_bayar', 'belum_bayar')->count(),
            'lunas' => $all->where('status_bayar', 'lunas')->count(),
            'hutang' => $all->where('status_bayar', 'hutang')->count(),
            'hutang_nominal' => $all->where('status_bayar', 'hutang')->sum('jumlah_piutang'),
            'dp' => $all->where('status_bayar', 'dp')->count(),
            'dp_nominal' => $all->where('status_bayar', 'dp')->sum('jumlah_piutang'),
            'desain' => $all->where('status', 'desain')->count(),
            'cetak' => $all->where('status', 'cetak')->count(),
            'finishing' => $all->where('status', 'finishing')->count(),
            'qc' => $all->where('status', 'qc')->count(),
            'bungkus' => $all->where('status', 'bungkus')->count(),
            'siap_diambil' => $all->where('status', 'siap_diambil')->count(),
            'selesai' => $all->where('status', 'selesai')->count(),
            'telat' => $all->filter(fn ($o) => $this->isOverdue($o))->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recentOrders(int $limit = 30, ?string $from = null, ?string $to = null): Collection
    {
        [$indoorQuery, $outdoorQuery, $artworkQuery] = $this->scopedQueries($from, $to);

        $indoor = $indoorQuery->get();
        $outdoor = $outdoorQuery->get();
        $artwork = $artworkQuery->get();

        $indoor->load('customer', 'createdBy', 'kasir', 'desainBy', 'cetakBy', 'finishingBy', 'qcBy', 'bungkusBy', 'pengambilanBy', 'items');
        $outdoor->load('customer', 'createdBy', 'kasir', 'desainBy', 'cetakBy', 'finishingBy', 'qcBy', 'bungkusBy', 'pengambilanBy', 'items');
        $artwork->load('customer', 'createdBy', 'kasir', 'desainBy', 'cetakBy', 'finishingBy', 'qcBy', 'bungkusBy', 'pengambilanBy', 'items');

        $mapped = $indoor->map(fn ($o) => $this->toRow($o, 'Indoor'))
            ->concat($outdoor->map(fn ($o) => $this->toRow($o, 'Outdoor')))
            ->concat($artwork->map(fn ($o) => $this->toRow($o, 'Artwork')));

        return $mapped->sortByDesc('created_at')->take($limit)->values();
    }

    private function loadOrders(?string $from = null, ?string $to = null): Collection
    {
        [$indoorQuery, $outdoorQuery, $artworkQuery] = $this->scopedQueries($from, $to);

        return $indoorQuery->get()->concat($outdoorQuery->get())->concat($artworkQuery->get());
    }

    /**
     * Query builders scoped to an optional created_at date range, shared by
     * stats() and recentOrders() so both respect the same date filter.
     *
     * @return array{0: \Illuminate\Database\Eloquent\Builder, 1: \Illuminate\Database\Eloquent\Builder, 2: \Illuminate\Database\Eloquent\Builder}
     */
    private function scopedQueries(?string $from, ?string $to): array
    {
        $scope = function ($query) use ($from, $to) {
            if ($from) {
                $query->where('created_at', '>=', $from.' 00:00:00');
            }
            if ($to) {
                $query->where('created_at', '<=', $to.' 23:59:59');
            }

            return $query;
        };

        // Historical rows (pre-dating this pipeline) were backfilled to
        // status/status_bayar = selesai/lunas with no created_at — excluding
        // rows with a null created_at keeps the dashboard scoped to orders
        // actually placed through the new pipeline.
        return [
            $scope(OrderIndoor::query()->whereNotNull('created_at')),
            $scope(OrderOutdoor::query()),
            $scope(OrderArtwork::query()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow($order, string $tipe): array
    {
        $selesaiAt = $order->diambil_at;
        $durasi = $order->created_at
            ? $order->created_at->locale('id')->diffForHumans($selesaiAt ?? now(), true)
            : null;

        return [
            'no_order' => $order->NoOrder,
            'tipe' => $tipe,
            'customer' => $order->customer?->NmCust ?? $order->KdCust,
            'created_at' => $order->created_at,
            'status' => $order->status,
            'status_bayar' => $order->status_bayar,
            'jumlah_piutang' => $order->jumlah_piutang,
            'durasi' => $durasi,
            'operator_file' => $order->createdBy?->name,
            'kasir' => $order->kasir?->name,
            'desain_by' => $order->desainBy?->name,
            'cetak_by' => $order->cetakBy?->name,
            'finishing_by' => $order->finishingBy?->name,
            'qc_by' => $order->qcBy?->name,
            'bungkus_by' => $order->bungkusBy?->name,
            'pengambilan_by' => $order->pengambilanBy?->name,
            'desain_progress' => $this->stageProgress($order, 'desain'),
            'cetak_progress' => $this->stageProgress($order, 'cetak'),
            'finishing_progress' => $this->stageProgress($order, 'finishing'),
            'qc_progress' => $this->stageProgress($order, 'qc'),
            'bungkus_progress' => $this->stageProgress($order, 'bungkus'),
            'pengambilan_progress' => $this->stageProgress($order, 'siap_diambil'),
            'progress' => $order->status === 'selesai' ? null : $this->stageProgress($order, $order->status),
            'selesai' => $selesaiAt !== null,
            'telat' => $this->isOverdue($order),
        ];
    }

    /**
     * Live "N/M unit" progress at a given stage — N is how much qty has
     * already moved PAST that stage (summed across the order's line
     * items), out of the order's total Qty. This has to sum every bucket
     * strictly AFTER $stage, not just "Qty minus this bucket" — a fast
     * unit can already be sitting several stages ahead while a slower
     * unit from the same line hasn't even reached this stage yet, so
     * "not currently in this bucket" is not the same as "moved past it".
     */
    private const BUCKET_STAGES = ['desain', 'cetak', 'finishing', 'qc', 'bungkus', 'siap_diambil', 'selesai'];

    private function stageProgress($order, string $stage): ?string
    {
        $index = array_search($stage, self::BUCKET_STAGES, true);

        if ($index === false || ! $order->relationLoaded('items') || $order->items->isEmpty()) {
            return null;
        }

        $laterStages = array_slice(self::BUCKET_STAGES, $index + 1);
        $total = $order->items->sum('Qty');

        if ($total === 0) {
            return null;
        }

        $done = $order->items->sum(function ($item) use ($laterStages) {
            return collect($laterStages)->sum(fn ($s) => (int) $item->{"qty_{$s}"});
        });

        return "{$done}/{$total}";
    }

    private function isOverdue($order): bool
    {
        return ! in_array($order->status, ['siap_diambil', 'selesai', 'batal'], true)
            && $order->created_at
            && $order->created_at->lt(now()->subHours(72));
    }
}
