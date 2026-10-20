<?php

namespace App\Traits;

use Illuminate\Support\Carbon;

trait HasOperatorEstimate
{
    /**
     * Estimasi dari Lembar Kerja CS selalu diprioritaskan. Order lama yang
     * belum memiliki estimasi mendapat fallback 1x24 jam sejak dibuat.
     */
    public function operatorEstimateEndsAt(): ?Carbon
    {
        $jobSheetDeadline = $this->customerServiceJobSheet?->estimateEndsAt();

        if ($jobSheetDeadline) {
            return $jobSheetDeadline;
        }

        $createdAt = $this->created_at ?? $this->TglOrder;
        if (! $createdAt) {
            return null;
        }

        $createdAt = $createdAt instanceof Carbon ? $createdAt->copy() : Carbon::parse($createdAt);

        if (! $createdAt->lt(now()->startOfDay())) {
            return null;
        }

        return $createdAt->addDay();
    }
}
