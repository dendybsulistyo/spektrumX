<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Discount-per-nota with an approval gate: a kasir requests either a
 * percentage (diskon_requested_persen) or a flat Rupiah amount
 * (diskon_requested_nominal) — diskon_tipe records which one — and it only
 * ever affects money once an Admin/Owner/Admin Kasir approves it, at which
 * point diskon_persen or diskon_nominal_tetap (whichever matches the tipe)
 * gets set. Rejecting never touches those approved columns, so a rejected
 * request simply has no financial effect. Either way, diskonNominal()
 * always resolves to a plain Rupiah amount for the rest of the app to use.
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
        if ($this->diskon_tipe === 'nominal') {
            return round((float) $this->diskon_nominal_tetap, 2);
        }

        if (! $this->diskon_persen) {
            return 0.0;
        }

        // Percentage discounts land on odd fractional-Rupiah amounts once
        // the order total itself isn't a round number (e.g. outdoor's
        // area-based pricing) — round to the nearest Rp 100 so the final
        // bill never needs coins smaller than that.
        $raw = (float) $this->total * ((float) $this->diskon_persen / 100);

        return round($raw / 100) * 100;
    }

    public function totalSetelahDiskon(): float
    {
        return round((float) $this->total - $this->diskonNominal(), 2);
    }

    /**
     * Human-readable label for the currently APPROVED discount, e.g. "10%"
     * or "Rp 50.000".
     */
    public function diskonApprovedLabel(): string
    {
        if ($this->diskon_tipe === 'nominal') {
            return 'Rp '.number_format((float) $this->diskon_nominal_tetap, 0, ',', '.');
        }

        return rtrim(rtrim(number_format((float) $this->diskon_persen, 2), '0'), '.').'%';
    }

    /**
     * Same as diskonApprovedLabel() but for the still-pending/rejected
     * request values (diskon_requested_persen / diskon_requested_nominal).
     */
    public function diskonRequestedLabel(): string
    {
        if ($this->diskon_tipe === 'nominal') {
            return 'Rp '.number_format((float) $this->diskon_requested_nominal, 0, ',', '.');
        }

        return rtrim(rtrim(number_format((float) $this->diskon_requested_persen, 2), '0'), '.').'%';
    }
}
