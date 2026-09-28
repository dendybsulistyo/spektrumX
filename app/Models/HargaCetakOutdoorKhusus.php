<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HargaCetakOutdoorKhusus extends Model
{
    protected $table = 'harga_cetak_outdoor_khusus';

    protected $fillable = [
        'KdCust',
        'KdCtk',
        'HargaStd',
        'HargaMin',
    ];

    protected function casts(): array
    {
        return [
            'HargaStd' => 'integer',
            'HargaMin' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'KdCust', 'KdCust');
    }
}
