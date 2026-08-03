<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodeTutupBuku extends Model
{
    protected $table = 'periode_tutup_buku';

    protected $fillable = [
        'periode',
        'ditutup_oleh',
        'ditutup_at',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'ditutup_at' => 'datetime',
        ];
    }

    public function ditutupOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditutup_oleh');
    }

    /**
     * Accepts either a 'YYYY-MM-DD' date or a 'YYYY-MM' periode string.
     */
    public static function isClosed(?string $tanggalOrPeriode): bool
    {
        if (! $tanggalOrPeriode) {
            return false;
        }

        return self::query()->where('periode', substr($tanggalOrPeriode, 0, 7))->exists();
    }
}
