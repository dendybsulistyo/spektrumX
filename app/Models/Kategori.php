<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $table = 'aman_divisi_Master_Produk_Indoor';

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
}
