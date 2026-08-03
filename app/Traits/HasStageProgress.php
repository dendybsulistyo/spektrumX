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
}
