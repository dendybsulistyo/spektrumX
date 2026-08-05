<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cancellation request/approval + "nota pengganti" (replacement invoice)
 * workflow, shared by OrderIndoor/OrderOutdoor/OrderArtwork. Approving a
 * cancellation with invoice_voided_at set means the old invoice is voided
 * and waiting for a replacement order (see replaces()/replacement());
 * approved without it means the order is simply cancelled outright, no
 * follow-up needed.
 */
trait HasCancelAndNotaPengganti
{
    public function cancelRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancel_requested_by');
    }

    public function cancelApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancel_approved_by');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(static::class, 'replacement_order_id');
    }

    public function replacement(): HasMany
    {
        return $this->hasMany(static::class, 'replacement_order_id');
    }
}
