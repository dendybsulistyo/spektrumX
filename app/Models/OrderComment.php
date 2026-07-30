<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class OrderComment extends Model
{
    protected $table = 'order_comments';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_type',
        'order_id',
        'user_id',
        'pesan',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
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

    /**
     * Count of unread messages (posted by someone else, after the current
     * user's last visit to that order's thread) per order id.
     *
     * @return Collection<int, int>
     */
    public static function unreadCountsFor(string $orderType, iterable $orderIds): Collection
    {
        $orderIds = collect($orderIds)->values();

        if ($orderIds->isEmpty()) {
            return collect();
        }

        $userId = auth()->id();

        $lastReadByOrder = OrderCommentRead::where('user_id', $userId)
            ->where('order_type', $orderType)
            ->whereIn('order_id', $orderIds)
            ->pluck('last_read_at', 'order_id');

        return static::where('order_type', $orderType)
            ->whereIn('order_id', $orderIds)
            ->where('user_id', '!=', $userId)
            ->get()
            ->groupBy('order_id')
            ->map(function (Collection $comments, int $orderId) use ($lastReadByOrder) {
                $lastRead = $lastReadByOrder->get($orderId);

                return $comments->filter(fn (self $c) => ! $lastRead || $c->created_at->gt($lastRead))->count();
            });
    }

    /**
     * Ids of orders (of the given type) that have at least one message the
     * current user hasn't read yet — regardless of the order's current
     * status. Used to resurface an order in an operator's queue when
     * someone else adds a reply after it has already moved past their stage.
     *
     * @return Collection<int, int>
     */
    public static function orderIdsWithUnreadFor(string $orderType): Collection
    {
        $userId = auth()->id();

        $lastReadByOrder = OrderCommentRead::where('user_id', $userId)
            ->where('order_type', $orderType)
            ->pluck('last_read_at', 'order_id');

        return static::where('order_type', $orderType)
            ->where('user_id', '!=', $userId)
            ->get()
            ->groupBy('order_id')
            ->filter(function (Collection $comments, int $orderId) use ($lastReadByOrder) {
                $lastRead = $lastReadByOrder->get($orderId);

                return $comments->contains(fn (self $c) => ! $lastRead || $c->created_at->gt($lastRead));
            })
            ->keys();
    }
}
