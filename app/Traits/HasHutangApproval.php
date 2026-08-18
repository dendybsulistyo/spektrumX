<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A VIP customer (one with a customer_limits row) can hutang freely as long
 * as this order's amount still fits their plafon (Batas) given their running
 * balance (Total) — that case commits immediately, same as tunai/DP. Once it
 * would push them past the plafon, it's held here for Admin/Admin Kasir
 * sign-off instead of being rejected outright — see
 * KasirController::bayar()/approveHutang()/rejectHutang().
 */
trait HasHutangApproval
{
    public function hutangRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hutang_requested_by');
    }

    public function hutangApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hutang_approved_by');
    }

    public function hutangRejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hutang_rejected_by');
    }

    public function hutangApprovalStatus(): string
    {
        if ($this->hutang_approved_at) {
            return 'approved';
        }

        if ($this->hutang_rejected_at) {
            return 'rejected';
        }

        if ($this->hutang_requested_at) {
            return 'pending';
        }

        return 'none';
    }

    /**
     * The Rupiah amount that would actually be financed as hutang — same
     * figure bayar() charges for any other payment method.
     */
    public function hutangAmount(): float
    {
        return $this->diskonStatus() === 'approved' ? $this->totalSetelahDiskon() : (float) $this->total;
    }

    /**
     * Null when the customer isn't VIP (hutang isn't offered at all — see
     * Customer::isVip). True/false otherwise: whether this order's amount
     * still fits within the customer's plafon.
     */
    public function withinHutangPlafon(): ?bool
    {
        if (! $this->customer?->isVip) {
            return null;
        }

        return ($this->customer->limit->Total + $this->hutangAmount()) <= $this->customer->limit->Batas;
    }
}
