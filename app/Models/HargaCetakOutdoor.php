<?php

namespace App\Models;

use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class HargaCetakOutdoor extends Model
{
    protected $table = 'harga_cetak_outdoor';

    protected $primaryKey = 'KdCtk';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
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
}
