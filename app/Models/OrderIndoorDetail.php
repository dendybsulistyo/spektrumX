<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderIndoorDetail extends Model
{
    protected $table = 'indd_order_indoor_Seting_SPEK';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'BrsOrder',
        'KdProd',
        'NmProd',
        'Judul',
        'Panjang',
        'Lebar',
        'Qty',
        'KdStat',
    ];

    protected function casts(): array
    {
        return [
            'Panjang' => 'float',
            'Lebar' => 'float',
        ];
    }
}
