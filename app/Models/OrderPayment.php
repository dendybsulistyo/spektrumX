<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    public $timestamps = false;

    public const JENIS_LABELS = [
        'lunas' => 'Lunas',
        'dp' => 'DP',
        'pelunasan_dp' => 'Pelunasan DP',
        'pelunasan_hutang' => 'Pelunasan Hutang',
        'nota_pengganti' => 'Nota Pengganti',
    ];

    public const CARA_BAYAR_LABELS = [
        'tunai' => 'Tunai',
        'qris' => 'QRIS',
        'transfer' => 'Transfer',
    ];

    protected $fillable = [
        'order_type',
        'order_id',
        'jenis',
        'jumlah',
        'cara_bayar',
        'no_referensi',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForOrder($query, string $orderType, int $orderId)
    {
        return $query->where('order_type', $orderType)->where('order_id', $orderId);
    }
}
