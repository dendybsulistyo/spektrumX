<?php

namespace App\Http\Controllers;

use App\Support\ResolvesOrderType;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use ResolvesOrderType;

    public function show(string $type, int $id): View
    {
        $order = $this->resolveOrder($type, $id);
        $order->load('customer');

        $items = $type === 'indoor' ? $order->detailItems() : $order->items;

        return view('invoice.show', ['type' => $type, 'order' => $order, 'items' => $items]);
    }
}
