<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusNote extends Model
{
    protected $table = 'order_status_notes';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_type',
        'order_id',
        'order_detail_id',
        'qty',
        'stage',
        'action',
        'catatan',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'qty' => 'integer',
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
