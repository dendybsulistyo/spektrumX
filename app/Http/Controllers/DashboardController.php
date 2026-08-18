<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Services\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const STAGE_LABELS = [
        'desain' => 'Desain',
        'cetak' => 'Cetak',
        'finishing' => 'Finishing',
        'qc' => 'Back Office',
        'bungkus' => 'Bungkus',
        'siap_diambil' => 'Siap Diambil',
        'selesai' => 'Pengambilan',
    ];

    public function __construct(private readonly DashboardStatsService $stats) {}

    public function index(Request $request): View
    {
        return view('dashboard', $this->loadData($request));
    }

    /**
     * Per-item progress (current qty per stage bucket) plus the full
     * OrderStatusNote history log for one order — powers the "riwayat
     * proses" popup opened from an order number on the dashboard.
     */
    public function orderProgress(string $type, int $id): JsonResponse
    {
        $order = match ($type) {
            'indoor' => OrderIndoor::with('customer', 'items')->findOrFail($id),
            'outdoor' => OrderOutdoor::with('customer', 'items')->findOrFail($id),
            'artwork' => OrderArtwork::with('customer', 'items')->findOrFail($id),
            default => abort(404),
        };

        $items = $order->items->map(fn ($item) => [
            'id' => $item->id,
            'name' => $type === 'outdoor' ? ($item->gabungan ?: '-') : $item->Judul,
            'file' => $type === 'outdoor' ? ($item->NmFile ?: null) : null,
            'qty_total' => $item->Qty,
            'stages' => collect(self::STAGE_LABELS)->mapWithKeys(
                fn ($label, $stage) => [$stage => ['label' => $label, 'qty' => (int) $item->{"qty_{$stage}"}]]
            )->values(),
        ]);

        $history = OrderStatusNote::forOrder($type, $id)
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($note) => [
                'created_at' => $note->created_at?->format('d M Y H:i'),
                'stage' => self::STAGE_LABELS[$note->stage] ?? ucfirst($note->stage),
                'stage_key' => $note->stage,
                'qty' => $note->qty,
                'action' => $note->action,
                'user' => $note->user?->name ?? '-',
                'catatan' => $note->catatan,
            ]);

        return response()->json([
            'no_order' => $order->NoOrder,
            'customer' => $order->customer?->NmCust,
            'items' => $items,
            'history' => $history,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(Request $request): array
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : null;
        $to = $request->filled('to') ? $request->string('to')->toString() : null;

        // Tanpa filter tanggal, cukup tampilkan 30 order terbaru seperti
        // biasa. Begitu operator mencari rentang tanggal tertentu, batas
        // dinaikkan supaya semua order dalam rentang itu ikut tampil.
        $limit = ($from || $to) ? 500 : 30;

        return [
            'stats' => $this->stats->stats($from, $to),
            'recent' => $this->stats->recentOrders($limit, $from, $to),
            'from' => $from,
            'to' => $to,
        ];
    }
}
