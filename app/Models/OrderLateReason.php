<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/** Alasan order telat yang ditulis operator; hanya ditambah, tidak diubah. */
class OrderLateReason extends Model
{
    protected $fillable = ['order_type', 'order_id', 'alasan', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForOrder(Builder $query, string $type, int $id): Builder
    {
        return $query->where('order_type', $type)->where('order_id', $id);
    }

    /** @return array{alasan: string, user: string, created_at: string|null} */
    public function toDisplay(): array
    {
        return [
            'alasan' => $this->alasan,
            'user' => $this->user?->name ?? '-',
            'created_at' => $this->created_at?->format('d M Y H:i'),
        ];
    }

    /**
     * Alasan untuk banyak order sekaligus, dikelompokkan "tipe-id", terbaru dulu.
     *
     * @param  array<string, array<int, int>>  $idsByType
     * @return Collection<string, Collection<int, self>>
     */
    public static function groupedFor(array $idsByType): Collection
    {
        $idsByType = array_filter($idsByType);
        if (! $idsByType) {
            return collect();
        }

        return self::query()->with('user:id,name')
            ->where(function (Builder $query) use ($idsByType) {
                foreach ($idsByType as $type => $ids) {
                    $query->orWhere(fn (Builder $scope) => $scope->where('order_type', $type)->whereIn('order_id', $ids));
                }
            })
            ->latest()->orderByDesc('id')->get()
            ->groupBy(fn (self $reason) => $reason->order_type.'-'.$reason->order_id);
    }
}
