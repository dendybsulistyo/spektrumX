<?php

namespace App\Services;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    private const OVERDUE_EXCLUDED_STATUSES = ['siap_diambil', 'selesai', 'batal'];

    /**
     * @return array<string, mixed>
     */
    public function stats(?string $from = null, ?string $to = null): array
    {
        $totals = collect($this->scopedQueries($from, $to))
            ->map(fn ($query) => $query->selectRaw(''
                .'COUNT(*) as total, '
                .'SUM(CASE WHEN status_bayar = "belum_bayar" THEN 1 ELSE 0 END) as belum_bayar, '
                .'SUM(CASE WHEN status_bayar = "lunas" THEN 1 ELSE 0 END) as lunas, '
                .'SUM(CASE WHEN status_bayar = "hutang" THEN 1 ELSE 0 END) as hutang, '
                .'COALESCE(SUM(CASE WHEN status_bayar = "hutang" THEN jumlah_piutang ELSE 0 END), 0) as hutang_nominal, '
                .'SUM(CASE WHEN status_bayar = "dp" THEN 1 ELSE 0 END) as dp, '
                .'COALESCE(SUM(CASE WHEN status_bayar = "dp" THEN jumlah_piutang ELSE 0 END), 0) as dp_nominal, '
                .'SUM(CASE WHEN status = "desain" THEN 1 ELSE 0 END) as desain, '
                .'SUM(CASE WHEN status = "cetak" THEN 1 ELSE 0 END) as cetak, '
                .'SUM(CASE WHEN status = "finishing" THEN 1 ELSE 0 END) as finishing, '
                .'SUM(CASE WHEN status = "qc" THEN 1 ELSE 0 END) as qc, '
                .'SUM(CASE WHEN status = "bungkus" THEN 1 ELSE 0 END) as bungkus, '
                .'SUM(CASE WHEN status = "siap_diambil" THEN 1 ELSE 0 END) as siap_diambil, '
                .'SUM(CASE WHEN status = "selesai" THEN 1 ELSE 0 END) as selesai, '
                .'SUM(CASE WHEN status NOT IN ("siap_diambil", "selesai", "batal") '
                .'AND created_at IS NOT NULL AND created_at < ? THEN 1 ELSE 0 END) as telat',
                [now()->subHours(72)]
            )->first());

        $sum = fn (string $key): int => (int) $totals->sum(fn ($row) => $row->{$key} ?? 0);
        $sumAmount = fn (string $key): float => (float) $totals->sum(fn ($row) => $row->{$key} ?? 0);

        return [
            'total' => $sum('total'),
            'belum_bayar' => $sum('belum_bayar'),
            'lunas' => $sum('lunas'),
            'hutang' => $sum('hutang'),
            'hutang_nominal' => $sumAmount('hutang_nominal'),
            'dp' => $sum('dp'),
            'dp_nominal' => $sumAmount('dp_nominal'),
            'desain' => $sum('desain'),
            'cetak' => $sum('cetak'),
            'finishing' => $sum('finishing'),
            'qc' => $sum('qc'),
            'bungkus' => $sum('bungkus'),
            'siap_diambil' => $sum('siap_diambil'),
            'selesai' => $sum('selesai'),
            'telat' => $sum('telat'),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recentOrders(int $limit = 30, ?string $from = null, ?string $to = null): Collection
    {
        [$indoorQuery, $outdoorQuery, $artworkQuery] = $this->scopedQueries($from, $to);

        $indoor = $indoorQuery->latest('created_at')->limit($limit)->get();
        $outdoor = $outdoorQuery->latest('created_at')->limit($limit)->get();
        $artwork = $artworkQuery->latest('created_at')->limit($limit)->get();

        $indoor->load('customer', 'createdBy', 'kasir', 'desainBy', 'cetakBy', 'finishingBy', 'qcBy', 'bungkusBy', 'pengambilanBy', 'items');
        $outdoor->load('customer', 'createdBy', 'kasir', 'desainBy', 'cetakBy', 'finishingBy', 'qcBy', 'bungkusBy', 'pengambilanBy', 'items');
        $artwork->load('customer', 'createdBy', 'kasir', 'desainBy', 'cetakBy', 'finishingBy', 'qcBy', 'bungkusBy', 'pengambilanBy', 'items');

        $mapped = $indoor->map(fn ($o) => $this->toRow($o, 'Indoor'))
            ->concat($outdoor->map(fn ($o) => $this->toRow($o, 'Outdoor')))
            ->concat($artwork->map(fn ($o) => $this->toRow($o, 'Artwork')));

        return $mapped->sortByDesc('created_at')->take($limit)->values();
    }

    /**
     * Daftar order telat lintas tiga tabel. Query memakai UNION ALL lalu baru
     * dipaginasi, sehingga detail/order history tidak ikut dimuat ke memori.
     *
     * @return LengthAwarePaginator<object>
     */
    public function overdueOrders(
        int $page = 1,
        int $perPage = 25,
        ?string $type = null,
        ?string $status = null,
        ?string $from = null,
        ?string $to = null,
    ): LengthAwarePaginator {
        $cutoff = now()->subHours(72);
        $union = null;

        foreach (['indoor' => 'order_indoor', 'outdoor' => 'order_outdoor', 'artwork' => 'order_artwork'] as $slug => $table) {
            $query = DB::table($table)
                ->select([
                    'id', 'NoOrder', 'KdCust', 'status', 'status_bayar', 'created_at',
                    'desain_by', 'cetak_by', 'finishing_by', 'qc_by', 'bungkus_by',
                ])
                ->selectRaw('? as type_slug', [$slug])
                ->whereNotNull('created_at')
                ->where('created_at', '<', $cutoff)
                ->whereNotIn('status', self::OVERDUE_EXCLUDED_STATUSES)
                ->when($from, fn ($builder) => $builder->where('created_at', '>=', $from.' 00:00:00'))
                ->when($to, fn ($builder) => $builder->where('created_at', '<=', $to.' 23:59:59'));

            $union = $union ? $union->unionAll($query) : $query;
        }

        $orders = DB::query()->fromSub($union, 'overdue')
            ->leftJoin('customers', 'customers.KdCust', '=', 'overdue.KdCust')
            ->leftJoin('users as overdue_desain', 'overdue_desain.id', '=', 'overdue.desain_by')
            ->leftJoin('users as overdue_cetak', 'overdue_cetak.id', '=', 'overdue.cetak_by')
            ->leftJoin('users as overdue_finishing', 'overdue_finishing.id', '=', 'overdue.finishing_by')
            ->leftJoin('users as overdue_qc', 'overdue_qc.id', '=', 'overdue.qc_by')
            ->leftJoin('users as overdue_bungkus', 'overdue_bungkus.id', '=', 'overdue.bungkus_by')
            ->select(
                'overdue.*', 'customers.NmCust as customer',
                'overdue_desain.name as desain_operator', 'overdue_cetak.name as cetak_operator',
                'overdue_finishing.name as finishing_operator', 'overdue_qc.name as qc_operator',
                'overdue_bungkus.name as bungkus_operator',
            )
            ->when($type, fn ($query) => $query->where('overdue.type_slug', $type))
            ->when($status, fn ($query) => $query->where('overdue.status', $status))
            ->orderBy('overdue.created_at')
            ->orderBy('overdue.id');

        return $orders->paginate(
            perPage: max(10, min(50, $perPage)),
            page: max(1, $page)
        );
    }

    /**
     * Query builders scoped to an optional created_at date range, shared by
     * stats() and recentOrders() so both respect the same date filter.
     *
     * @return array{0: Builder, 1: Builder, 2: Builder}
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
            'id' => $order->id,
            'type_slug' => strtolower($tipe),
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
