<?php

namespace App\Http\Controllers;

use App\Services\OrderPricingService;
use App\Support\ResolvesOrderType;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use ResolvesOrderType;

    public function __construct(private readonly OrderPricingService $pricingService) {}

    public function show(string $type, int $id): View
    {
        $order = $this->resolveOrder($type, $id);
        $order->load('customer', 'kasir');

        // The "nota pengganti" (replacement invoice) feature only exists for
        // Order Outdoor — Indoor/Artwork models don't define this relation.
        if ($type === 'outdoor') {
            $order->load('replaces');
        }

        $rawItems = match ($type) {
            'indoor' => $order->detailItems(),
            'outdoor' => $order->items()->with('hargaCetak')->get(),
            'artwork' => $order->items,
            default => abort(404),
        };

        $items = $this->pricingService->detailedLineItems($type, $order, $rawItems);

        return view('invoice.show', ['type' => $type, 'order' => $order, 'items' => $items]);
    }
}
