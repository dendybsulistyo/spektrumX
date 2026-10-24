<?php

namespace App\Traits;

use App\Models\OrderPayment;
use App\Models\User;
use App\Services\OrderDocumentService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    /**
     * Alasan order tidak boleh dihapus permanen, atau null bila boleh. Hapus
     * hanya untuk order yang masih benar-benar baru (belum diproses, belum
     * dibayar, belum ada dokumen); selebihnya wajib lewat pengajuan pembatalan
     * agar pembayaran & jurnal tidak tertinggal tanpa order.
     */
    public function deleteBlockReason(): ?string
    {
        if ($this->status !== 'baru' || ($this->status_bayar ?? 'belum_bayar') !== 'belum_bayar') {
            return 'Order sudah diproses atau dibayar. Gunakan pengajuan pembatalan.';
        }

        $type = array_search(static::class, OrderDocumentService::MODELS, true);
        if (OrderPayment::where('order_type', $type)->where('order_id', $this->id)->exists()) {
            return 'Order sudah memiliki catatan pembayaran. Gunakan pengajuan pembatalan.';
        }
        if (DB::table('order_documents')->where('order_type', $type)->where('order_id', $this->id)->exists()) {
            return 'Order sudah memiliki dokumen SO/DO/Invoice. Gunakan pengajuan pembatalan.';
        }

        return null;
    }
}
