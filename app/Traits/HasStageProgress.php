<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

/**
 * For models with a `status` column tracking a fixed pipeline (desain →
 * cetak → finishing → qc → bungkus → siap_diambil), where each stage's
 * `_at` timestamp is written when that stage is COMPLETED — i.e. it also
 * marks the moment the order entered the next stage. That lets us compute
 * "how long has this order sat in its current stage" from data that
 * already exists, with no extra column.
 */
trait HasStageProgress
{
    /**
     * Header `status` values that are never derived from line items —
     * pre-production (order not yet paid) or terminal (cancelled / fully
     * picked up by the customer).
     */
    public const NON_ITEM_DERIVED_STATUSES = ['baru', 'dibayar', 'batal', 'selesai'];

    public function stageEnteredAt(): ?Carbon
    {
        return match ($this->status) {
            'desain' => $this->created_at,
            'cetak' => $this->desain_at,
            'finishing' => $this->cetak_at,
            'qc' => $this->finishing_at,
            'bungkus' => $this->qc_at,
            'siap_diambil' => $this->bungkus_at,
            default => null,
        };
    }

    public function isMacet(int $days = 3): bool
    {
        $enteredAt = $this->stageEnteredAt();

        return $enteredAt !== null && $enteredAt->diffInDays(now()) >= $days;
    }

    /**
     * The stage whose `_at` column is stamped once every item has just
     * cleared it — i.e. the stage immediately before $stage in the
     * pipeline. Inverse of the `stageEnteredAt()` match above.
     */
    public static function stageBefore(string $stage): ?string
    {
        return match ($stage) {
            'cetak' => 'desain',
            'finishing' => 'cetak',
            'qc' => 'finishing',
            'bungkus' => 'qc',
            'siap_diambil' => 'bungkus',
            default => null,
        };
    }

    /**
     * Recomputes the header `status` as the LEAST-advanced stage among this
     * order's line items, and writes it back — same column, same
     * downstream consumers (Kasir, Papan Pantau, Dashboard, File Monitor,
     * isMacet), just now driven by item-level progress instead of being
     * set directly by a stage controller. Called after every item-stage
     * transition (see StageProgressService::advanceItem()); never touches
     * pre-production/terminal orders.
     */
    public function recalculateStatus(): void
    {
        if (in_array($this->status, self::NON_ITEM_DERIVED_STATUSES, true)) {
            return;
        }

        $items = $this->detailItems();

        if ($items->isEmpty()) {
            return;
        }

        $leastIndex = $items->min(fn ($item) => $item->earliestActiveStageIndex());
        $newStage = $items->first()::stageName($leastIndex);

        if ($newStage === $this->status) {
            return;
        }

        $update = ['status' => $newStage];

        $prevStage = self::stageBefore($newStage);
        if ($prevStage && ! $this->{"{$prevStage}_at"}) {
            $update["{$prevStage}_by"] = auth()->id();
            $update["{$prevStage}_at"] = now();
        }

        $this->update($update);
    }
}
