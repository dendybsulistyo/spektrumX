<?php

namespace App\Models;

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

    protected function casts(): array
    {
        return [
            'HargaStd' => 'float',
            'HargaMin' => 'float',
        ];
    }
}
