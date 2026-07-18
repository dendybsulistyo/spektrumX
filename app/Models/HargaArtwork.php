<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HargaArtwork extends Model
{
    protected $table = 'harga_artwork';

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

    /**
     * isHPilih is a legacy code column: 1 = Ya, 2 = Tidak (not a 0/1 boolean).
     */
    protected function casts(): array
    {
        return [
            'HargaStd' => 'float',
            'HargaMin' => 'float',
            'isPjLb' => 'boolean',
            'isHPilih' => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'KdDivs', 'KdDivs');
    }
}
