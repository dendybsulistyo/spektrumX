<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Services\OrderPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class RekapPenerimaanController extends Controller
{
    private const GROUPS = [
        'print_dokumen' => ['label' => 'PRINT DOKUMEN', 'items' => [
            '01' => 'Canon Document Color', '02' => 'Canon B/W', '03' => 'Paper/Media Document',
            '04' => 'Laminating POD', '05' => 'Kartu Nama', '06' => 'ID Card',
            '07' => 'Cetak Offset', '08' => 'Laminating & UV Offset',
        ]],
        'prepress' => ['label' => 'PREPRESS', 'items' => ['09' => 'CTcP']],
        'print_outdoor' => ['label' => 'PRINT OUTDOOR', 'items' => [
            'printer:01' => 'Konica Minolta 42 pl', 'printer:02' => 'Konica Minolta 14 pl',
            'printer:03' => 'Scitex XL Jet', 'printer:06' => 'Direct Sublim',
        ]],
        'print_indoor' => ['label' => 'PRINT INDOOR', 'items' => [
            'printer:07' => 'Epson Indoor', '11' => 'Laminating Poster',
            'printer:04' => 'Mimaki UV SIJ', 'printer:05' => 'Epson Ecosolvent',
        ]],
        'digital_artwork' => ['label' => 'DIGITAL ARTWORK', 'items' => [
            '12' => 'Flatbed Print', '13' => 'Media Flatbed', '14' => 'Accessories Flatbed',
            '15' => 'Finishing Artwork', '16' => 'Cutting CNC', '17' => 'Produk Jadi',
            '28' => 'Print Tumbler',
        ]],
        'print_textile' => ['label' => 'PRINT TEXTILE', 'items' => [
            '18' => 'Dye Sublimation', '19' => 'DTG', '27' => 'DTF',
        ]],
        'lain_lain' => ['label' => 'LAIN-LAIN', 'items' => [
            '20' => 'Lain-lain', '21' => 'Consumable', '22' => 'Sunblasting', '23' => 'Scan',
            '24' => 'Kalibrasi', '25' => 'Penjualan Paket', '26' => 'Stand Banner',
        ]],
    ];

    public function __construct(private readonly OrderPricingService $pricing) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $from = CarbonImmutable::parse($filters['dari'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($filters['sampai'] ?? now()->toDateString())->startOfDay();
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $amounts = collect();
        $this->collectOrders('indoor', OrderIndoor::class, $from, $to, $amounts);
        $this->collectOrders('outdoor', OrderOutdoor::class, $from, $to, $amounts);
        $this->collectOrders('artwork', OrderArtwork::class, $from, $to, $amounts);

        $groups = collect(self::GROUPS)->map(function (array $group) use ($amounts) {
            $items = collect($group['items'])->map(fn (string $label, string $key) => (object) [
                'label' => $label,
                'amount' => (float) $amounts->get($key, 0),
            ])->values();

            return (object) [
                'label' => $group['label'],
                'items' => $items,
                'total' => (float) $items->sum('amount'),
            ];
        })->values();

        $unmapped = (float) $amounts->except(collect(self::GROUPS)->flatMap(fn ($group) => array_keys($group['items'])))->sum();
        if ($unmapped !== 0.0) {
            $other = $groups->last();
            $other->items->push((object) ['label' => 'Belum terkelompok', 'amount' => $unmapped]);
            $other->total += $unmapped;
        }

        return view('rekap-penerimaan.index', [
            'from' => $from,
            'to' => $to,
            'groups' => $groups,
            'grandTotal' => (float) $groups->sum('total'),
        ]);
    }

    /** @param class-string<OrderIndoor|OrderOutdoor|OrderArtwork> $model */
    
    private function collectOrders(string $type, string $model, CarbonImmutable $from, CarbonImmutable $to, Collection $amounts): void
    {
        $relations = $type === 'outdoor' ? ['items.hargaCetak'] : ['items'];
        $orders = $model::query()->with($relations)
            ->whereBetween('TglOrder', [$from->toDateString(), $to->toDateString()])
            ->where('status', '!=', 'batal')
            ->where('status_bayar', '!=', 'belum_bayar')
            ->get();

        foreach ($orders as $order) {
            $lines = $this->pricing->detailedLineItems($type, $order, $order->items);
            $gross = (float) $lines->sum('subtotal');
            $net = $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;
            $allocated = 0.0;

            foreach ($lines->values() as $index => $line) {
                $last = $index === $lines->count() - 1;
                $amount = $last ? $net - $allocated : ($gross > 0 ? round($net * (float) $line->subtotal / $gross) : 0);
                $allocated += $amount;
                $key = $type === 'outdoor'
                    ? 'printer:'.substr((string) $order->items->values()->get($index)?->KdCtk, 0, 2)
                    : substr((string) $line->kd_prod, 0, 2);
                $amounts->put($key, (float) $amounts->get($key, 0) + $amount);
            }
        }
    }
}
