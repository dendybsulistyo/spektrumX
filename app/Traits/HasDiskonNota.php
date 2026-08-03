<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Discount-per-nota with an approval gate: a kasir requests a percentage
 * (diskon_requested_persen), and it only ever affects money once an
 * Admin/Owner/Admin Kasir approves it — at which point diskon_persen (the
 * value actually used everywhere else, e.g. KasirController::bayar()) gets
 * set. Rejecting never touches diskon_persen, so a rejected request simply
 * has no financial effect.
 */
trait HasDiskonNota
{
    public function diskonRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diskon_requested_by');
    }

    public function diskonApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diskon_approved_by');
    }

    public function diskonRejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diskon_rejected_by');
    }

    public function diskonStatus(): string
    {
        if ($this->diskon_approved_at) {
            return 'approved';
        }

        if ($this->diskon_rejected_at) {
            return 'rejected';
        }

        if ($this->diskon_requested_at) {
            return 'pending';
        }

        return 'none';
    }

    public function diskonNominal(): float
    {
        if (! $this->diskon_persen) {
            return 0.0;
        }

        return round((float) $this->total * ((float) $this->diskon_persen / 100), 2);
    }

    public function totalSetelahDiskon(): float
    {
        return round((float) $this->total - $this->diskonNominal(), 2);
    }
}
