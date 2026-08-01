<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PapanPantauController extends Controller
{
    /**
     * Read-only cross-stage board: every active order (Indoor/Outdoor/
     * Artwork), grouped by its current pipeline status into one column per
     * stage — so a supervisor can see where everything sits without opening
     * each stage's own menu.
     */
    private const STAGES = [
        'baru' => 'Baru',
        'dibayar' => 'Dibayar',
        'desain' => 'Layout',
        'cetak' => 'Cetak',
        'finishing' => 'Finishing',
        'qc' => 'Back Office',
        'bungkus' => 'Bungkus',
        'siap_diambil' => 'Siap Diambil',
    ];

    public function index(): View
    {
        $indoor = OrderIndoor::query()->whereNotNull('created_at')
            ->whereIn('status', array_keys(self::STAGES))
            ->with('customer')->get()
            ->map(fn ($o) => $this->toCard($o, 'Indoor'));

        $outdoor = OrderOutdoor::query()
            ->whereIn('status', array_keys(self::STAGES))
            ->with('customer')->get()
            ->map(fn ($o) => $this->toCard($o, 'Outdoor'));

        $artwork = OrderArtwork::query()
            ->whereIn('status', array_keys(self::STAGES))
            ->with('customer')->get()
            ->map(fn ($o) => $this->toCard($o, 'Artwork'));

        $cards = $indoor->concat($outdoor)->concat($artwork)->sortByDesc('created_at');

        $columns = collect(self::STAGES)->map(fn ($label, $status) => [
            'label' => $label,
            'cards' => $cards->where('status', $status)->values(),
        ]);

        return view('papan-pantau.index', compact('columns'));
    }

    /**
     * @return array<string, mixed>
     */
    private function toCard($order, string $tipe): array
    {
        return [
            'no_order' => $order->NoOrder,
            'tipe' => $tipe,
            'customer' => $order->customer?->NmCust ?? $order->KdCust,
            'status' => $order->status,
            'status_bayar' => $order->status_bayar,
            'created_at' => $order->created_at,
            'telat' => ! in_array($order->status, ['siap_diambil', 'selesai', 'batal'], true)
                && $order->created_at
                && $order->created_at->lt(now()->subHours(72)),
        ];
    }
}
