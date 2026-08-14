<?php

namespace App\Traits;

/**
 * For line-item ("detail") models whose `Qty` is split across qty_<stage>
 * buckets (qty_desain, qty_cetak, qty_finishing, qty_qc, qty_bungkus,
 * qty_siap_diambil, qty_selesai — always summing to Qty). Qty flows forward
 * one bucket at a time as operators process it, so a single line can have
 * some qty already at Cetak while the rest is still at Desain — see
 * StageProgressService::advance().
 */
trait HasItemStageProgress
{
    public const STAGE_ORDER = ['desain', 'cetak', 'finishing', 'qc', 'bungkus', 'siap_diambil', 'selesai'];

    public const NEXT_STAGE = [
        'desain' => 'cetak',
        'cetak' => 'finishing',
        'finishing' => 'qc',
        'qc' => 'bungkus',
        'bungkus' => 'siap_diambil',
        'siap_diambil' => 'selesai',
    ];

    public function qtyAt(string $stage): int
    {
        return (int) $this->{"qty_{$stage}"};
    }

    /**
     * The earliest stage (in pipeline order) where this item still has any
     * qty sitting — i.e. how far behind its slowest portion is. Used to
     * roll up the parent order's header `status` (see
     * HasStageProgress::recalculateStatus()) and, per-item, to know which
     * stage still needs attention.
     */
    public function earliestActiveStageIndex(): int
    {
        foreach (self::STAGE_ORDER as $index => $stage) {
            if ($this->qtyAt($stage) > 0) {
                return $index;
            }
        }

        return count(self::STAGE_ORDER) - 1;
    }

    public function earliestActiveStage(): string
    {
        return self::STAGE_ORDER[$this->earliestActiveStageIndex()];
    }

    /**
     * Trait constants can't be referenced as `TraitName::CONST` from outside
     * a class that uses the trait — this lets other code resolve a
     * STAGE_ORDER index back to its stage name via any item instance.
     */
    public static function stageName(int $index): string
    {
        return self::STAGE_ORDER[$index];
    }

    public static function nextStage(string $stage): ?string
    {
        return self::NEXT_STAGE[$stage] ?? null;
    }
}
