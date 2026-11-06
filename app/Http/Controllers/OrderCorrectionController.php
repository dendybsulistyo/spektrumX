<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderCorrectionRequest;
use App\Models\BahanCetakOutdoor;
use App\Models\OrderArtworkDetail;
use App\Models\OrderIndoorDetail;
use App\Models\OrderOutdoorDetail;
use App\Services\OrderCorrectionService;
use App\Support\ResolvesOrderDetailType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class OrderCorrectionController extends Controller
{
    use ResolvesOrderDetailType;

    public function __construct(private OrderCorrectionService $corrections) {}

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);
        $search = trim((string) $request->query('q'));

        return view('order-corrections.index', ['search' => $search, 'items' => $this->items($search)]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->role?->name === 'admin' || $request->user()?->hasPermission('order-correction.manage'),
            403,
            'Koreksi proses hanya dapat dilakukan admin.',
        );
    }

    public function store(StoreOrderCorrectionRequest $request, string $type, int $id): RedirectResponse
    {
        $data = $request->validated();
        $result = $this->corrections->moveTo(
            $this->resolveDetailItem($type, $id),
            $data['target_stage'],
            $data['reason'],
            $request->user()->id,
        );
        $destination = $data['target_stage'] === 'siap_diambil' ? 'Siap Diambil' : 'Bungkus';

        return redirect()->route('order-corrections.index', ['q' => $result['order']->NoOrder])
            ->with('status', "{$result['moved']} qty berhasil dikoreksi ke {$destination}.");
    }

    /** @return Collection<int, array<string, mixed>> */
    private function items(string $search): Collection
    {
        $outdoorMaterials = BahanCetakOutdoor::query()->pluck('NmBhn', 'NoCetak');
        $matchingMaterialCodes = $search === ''
            ? collect()
            : $outdoorMaterials->filter(fn (string $name): bool => str_contains(mb_strtolower($name), mb_strtolower($search)))->keys();
        $definitions = [
            'indoor' => [OrderIndoorDetail::class, ['NmProd', 'Judul']],
            'outdoor' => [OrderOutdoorDetail::class, ['NmFile']],
            'artwork' => [OrderArtworkDetail::class, ['NmProd', 'Judul']],
        ];

        return collect($definitions)->flatMap(function (array $definition, string $type) use ($search, $outdoorMaterials, $matchingMaterialCodes): Collection {
            [$model, $nameColumns] = $definition;

            return $model::query()
                ->with('order.customer')
                ->whereHas('order', fn (Builder $query) => $query->whereNotIn('status', ['baru', 'dibayar', 'batal', 'selesai']))
                ->where(function (Builder $query): void {
                    foreach (['desain', 'cetak', 'finishing', 'qc', 'bungkus'] as $stage) {
                        $query->orWhere("qty_{$stage}", '>', 0);
                    }
                })
                ->when($search !== '', function (Builder $query) use ($search, $nameColumns, $type, $matchingMaterialCodes): void {
                    $query->where(function (Builder $query) use ($search, $nameColumns, $type, $matchingMaterialCodes): void {
                        foreach ($nameColumns as $column) {
                            $query->orWhere($column, 'like', "%{$search}%");
                        }
                        if ($type === 'outdoor' && $matchingMaterialCodes->isNotEmpty()) {
                            $query->orWhere(function (Builder $materialQuery) use ($matchingMaterialCodes): void {
                                foreach ($matchingMaterialCodes as $code) {
                                    $materialQuery->orWhere('KdCtk', 'like', '__'.$code);
                                }
                            });
                        }
                        $query->orWhereHas('order', function (Builder $orderQuery) use ($search): void {
                            $orderQuery->where('NoOrder', 'like', "%{$search}%")
                                ->orWhereHas('customer', fn (Builder $customerQuery) => $customerQuery->where('NmCust', 'like', "%{$search}%"));
                        });
                    });
                })
                ->latest('id')->limit(30)->get()
                ->map(fn ($item): array => [
                    'type' => $type,
                    'item' => $item,
                    'name' => $item->NmFile ?: ($item->Judul ?: ($item->NmProd ?: '-')),
                    'material' => $type === 'outdoor' ? $outdoorMaterials->get($item->bahanCode()) : null,
                    'positions' => collect($item::STAGE_ORDER)
                        ->filter(fn (string $stage): bool => $item->qtyAt($stage) > 0)
                        ->mapWithKeys(fn (string $stage): array => [$stage => $item->qtyAt($stage)]),
                ]);
        })->sortByDesc(fn (array $row) => $row['item']->order->id)->take(60)->values();
    }
}
