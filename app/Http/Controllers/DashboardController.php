<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Services\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        // Tema "Ruang Cetak"; ganti ke view('dashboard', ...) untuk kembali ke dashboard lama.
        return view('dashboard-ruang-cetak', $this->loadData($request));
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
     * Resume order yang belum siap setelah 24 jam. Endpoint ini dipanggil
     * hanya saat kartu telat dibuka dan selalu mengembalikan data paginasi.
     */
    public function overdueOrders(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:10,50'],
            'type' => ['nullable', 'in:indoor,outdoor,artwork'],
            'status' => ['nullable', 'in:baru,desain,cetak,finishing,qc,bungkus'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $orders = $this->stats->overdueOrders(
            page: (int) ($validated['page'] ?? 1),
            perPage: (int) ($validated['per_page'] ?? 25),
            type: $validated['type'] ?? null,
            status: $validated['status'] ?? null,
            from: $validated['from'] ?? null,
            to: $validated['to'] ?? null,
        );

        $stageLabels = self::STAGE_LABELS;
        $typeLabels = ['indoor' => 'Indoor', 'outdoor' => 'Outdoor', 'artwork' => 'Artwork'];

        return response()->json([
            'data' => $orders->getCollection()->map(function ($order) use ($stageLabels, $typeLabels) {
                $createdAt = Carbon::parse($order->created_at);
                $operatorColumn = match ($order->status) {
                    'desain' => 'desain_by',
                    'cetak' => 'cetak_by',
                    'finishing' => 'finishing_by',
                    'qc' => 'qc_by',
                    'bungkus' => 'bungkus_by',
                    default => null,
                };
                $operator = $operatorColumn ? $order->{str_replace('_by', '_operator', $operatorColumn)} : null;

                return [
                    'id' => $order->id,
                    'type_slug' => $order->type_slug,
                    'type' => $typeLabels[$order->type_slug] ?? ucfirst($order->type_slug),
                    'no_order' => $order->NoOrder,
                    'customer' => $order->customer ?: $order->KdCust,
                    'created_at' => $createdAt->format('d M Y · H:i'),
                    'overdue' => $createdAt->locale('id')->diffForHumans(now(), true),
                    'status' => $order->status,
                    'status_label' => $stageLabels[$order->status] ?? ucfirst($order->status),
                    'status_bayar' => $order->status_bayar,
                    'operator' => $operator,
                ];
            })->values(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadData(Request $request): array
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : null;
        $to = $request->filled('to') ? $request->string('to')->toString() : null;

        // Tanpa filter tanggal, cukup tampilkan 50 order terbaru seperti
        // biasa. Begitu operator mencari rentang tanggal tertentu, batas
        // dinaikkan supaya semua order dalam rentang itu ikut tampil.
        $limit = ($from || $to) ? 500 : 50;

        return [
            'stats' => $this->stats->stats($from, $to),
            'recent' => $this->stats->recentOrders($limit, $from, $to),
            'from' => $from,
            'to' => $to,
        ];
    }
}
