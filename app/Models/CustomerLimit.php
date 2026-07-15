<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerLimit extends Model
{
    protected $table = 'limitp_';

    protected $primaryKey = 'KdCust';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'KdCust',
        'Batas',
        'Total',
    ];

    protected function casts(): array
    {
        return [
            'Batas' => 'float',
            'Total' => 'float',
        ];
    }
}
