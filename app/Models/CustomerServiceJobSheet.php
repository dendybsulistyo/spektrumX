<?php

namespace App\Models;

use App\Support\HariKerja;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CustomerServiceJobSheet extends Model
{
    public const INDOOR_FOLDER_LABELS = [
        'sublime' => 'Sublime',
        'artwork' => 'Artwork',
        'pod' => 'POD',
    ];

    protected $fillable = [
        'customer_code',
        'customer_name',
        'pc',
        'folder_file',
        'received_at',
        'deadline',
        'progress_hari',
        'opf',
        'notes',
        'is_urgent',
        'order_type',
        'indoor_folder',
        'items',
        'created_by',
        'claimed_by',
        'claimed_at',
        'claimed_order_type',
        'order_id',
        'order_number',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'deadline' => 'date',
            'progress_hari' => 'integer',
            'is_urgent' => 'boolean',
            'items' => 'array',
            'claimed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'KdCust');
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function indoorFolderLabel(): ?string
    {
        return self::INDOOR_FOLDER_LABELS[$this->indoor_folder] ?? null;
    }

    public function estimateEndsAt(): ?Carbon
    {
        if (! $this->created_at || ! $this->progress_hari) {
            return null;
        }

        return HariKerja::tambah($this->created_at, (int) $this->progress_hari);
    }

    /** Tautan order ke lembar kerja baru tercatat sejak tanggal ini; lembar yang lebih lama tidak bisa dinilai. */
    public const ORDER_LINK_SINCE = '2026-10-12';

    /** Sudah diambil operator tetapi ordernya belum tersimpan (tab tertutup, refresh, error, dsb.). */
    public function scopeStuck($query)
    {
        return $query->whereNotNull('claimed_at')
            ->whereNull('order_id')
            ->where('claimed_at', '>=', self::ORDER_LINK_SINCE);
    }

    public function isStuck(): bool
    {
        return $this->claimed_at !== null && $this->order_id === null
            && $this->claimed_at->gte(self::ORDER_LINK_SINCE);
    }

    /** Operator pengambil, atau admin (roles.manage), boleh melanjutkan/mengembalikan lembar yang tersangkut. */
    public function canBeHandledBy(User $user): bool
    {
        return (int) $this->claimed_by === (int) $user->id || $user->hasPermission('roles.manage');
    }
}
