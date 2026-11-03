<?php

namespace App\Http\Controllers;

use App\Models\DeletedOrderLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Riwayat order yang dihapus (order percobaan/dummy) untuk admin:
 * apa yang dihapus, alasannya, siapa, dan salinan item/pembayaran/jurnalnya.
 */
class DeletedOrderLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:60']]);
        $search = trim((string) ($filters['search'] ?? ''));

        $logs = Schema::hasTable('deleted_order_logs')
            ? DeletedOrderLog::query()
                ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('no_order', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('alasan', 'like', "%{$search}%")))
                ->latest('deleted_at')
                ->paginate(30)
                ->withQueryString()
            : new LengthAwarePaginator([], 0, 30);

        return view('deleted-orders.index', compact('logs', 'search'));
    }
}
