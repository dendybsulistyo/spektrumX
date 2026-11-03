<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait HasOperatorEstimate
{
    /**
     * Batas estimasi pengerjaan (keputusan user 3 Nov 2026): dihitung sejak
     * order pertama kali diproses kasir, selama Progress Hari dari Lembar Kerja
     * CS (default 1 hari). Order yang belum dibayar belum punya batas.
     * Dipakai antrean operator, dashboard, kartu Telat & History Telat.
     */
    public function operatorEstimateEndsAt(): ?Carbon
    {
        $start = $this->diproses_kasir_at;
        if (! $start) {
            return null;
        }

        $start = $start instanceof Carbon ? $start->copy() : Carbon::parse($start);

        return $start->addDays($this->operatorEstimateDays());
    }

    public function operatorEstimateDays(): int
    {
        return max(1, (int) ($this->customerServiceJobSheet?->progress_hari ?: 1));
    }
}
