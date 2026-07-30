<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $table = 'kategori_produk_indoor';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'KdDivs',
        'NmDivs',
        'NoUrut',
    ];

    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'KdDivs', 'KdDivs');
    }

    public function produkArtwork(): HasMany
    {
        return $this->hasMany(HargaArtwork::class, 'KdDivs', 'KdDivs');
    }
}
