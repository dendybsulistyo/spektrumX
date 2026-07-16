<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends Model
{
    protected $table = 'z_produk_NamaProduk_INDOOR_CEK';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'KdProd',
        'KdDivs',
        'NmProd',
        'NoUrut',
        'HargaStd',
        'HargaMin',
        'Satuan',
        'isPjLb',
        'isHPilih',
    ];

    protected function casts(): array
    {
        return [
            'HargaStd' => 'float',
            'HargaMin' => 'float',
            'isPjLb' => 'boolean',
            'isHPilih' => 'boolean',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'KdDivs', 'KdDivs');
    }
}
