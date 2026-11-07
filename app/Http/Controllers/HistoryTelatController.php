<?php

namespace App\Http\Controllers;

use App\Models\OrderLateLog;
use App\Models\OrderLateReason;
use App\Models\User;
use App\Services\OrderDocumentService;
use App\Services\OrderLateLogService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * History order telat (> 24 jam belum Siap Diambil) untuk admin:
 * order mana, tertahan di tahap apa, siapa operatornya, dan berapa lama telatnya.
 */
class HistoryTelatController extends Controller
{
    public function index(Request $request, OrderLateLogService $lateLogs): View
    {
        $filters = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'tipe' => ['nullable', Rule::in(array_keys(OrderDocumentService::MODELS))],
            'tahap' => ['nullable', Rule::in(array_keys(OrderLateLog::STAGE_LABELS))],
            'kondisi' => ['nullable', Rule::in(['berjalan', 'selesai'])],
            'sumber' => ['nullable', Rule::in(['tercatat', 'perkiraan'])],
            'search' => ['nullable', 'string', 'max:60'],
        ]);
        $dari = $filters['dari'] ?? now()->startOfMonth()->format('Y-m-d');
        $sampai = $filters['sampai'] ?? now()->format('Y-m-d');
        $search = trim((string) ($filters['search'] ?? ''));

        $view = [
            'stageLabels' => OrderLateLog::STAGE_LABELS,
            'dari' => $dari, 'sampai' => $sampai, 'search' => $search,
            'tipe' => $filters['tipe'] ?? null, 'tahap' => $filters['tahap'] ?? null,
            'kondisi' => $filters['kondisi'] ?? null, 'sumber' => $filters['sumber'] ?? null,
        ];

        if (! $lateLogs->isAvailable()) {
            return view('history-telat.index', $view + [
                'logs' => new LengthAwarePaginator([], 0, 50), 'orders' => collect(), 'operators' => collect(),
                'summary' => ['total' => 0, 'ongoing' => 0, 'stages' => collect()], 'lateReasons' => collect(), 'unavailable' => true,
            ]);
        }

        // Pencarian nomor order: ubah dulu jadi daftar id per jenis order.
        $searchIds = [];
        if ($search !== '') {
            foreach (OrderDocumentService::MODELS as $type => $model) {
                $searchIds[$type] = $model::query()->where('NoOrder', 'like', "%{$search}%")->limit(500)->pluck('id')->all();
            }
        }

        $query = OrderLateLog::query()
            ->whereBetween('late_at', [$dari.' 00:00:00', $sampai.' 23:59:59'])
            ->when($view['tipe'], fn ($query, $type) => $query->where('order_type', $type))
            ->when($view['tahap'], fn ($query, $stage) => $query->where('stage', $stage))
            ->when($view['kondisi'] === 'berjalan', fn ($query) => $query->whereNull('resolved_at'))
            ->when($view['kondisi'] === 'selesai', fn ($query) => $query->whereNotNull('resolved_at'))
            ->when($view['sumber'], fn ($query, $source) => $query->where('is_estimated', $source === 'perkiraan'))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($searchIds) {
                $query->whereRaw('1 = 0');
                foreach ($searchIds as $type => $ids) {
                    if ($ids) {
                        $query->orWhere(fn ($q) => $q->where('order_type', $type)->whereIn('order_id', $ids));
                    }
                }
            }));

        $summary = [
            'total' => (clone $query)->count(),
            'ongoing' => (clone $query)->whereNull('resolved_at')->count(),
            'stages' => (clone $query)->whereNotNull('stage')->selectRaw('stage, COUNT(*) as total')
                ->groupBy('stage')->orderByDesc('total')->pluck('total', 'stage'),
        ];

        $logs = $query->orderByRaw('resolved_at IS NULL DESC')->latest('late_at')->paginate(50)->withQueryString();

        // Nomor order, customer & operator tahap untuk baris di halaman ini saja.
        $orders = collect();
        foreach (collect($logs->items())->groupBy('order_type') as $type => $rows) {
            $model = OrderDocumentService::MODELS[$type] ?? null;
            if (! $model) {
                continue;
            }
            $model::query()->with('customer:KdCust,NmCust')->whereIn('id', $rows->pluck('order_id')->unique())
                ->get(['id', 'NoOrder', 'KdCust', 'status', 'desain_by', 'cetak_by', 'finishing_by', 'qc_by', 'bungkus_by'])
                ->each(fn ($order) => $orders->put($type.'-'.$order->id, $order));
        }
        $operatorIds = $orders->flatMap(fn ($order) => [$order->desain_by, $order->cetak_by, $order->finishing_by, $order->qc_by, $order->bungkus_by])
            ->filter()->unique();
        $operators = $operatorIds->isEmpty() ? collect() : User::query()->whereIn('id', $operatorIds)->pluck('name', 'id');

        $lateReasons = OrderLateReason::groupedFor(collect($logs->items())->groupBy('order_type')
            ->map(fn ($rows) => $rows->pluck('order_id')->unique()->all())->all());

        return view('history-telat.index', $view + compact('logs', 'orders', 'operators', 'summary', 'lateReasons') + ['unavailable' => false]);
    }
}
