<?php

namespace App\Models;

use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Casts\Attribute;
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

    protected function hargaStd(): Attribute
    {
        return Attribute::get(fn ($value) => Rupiah::hargaOutdoor($value));
    }

    protected function hargaMin(): Attribute
    {
        return Attribute::get(fn ($value) => Rupiah::hargaOutdoor($value));
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'KdCust', 'KdCust');
    }
}
