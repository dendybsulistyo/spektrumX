<?php

namespace App\Services;

use App\Models\OrderReworkRequest;
use App\Models\OrderStatusNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCorrectionService
{
    private const TARGET_STAGES = ['bungkus', 'siap_diambil'];

    /** @return array{order: Model, item: Model, moved: int, from: array<string, int>, target: string} */
    public function moveTo(Model $item, string $targetStage, string $reason, int $userId): array
    {
        if (! in_array($targetStage, self::TARGET_STAGES, true)) {
            throw ValidationException::withMessages(['target_stage' => 'Tahap tujuan koreksi tidak valid.']);
        }

        return DB::transaction(function () use ($item, $targetStage, $reason, $userId): array {
            $order = $item->order->newQuery()->lockForUpdate()->findOrFail($item->order->id);
            $item = $item->newQuery()->lockForUpdate()->findOrFail($item->getKey());
            $item->setRelation('order', $order);

            if (in_array($order->status, StageProgressService::EXCLUDE_ORDER_STATUSES, true)) {
                throw ValidationException::withMessages(['item' => 'Order belum masuk produksi, sudah batal, atau sudah selesai.']);
            }
            if ($order->cancel_requested_at) {
                throw ValidationException::withMessages(['item' => 'Order sedang menunggu persetujuan pembatalan.']);
            }
            if (OrderReworkRequest::forOrder($item->orderTypeSlug(), $order->id)->pending()->exists()) {
                throw ValidationException::withMessages(['item' => 'Order sedang menunggu persetujuan pembatalan/ulang proses.']);
            }

            $targetIndex = array_search($targetStage, $item::STAGE_ORDER, true);
            $sourceStages = array_slice($item::STAGE_ORDER, 0, $targetIndex);
            $quantities = collect($sourceStages)
                ->mapWithKeys(fn (string $stage): array => [$stage => $item->qtyAt($stage)])
                ->filter(fn (int $qty): bool => $qty > 0);
            $moved = $quantities->sum();

            if ($moved < 1) {
                throw ValidationException::withMessages(['item' => "Tidak ada qty sebelum tahap {$targetStage} yang dapat dikoreksi."]);
            }

            $updates = $quantities->mapWithKeys(fn (int $qty, string $stage): array => ["qty_{$stage}" => 0])->all();
            $updates["qty_{$targetStage}"] = $item->qtyAt($targetStage) + $moved;
            $updates['stage_entered_at'] = now();
            $item->update($updates);

            $loggedAt = now();
            foreach ($quantities as $sourceStage => $qty) {
                $sourceIndex = array_search($sourceStage, $item::STAGE_ORDER, true);
                $passedStages = array_slice($item::STAGE_ORDER, $sourceIndex, $targetIndex - $sourceIndex);
                foreach ($passedStages as $passedStage) {
                    OrderStatusNote::create([
                        'order_type' => $item->orderTypeSlug(), 'order_id' => $order->id,
                        'order_detail_id' => $item->id, 'qty' => $qty,
                        'stage' => $passedStage, 'action' => 'selesai',
                        'catatan' => "Koreksi admin ke {$targetStage}: {$reason}",
                        'user_id' => $userId, 'created_at' => $loggedAt,
                    ]);
                }
            }

            $order->recalculateStatus();

            return [
                'order' => $order->fresh(), 'item' => $item->fresh(), 'moved' => $moved,
                'from' => $quantities->all(), 'target' => $targetStage,
            ];
        });
    }
}
