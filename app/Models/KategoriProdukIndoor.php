<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriProdukIndoor extends Model
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
}
