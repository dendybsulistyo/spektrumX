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

        $indoor->load('customer', 'kasir', 'desainBy', 'cetakBy', 'finishingBy', 'qcBy', 'bungkusBy', 'pengambilanBy');
        $outdoor->load('customer', 'kasir', 'desainBy', 'cetakBy', 'finishingBy', 'qcBy', 'bungkusBy', 'pengambilanBy', 'items');
        $artwork->load('customer', 'kasir', 'desainBy', 'cetakBy', 'finishingBy', 'qcBy', 'bungkusBy', 'pengambilanBy');

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
            'kasir' => $order->kasir?->name,
            'desain_by' => $order->desainBy?->name,
            'cetak_by' => $order->cetakBy?->name,
            'finishing_by' => $order->finishingBy?->name,
            'qc_by' => $order->qcBy?->name,
            'bungkus_by' => $order->bungkusBy?->name,
            'pengambilan_by' => $order->pengambilanBy?->name,
            'progress' => $this->cetakProgress($order, $tipe),
            'selesai' => $selesaiAt !== null,
            'telat' => $this->isOverdue($order),
        ];
    }

    /**
     * Live "N/M unit" cetak progress for an outdoor order currently sitting
     * in the cetak stage — the per-item qty_diproses tracking only exists
     * on outdoor orders, so Indoor/Artwork or any other stage has nothing
     * to show here.
     */
    private function cetakProgress($order, string $tipe): ?string
    {
        if ($tipe !== 'Outdoor' || $order->status !== 'cetak' || ! $order->relationLoaded('items')) {
            return null;
        }

        $done = $order->items->sum('qty_diproses');
        $total = $order->items->sum('Qty');

        return $total > 0 ? "{$done}/{$total}" : null;
    }

    private function isOverdue($order): bool
    {
        return ! in_array($order->status, ['siap_diambil', 'selesai', 'batal'], true)
            && $order->created_at
            && $order->created_at->lt(now()->subHours(72));
    }
}
