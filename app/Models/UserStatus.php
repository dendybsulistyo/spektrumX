<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Status teks ala WhatsApp: tampil ke semua user selama 24 jam. */
class UserStatus extends Model
{
    public const LIFETIME_HOURS = 24;

    /** Pilihan warna latar (selaras tema Ruang Cetak). */
    public const BACKGROUNDS = ['#1b2236', '#0f8fb3', '#c8246c', '#2e8b57', '#b07d00', '#5b4bb7'];

    protected $fillable = ['user_id', 'body', 'background', 'mentions', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'mentions' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(UserStatusView::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(UserStatusResponse::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }
}
