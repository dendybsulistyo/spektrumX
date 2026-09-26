<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MonitoringKinerjaController extends Controller
{
    private const STAGES = ['desain', 'cetak', 'finishing', 'qc', 'bungkus', 'kasir', 'pengambilan', 'pembatalan'];

    private const ORDER_MODELS = [
        'indoor' => OrderIndoor::class,
        'outdoor' => OrderOutdoor::class,
        'artwork' => OrderArtwork::class,
    ];

    private const ORDER_TYPE_LABELS = [
        'indoor' => 'Indoor',
        'outdoor' => 'Outdoor',
        'artwork' => 'Artwork',
    ];

    /**
     * Order-level activity (one row per stage transition) comes from
     * order_status_notes, which is common to Indoor/Outdoor/Artwork and every
     * stage (desain/cetak/finishing/qc/kasir/pengambilan/pembatalan).
     */
    public function index(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $noteCounts = OrderStatusNote::query()
            ->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->whereIn('stage', self::STAGES)
            ->select('user_id', 'stage', DB::raw('count(*) as jumlah'))
            ->groupBy('user_id', 'stage')
            ->get();

        $users = User::whereIn('id', $noteCounts->pluck('user_id')->unique())->pluck('name', 'id');

        $staffRows = [];
        foreach ($noteCounts as $row) {
            $staffRows[$row->user_id]['name'] ??= $users[$row->user_id] ?? 'Tidak diketahui';
            $staffRows[$row->user_id]['counts'][$row->stage] = $row->jumlah;
        }
        uasort($staffRows, fn ($a, $b) => array_sum($b['counts']) <=> array_sum($a['counts']));

        return view('monitoring-kinerja.index', [
            'from' => $from,
            'to' => $to,
            'stages' => self::STAGES,
            'staffRows' => $staffRows,
        ]);
    }

    public function show(Request $request, User $staff): View
    {
        [$from, $to] = $this->dateRange($request);

        $notes = OrderStatusNote::query()
            ->where('user_id', $staff->id)
            ->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
            ->whereIn('stage', self::STAGES)
            ->orderBy('created_at')
            ->get();

        $orders = $this->loadOrders($notes);
        $rows = $notes
            ->groupBy(fn (OrderStatusNote $note) => $note->order_type.':'.$note->order_id)
            ->map(function (Collection $orderNotes, string $key) use ($orders) {
                [$orderType, $orderId] = explode(':', $key, 2);
                /** @var Model|null $order */
                $order = $orders->get($key);
                $items = $order?->items ?? collect();
                $notesByDetail = $orderNotes->whereNotNull('order_detail_id')->groupBy('order_detail_id');
                $grossTotal = (float) ($order?->total ?? 0);
                $netTotal = $order && method_exists($order, 'totalSetelahDiskon')
                    ? (float) $order->totalSetelahDiskon()
                    : $grossTotal;

                return [
                    'key' => $key,
                    'order_type' => $orderType,
                    'order_type_label' => self::ORDER_TYPE_LABELS[$orderType] ?? ucfirst($orderType),
                    'order_id' => (int) $orderId,
                    'no_order' => $order?->NoOrder ?? '#'.$orderId,
                    'tanggal_order' => $order?->TglOrder,
                    'customer' => $order?->customer?->NmCust ?? $order?->KdCust ?? '-',
                    'status' => $order?->status ?? '-',
                    'item_count' => $items->count(),
                    'order_qty' => (int) $items->sum(fn ($item) => (int) ($item->Qty ?? 0)),
                    'touched_item_count' => $notesByDetail->keys()->count(),
                    'processed_qty' => (int) $orderNotes->sum(fn ($note) => (int) ($note->qty ?? 0)),
                    'activity_count' => $orderNotes->count(),
                    'gross_total' => $grossTotal,
                    'discount' => max(0, $grossTotal - $netTotal),
                    'net_total' => $netTotal,
                    'first_activity_at' => $orderNotes->first()?->created_at,
                    'last_activity_at' => $orderNotes->last()?->created_at,
                    'stage_summary' => $orderNotes->groupBy('stage')->map(fn (Collection $stageNotes, string $stage) => [
                        'stage' => $stage,
                        'count' => $stageNotes->count(),
                        'qty' => (int) $stageNotes->sum(fn ($note) => (int) ($note->qty ?? 0)),
                    ])->values(),
                    'items' => $items->map(function ($item) use ($notesByDetail) {
                        $itemNotes = $notesByDetail->get($item->id, collect());

                        return [
                            'id' => $item->id,
                            'label' => $item->NmFile ?: ($item->NmProd ?: ($item->Judul ?: 'Item '.$item->BrsOrder)),
                            'description' => $item->Judul && $item->Judul !== $item->NmProd ? $item->Judul : null,
                            'qty' => (int) ($item->Qty ?? 0),
                            'processed_qty' => (int) $itemNotes->sum(fn ($note) => (int) ($note->qty ?? 0)),
                            'stages' => $itemNotes->pluck('stage')->unique()->values(),
                        ];
                    })->values(),
                    'activities' => $orderNotes->map(fn (OrderStatusNote $note) => [
                        'stage' => $note->stage,
                        'action' => $note->action,
                        'qty' => (int) ($note->qty ?? 0),
                        'catatan' => $note->catatan,
                        'created_at' => $note->created_at,
                    ])->values(),
                ];
            })
            ->sortByDesc('last_activity_at')
            ->values();

        $summary = [
            'orders' => $rows->count(),
            'items' => $rows->sum('item_count'),
            'touched_items' => $rows->sum('touched_item_count'),
            'processed_qty' => $rows->sum('processed_qty'),
            'activities' => $notes->count(),
            'active_days' => $notes->pluck('created_at')->filter()->map->format('Y-m-d')->unique()->count(),
            'gross_total' => $rows->sum('gross_total'),
            'discount' => $rows->sum('discount'),
            'net_total' => $rows->sum('net_total'),
        ];

        $stageStats = collect(self::STAGES)->mapWithKeys(function (string $stage) use ($notes) {
            $stageNotes = $notes->where('stage', $stage);

            return [$stage => [
                'activities' => $stageNotes->count(),
                'qty' => (int) $stageNotes->sum(fn ($note) => (int) ($note->qty ?? 0)),
                'orders' => $stageNotes->map(fn ($note) => $note->order_type.':'.$note->order_id)->unique()->count(),
            ]];
        });

        return view('monitoring-kinerja.show', compact('staff', 'from', 'to', 'rows', 'summary', 'stageStats'));
    }

    private function dateRange(Request $request): array
    {
        $dates = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $from = ! empty($dates['from'])
            ? CarbonImmutable::parse($dates['from'])->format('Y-m-d')
            : now()->startOfMonth()->format('Y-m-d');
        $to = ! empty($dates['to'])
            ? CarbonImmutable::parse($dates['to'])->format('Y-m-d')
            : now()->format('Y-m-d');

        return $from <= $to ? [$from, $to] : [$to, $from];
    }

    private function loadOrders(Collection $notes): Collection
    {
        return collect(self::ORDER_MODELS)->flatMap(function (string $modelClass, string $orderType) use ($notes) {
            $ids = $notes->where('order_type', $orderType)->pluck('order_id')->unique()->values();
            if ($ids->isEmpty()) {
                return [];
            }

            return $modelClass::query()
                ->with(['customer', 'items'])
                ->whereIn('id', $ids)
                ->get()
                ->mapWithKeys(fn (Model $order) => [$orderType.':'.$order->getKey() => $order]);
        });
    }
}
