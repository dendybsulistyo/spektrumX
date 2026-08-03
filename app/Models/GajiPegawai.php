<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GajiPegawai extends Model
{
    protected $table = 'gaji_pegawai';

    protected $fillable = [
        'user_id',
        'gaji_pokok',
        'tunjangan',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok' => 'float',
            'tunjangan' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function totalGaji(): float
    {
        return $this->gaji_pokok + $this->tunjangan;
    }
}
