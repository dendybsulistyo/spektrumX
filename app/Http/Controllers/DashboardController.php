<?php

namespace App\Http\Controllers;

use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Historical rows (pre-dating this pipeline) were backfilled to
        // status/status_bayar = selesai/lunas with no created_at — excluding
        // rows with a null created_at keeps the dashboard scoped to orders
        // actually placed through the new pipeline.
        $indoor = OrderIndoor::query()->whereNotNull('created_at')->get();
        $outdoor = OrderOutdoor::query()->get();

        $all = $indoor->concat($outdoor);

        $stats = [
            'total' => $all->count(),
            'belum_bayar' => $all->where('status_bayar', 'belum_bayar')->count(),
            'lunas' => $all->where('status_bayar', 'lunas')->count(),
            'hutang' => $all->where('status_bayar', 'hutang')->count(),
            'hutang_nominal' => $all->where('status_bayar', 'hutang')->sum('jumlah_piutang'),
            'desain' => $all->where('status', 'desain')->count(),
            'cetak' => $all->where('status', 'cetak')->count(),
            'qc' => $all->where('status', 'qc')->count(),
            'siap_diambil' => $all->where('status', 'siap_diambil')->count(),
            'selesai' => $all->where('status', 'selesai')->count(),
            'telat' => $all->filter(fn ($o) => $this->isOverdue($o))->count(),
        ];

        $recent = $this->buildRecentOrders($indoor, $outdoor);

        return view('dashboard', compact('stats', 'recent'));
    }

    private function buildRecentOrders(Collection $indoor, Collection $outdoor): Collection
    {
        $indoor->load('customer', 'kasir', 'desainBy', 'cetakBy', 'qcBy');
        $outdoor->load('customer', 'kasir', 'desainBy', 'cetakBy', 'qcBy');

        $mapped = $indoor->map(fn ($o) => $this->toRow($o, 'Indoor'))
            ->concat($outdoor->map(fn ($o) => $this->toRow($o, 'Outdoor')));

        return $mapped->sortByDesc('created_at')->take(30)->values();
    }

    private function toRow($order, string $tipe): array
    {
        $selesaiAt = $order->diambil_at;
        $durasi = $order->created_at
            ? $order->created_at->diffForHumans($selesaiAt ?? now(), true)
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
            'qc_by' => $order->qcBy?->name,
            'selesai' => $selesaiAt !== null,
            'telat' => $this->isOverdue($order),
        ];
    }

    private function isOverdue($order): bool
    {
        return ! in_array($order->status, ['siap_diambil', 'selesai', 'batal'], true)
            && $order->created_at
            && $order->created_at->lt(now()->subHours(72));
    }
}
