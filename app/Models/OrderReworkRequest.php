<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReworkRequest extends Model
{
    public $timestamps = false;

    /**
     * Stage labels for display — mirrors the labels used on each stage's
     * own menu/page.
     */
    public const STAGE_LABELS = [
        'desain' => 'Layout',
        'cetak' => 'Cetak',
        'finishing' => 'Finishing',
        'qc' => 'Back Office',
        'bungkus' => 'Bungkus',
    ];

    protected $fillable = [
        'order_type',
        'order_id',
        'current_stage',
        'action',
        'target_stage',
        'qty',
        'reason',
        'requested_by',
        'requested_at',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'resolved_at' => 'datetime',
            'qty' => 'integer',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeForOrder($query, string $orderType, int $orderId)
    {
        return $query->where('order_type', $orderType)->where('order_id', $orderId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * All pending requests keyed by "type-id" for quick per-order lookup in
     * stage index views — table stays small (one pending row per order at
     * most), so a single unfiltered query is simpler than per-controller id
     * gathering.
     *
     * @return \Illuminate\Support\Collection<string, self>
     */
    public static function pendingMap(): \Illuminate\Support\Collection
    {
        return static::pending()->with('requestedBy')->get()
            ->keyBy(fn (self $r) => $r->order_type.'-'.$r->order_id);
    }
}
