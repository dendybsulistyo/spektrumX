<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderOutdoorDetail extends Model
{
    protected $table = 'order_outdoor_detail';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_outdoor_id',
        'BrsOrder',
        'NmFile',
        'file_path',
        'Panjang',
        'Lebar',
        'Qty',
        'KdCtk',
        'ada_finishing',
        'jenis_finishing',
    ];

    protected function casts(): array
    {
        return [
            'Panjang' => 'float',
            'Lebar' => 'float',
            'ada_finishing' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderOutdoor::class, 'order_outdoor_id');
    }

    public function hargaCetak(): BelongsTo
    {
        return $this->belongsTo(HargaCetakOutdoor::class, 'KdCtk', 'KdCtk');
    }

    public function cetakUnits(): HasMany
    {
        return $this->hasMany(OrderOutdoorCetakUnit::class);
    }
}
