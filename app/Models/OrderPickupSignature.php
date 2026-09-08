<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPickupSignature extends Model
{
    protected $fillable = ['order_type', 'order_id', 'order_detail_id', 'qty', 'nama_penerima', 'kontak_penerima', 'signature_path', 'signature_hash', 'received_by', 'received_at'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
