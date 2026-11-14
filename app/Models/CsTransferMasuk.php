<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transfer masuk ke CS yang belum jelas peruntukannya; ditandai "sudah dipakai"
 * (used_at) setelah diketahui untuk order mana.
 */
class CsTransferMasuk extends Model
{
    protected $table = 'cs_transfer_masuk';

    protected $fillable = [
        'customer_code',
        'customer_name',
        'amount',
        'transfer_date',
        'notes',
        'created_by',
        'used_at',
        'used_order_number',
        'used_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transfer_date' => 'date',
            'used_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeBelumDipakai(Builder $query): Builder
    {
        return $query->whereNull('used_at');
    }
}
