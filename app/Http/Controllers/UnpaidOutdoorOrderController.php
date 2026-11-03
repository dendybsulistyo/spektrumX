<?php

namespace App\Http\Controllers;

use App\Models\OrderOutdoor;
use App\Services\OrderPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Daftar cepat (Ctrl+B) Order Outdoor yang belum dibayar, lengkap dengan
 * rincian item & ukuran asli. Hanya baca.
 */
class UnpaidOutdoorOrderController extends Controller
{
    public const PERMISSIONS = ['kasir.view', 'customer-service.view', 'order-outdoor.view', 'keuangan.view'];

    private const LIMIT = 150;

    public function __invoke(Request $request, OrderPricingService $pricing): JsonResponse
    {
        $canOpenKasir = $request->user()->hasPermission('kasir.view');

        $orders = OrderOutdoor::query()
            ->with(['customer:id,KdCust,NmCust', 'items.hargaCetak'])
            ->where('status_bayar', 'belum_bayar')
            ->where('status', '!=', 'batal')
            ->whereNull('invoice_voided_at')
            ->orderByDesc('TglOrder')->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();

        return response()->json([
            'limit' => self::LIMIT,
            'orders' => $orders->map(fn (OrderOutdoor $order) => [
                'id' => $order->id,
                'no_order' => $order->NoOrder,
                'customer' => $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : ($order->KdCust ?: '-'),
                'date_label' => optional($order->TglOrder)->locale('id')->translatedFormat('d M Y'),
                'queue' => ($order->payment_queue ?? 'kasir') === 'kasir' ? 'Kasir' : 'CS',
                'total' => (float) ($order->total ?? 0),
                'kasir_url' => $canOpenKasir ? route('kasir.show', ['type' => 'outdoor', 'id' => $order->id]) : null,
                'items' => $pricing->detailedLineItems('outdoor', $order, $order->items)->map(fn ($item) => [
                    'name' => $item->name ?: '-',
                    'panjang' => $item->panjang,
                    'lebar' => $item->lebar,
                    'qty' => $item->qty,
                    'bahan' => $item->bahan,
                    'printer' => $item->printer,
                    'subtotal' => (float) $item->subtotal,
                ])->values(),
            ])->values(),
        ]);
    }
}
