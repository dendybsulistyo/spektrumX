<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDailyEntry extends Model
{
    protected $fillable = [
        'tanggal',
        'occurred_at',
        'user_id',
        'source_key',
        'no_nota',
        'keterangan',
        'debet',
        'kredit',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'occurred_at' => 'datetime',
            'debet' => 'float',
            'kredit' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
