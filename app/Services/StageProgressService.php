<?php

namespace App\Services;

use App\Models\OrderArtworkDetail;
use App\Models\OrderIndoorDetail;
use App\Models\OrderOutdoorDetail;
use App\Models\OrderReworkRequest;
use App\Models\OrderStatusNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Moves N qty from one line item's qty_<stage> bucket to the next one, so
 * qty flows forward as soon as any portion of it is done at a stage — the
 * rest of that same line can keep sitting at the earlier stage. The parent
 * order's header `status` is recalculated afterwards as the least-advanced
 * stage among all its items (see HasStageProgress::recalculateStatus()).
 */
class StageProgressService
{
    /**
     * Header statuses where an order hasn't actually entered production
     * (unpaid) or is cancelled — items must never surface in a production
     * queue for these.
     */
    public const EXCLUDE_ORDER_STATUSES = ['baru', 'dibayar', 'batal'];

    /**
     * @return array{order: Model, item: Model, moved: int, stageCleared: bool}
     */
    public function advance(Model $item, string $fromStage, int $qty, ?string $catatan, int $userId): array
    {
        $nextStage = $item::nextStage($fromStage);
        abort_if($nextStage === null, 500, "Tidak ada tahap berikutnya dari {$fromStage}.");

        $order = $item->order;

        abort_if($order->cancel_requested_at, 422, 'Order ini sedang menunggu persetujuan pembatalan.');
        abort_if(
            OrderReworkRequest::forOrder($item->orderTypeSlug(), $order->id)->pending()->exists(),
            422,
            'Order ini sedang menunggu persetujuan pembatalan/ulang proses.'
        );
        $availableQty = $item->qtyAt($fromStage);

        // The amount can become stale between rendering the queue and an
        // operator submitting it (for example after another operator moves
        // part of the same item). Treat it as normal form validation, not a
        // server exception page.
        if ($qty < 1 || $qty > $availableQty) {
            $message = $availableQty > 0
                ? "Qty tidak valid. Sisa Qty di tahap {$fromStage}: {$availableQty}."
                : "Item ini sudah tidak memiliki sisa Qty di tahap {$fromStage}. Muat ulang halaman.";

            throw ValidationException::withMessages(['qty' => $message]);
        }

        $item->decrement("qty_{$fromStage}", $qty);
        $item->increment("qty_{$nextStage}", $qty);
        $item->refresh();

        $stageCleared = $item->qtyAt($fromStage) === 0;

        OrderStatusNote::create([
            'order_type' => $item->orderTypeSlug(),
            'order_id' => $order->id,
            'order_detail_id' => $item->id,
            'qty' => $qty,
            'stage' => $fromStage,
            'action' => $stageCleared ? 'selesai' : 'progress',
            'catatan' => $catatan,
            'user_id' => $userId,
            'created_at' => now(),
        ]);

        $order->recalculateStatus();

        return ['order' => $order, 'item' => $item, 'moved' => $qty, 'stageCleared' => $stageCleared];
    }

    /**
     * Line items with any qty currently sitting at $stage, grouped by
     * parent order id, for the given types. Orders that haven't been paid
     * yet or were cancelled are excluded even though their items may still
     * show a (harmless, backfilled) qty at 'desain'.
     *
     * @param  array<string, bool>  $showTypes  e.g. ['indoor' => true, 'outdoor' => true, 'artwork' => true]
     * @return array<string, Collection>
     */
    public function itemsAtStage(string $stage, array $showTypes, array $indoorWith = ['order.customer'], array $outdoorWith = ['order.customer'], array $artworkWith = ['order.customer']): array
    {
        $result = [];

        if ($showTypes['indoor'] ?? false) {
            $result['indoor'] = OrderIndoorDetail::query()
                ->where("qty_{$stage}", '>', 0)
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', self::EXCLUDE_ORDER_STATUSES))
                ->with($indoorWith)
                ->get()
                ->groupBy('order_indoor_id')
                ->sortByDesc(fn ($items) => $items->first()->order->NoOrder);
        }

        if ($showTypes['outdoor'] ?? false) {
            $result['outdoor'] = OrderOutdoorDetail::query()
                ->where("qty_{$stage}", '>', 0)
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', self::EXCLUDE_ORDER_STATUSES))
                ->with($outdoorWith)
                ->get()
                ->groupBy('order_outdoor_id')
                ->sortByDesc(fn ($items) => $items->first()->order->NoOrder);
        }

        if ($showTypes['artwork'] ?? false) {
            $result['artwork'] = OrderArtworkDetail::query()
                ->where("qty_{$stage}", '>', 0)
                ->whereHas('order', fn ($q) => $q->whereNotIn('status', self::EXCLUDE_ORDER_STATUSES))
                ->with($artworkWith)
                ->get()
                ->groupBy('order_artwork_id')
                ->sortByDesc(fn ($items) => $items->first()->order->NoOrder);
        }

        return $result;
    }
}
