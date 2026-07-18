<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HargaBertingkat extends Model
{
    protected $table = 'harga_bertingkat';

    public $timestamps = false;

    public $incrementing = false;

    protected $casts = [
        'BatasA' => 'integer',
        'BatasZ' => 'integer',
        'Harga' => 'float',
    ];
}
