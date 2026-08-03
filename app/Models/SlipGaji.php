<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlipGaji extends Model
{
    protected $table = 'slip_gaji';

    protected $fillable = [
        'user_id',
        'periode',
        'gaji_pokok',
        'tunjangan',
        'potongan',
        'total',
        'status',
        'cara_bayar',
        'no_referensi',
        'dibayar_at',
        'dibayar_oleh',
        'pengeluaran_id',
        'no_trans_jurnal',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok' => 'float',
            'tunjangan' => 'float',
            'potongan' => 'float',
            'total' => 'float',
            'dibayar_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dibayarOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_oleh');
    }
}
