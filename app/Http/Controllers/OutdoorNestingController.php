<?php

namespace App\Http\Controllers;

use App\Models\BahanCetakOutdoor;
use App\Models\PrinterOutdoor;
use App\Services\StageProgressService;
use App\Support\OutdoorNestingPlanner;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Saran Gabung Outdoor: membaca antrean Cetak outdoor (sumber yang sama dengan
 * halaman Operator Cetak) lalu menyarankan susunan per printer + bahan.
 * Hanya baca — tidak mengubah order, qty, maupun kolom gabungan.
 */
class OutdoorNestingController extends Controller
{
    public function __construct(
        private StageProgressService $stageProgress,
        private OutdoorNestingPlanner $planner,
    ) {}

    public function index(Request $request): View
    {
        $settings = $request->validate([
            'margin' => ['nullable', 'numeric', 'between:0,50'],
            'gap' => ['nullable', 'numeric', 'between:0,50'],
        ]);
        $margin = (float) ($settings['margin'] ?? OutdoorNestingPlanner::DEFAULT_MARGIN);
        $gap = (float) ($settings['gap'] ?? OutdoorNestingPlanner::DEFAULT_GAP);

        $items = ($this->stageProgress->itemsAtStage('cetak', ['outdoor' => true], outdoorWith: ['order.customer'])['outdoor'] ?? collect())
            ->flatten(1)
            ->reject(fn ($item) => $item->order?->cancel_requested_at);

        $printerNames = PrinterOutdoor::pluck('NmPrn', 'KdPrn');
        $materialNames = BahanCetakOutdoor::pluck('NmBhn', 'NoCetak');

        $groups = $items
            ->groupBy(fn ($item) => $item->KdCtk ?: '-')
            ->map(function ($groupItems, $kdCtk) use ($printerNames, $materialNames, $margin, $gap) {
                $first = $groupItems->first();
                $material = $materialNames[$first->bahanCode()] ?? null;
                $rollWidth = OutdoorNestingPlanner::rollWidthFromName($material);
                $pieces = $groupItems->map(fn ($item) => [
                    'key' => (string) $item->id,
                    'label' => $item->order->NoOrder.' · '.($item->NmFile ?: '-'),
                    'width' => (float) $item->Panjang,
                    'height' => (float) $item->Lebar,
                    'qty' => (int) $item->qty_cetak,
                ])->values()->all();

                return [
                    'kd_ctk' => $kdCtk,
                    'printer' => $printerNames[$first->printerCode()] ?? ($first->printerCode() ?: '-'),
                    'material' => $material ?? ($first->bahanCode() ?: '-'),
                    'roll_width' => $rollWidth,
                    'items' => $groupItems->sortBy(fn ($item) => $item->order->created_at)->map(fn ($item) => [
                        'key' => (string) $item->id,
                        'no_order' => $item->order->NoOrder,
                        'customer' => $item->order->customer?->NmCust ?? $item->order->KdCust,
                        'file' => $item->NmFile ?: '-',
                        'size' => $this->size($item->Panjang, $item->Lebar),
                        'qty' => (int) $item->qty_cetak,
                        'gabungan' => $item->gabungan,
                        'age' => $item->order->created_at ? Carbon::parse($item->order->created_at)->diffForHumans(short: true) : '-',
                    ])->values(),
                    'plan' => $rollWidth && $kdCtk !== '-' ? $this->planner->plan($rollWidth, $pieces, $margin, $gap) : null,
                ];
            })
            // Kelompok yang bisa digabung (≥ 2 potongan) di atas, lalu yang paling hemat.
            ->sortByDesc(fn ($group) => [$group['items']->sum('qty') > 1, $group['plan']['efficiency'] ?? 0])
            ->values();

        return view('order-cetak.saran-gabung', compact('groups', 'margin', 'gap'));
    }

    private function size($panjang, $lebar): string
    {
        $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 1, ',', '.'), '0'), ',');

        return $number($panjang).' × '.$number($lebar);
    }
}
