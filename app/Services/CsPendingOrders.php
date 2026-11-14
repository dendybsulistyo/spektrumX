<?php

namespace App\Services;

use App\Http\Controllers\CustomerServiceController;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\Support\Collection;

/**
 * Order Indoor/Outdoor yang masih di antrean CS dan belum dibayar, dikelompokkan per
 * customer — untuk mencocokkan transfer masuk dengan order-nya.
 */
class CsPendingOrders
{
    /**
     * @param  list<string>  $customerCodes
     * @return Collection<string, Collection<int, array{key: string, no: string, type: string, total: float, date: ?string, custom: bool}>>
     */
    public static function forCustomers(array $customerCodes, int $perCustomer = 5, bool $flagCustomArtwork = false): Collection
    {
        if ($customerCodes === []) {
            return collect();
        }

        $pending = fn (string $model) => $model::query()
            ->select(['id', 'NoOrder', 'KdCust', 'total', 'TglOrder'])
            ->where('payment_queue', 'cs')
            ->where('status_bayar', 'belum_bayar')
            ->whereIn('KdCust', $customerCodes);

        $indoor = $pending(OrderIndoor::class)
            ->when($flagCustomArtwork, fn ($query) => $query->with('items.produkArtwork'))
            ->get()
            ->map(fn (OrderIndoor $order) => self::row($order, 'indoor', $flagCustomArtwork && CustomerServiceController::customArtworkItems($order)->isNotEmpty()));
        $outdoor = $pending(OrderOutdoor::class)->get()
            ->map(fn (OrderOutdoor $order) => self::row($order, 'outdoor', false));

        return $indoor->concat($outdoor)
            ->sortByDesc(fn (array $row) => $row['date'].'-'.$row['no'])
            ->groupBy('customer')
            ->map(fn (Collection $rows) => $rows->take($perCustomer)->map(fn (array $row) => collect($row)->except('customer')->all())->values());
    }

    /**
     * @return array{key: string, no: string, type: string, total: float, date: ?string, custom: bool, customer: string}
     */
    private static function row(OrderIndoor|OrderOutdoor $order, string $type, bool $custom): array
    {
        return [
            'key' => "{$type}:{$order->id}",
            'no' => $order->NoOrder,
            'type' => $type,
            'total' => (float) $order->total,
            'date' => $order->TglOrder?->format('Y-m-d'),
            'custom' => $custom,
            'customer' => trim((string) $order->KdCust),
        ];
    }
}
