<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu pengajuan Diskon / Hutang VIP beserta keputusannya (riwayat approval). */
class ApprovalRequest extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public static function open(string $kind, string $orderType, int $orderId, ?string $detail, ?string $reason): void
    {
        self::create([
            'kind' => $kind, 'order_type' => $orderType, 'order_id' => $orderId,
            'detail' => $detail, 'reason' => $reason,
            'requested_by' => auth()->id(), 'requested_at' => now(),
        ]);
    }

    public static function decide(string $kind, string $orderType, int $orderId, bool $approved): void
    {
        self::query()->where('kind', $kind)->where('order_type', $orderType)->where('order_id', $orderId)
            ->where('status', 'pending')
            ->update(['status' => $approved ? 'approved' : 'rejected', 'decided_by' => auth()->id(), 'decided_at' => now()]);
    }
}
