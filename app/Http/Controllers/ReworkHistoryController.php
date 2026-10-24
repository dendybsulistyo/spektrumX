<?php

namespace App\Http\Controllers;

use App\Models\OrderReworkRequest;
use App\Services\OrderDocumentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Riwayat ulang proses (Layout s/d Pengambilan Barang) untuk admin:
 * order mana, tahap apa, alasan, dan siapa yang meminta/memutuskan.
 */
class ReworkHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'tahap' => ['nullable', Rule::in(array_keys(OrderReworkRequest::STAGE_LABELS))],
            'jenis' => ['nullable', Rule::in(['ulang', 'batal', 'semua'])],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'search' => ['nullable', 'string', 'max:60'],
        ]);
        $dari = $filters['dari'] ?? now()->startOfMonth()->format('Y-m-d');
        $sampai = $filters['sampai'] ?? now()->format('Y-m-d');
        $jenis = $filters['jenis'] ?? 'ulang';
        $search = trim((string) ($filters['search'] ?? ''));

        // Pencarian nomor order: ubah dulu jadi daftar id per jenis order.
        $searchIds = [];
        if ($search !== '') {
            foreach (OrderDocumentService::MODELS as $type => $model) {
                $searchIds[$type] = $model::query()->where('NoOrder', 'like', "%{$search}%")->limit(500)->pluck('id')->all();
            }
        }

        $requests = OrderReworkRequest::query()
            ->with(['requestedBy:id,name', 'resolvedBy:id,name'])
            ->whereBetween('requested_at', [$dari.' 00:00:00', $sampai.' 23:59:59'])
            ->when($jenis !== 'semua', fn ($query) => $query->where('action', $jenis))
            ->when($filters['tahap'] ?? null, fn ($query, $stage) => $query->where('current_stage', $stage))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($searchIds) {
                $query->whereRaw('1 = 0');
                foreach ($searchIds as $type => $ids) {
                    if ($ids) {
                        $query->orWhere(fn ($q) => $q->where('order_type', $type)->whereIn('order_id', $ids));
                    }
                }
            }))
            ->latest('requested_at')
            ->paginate(50)
            ->withQueryString();

        // Nomor order & customer untuk baris di halaman ini saja.
        $orders = collect();
        foreach (collect($requests->items())->groupBy('order_type') as $type => $rows) {
            $model = OrderDocumentService::MODELS[$type] ?? null;
            if (! $model) {
                continue;
            }
            $model::query()->with('customer:KdCust,NmCust')->whereIn('id', $rows->pluck('order_id')->unique())
                ->get(['id', 'NoOrder', 'KdCust'])
                ->each(fn ($order) => $orders->put($type.'-'.$order->id, $order));
        }

        return view('rework-history.index', [
            'requests' => $requests,
            'orders' => $orders,
            'stageLabels' => OrderReworkRequest::STAGE_LABELS,
            'dari' => $dari, 'sampai' => $sampai, 'jenis' => $jenis, 'search' => $search,
            'tahap' => $filters['tahap'] ?? null, 'status' => $filters['status'] ?? null,
        ]);
    }
}
