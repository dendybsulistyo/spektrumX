<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'qty_diproses',
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
            'qty_diproses' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderOutdoor::class, 'order_outdoor_id');
    }

    public function sisaQty(): int
    {
        return max(0, (int) $this->Qty - (int) $this->qty_diproses);
    }

    public function isSelesai(): bool
    {
        return $this->sisaQty() === 0;
    }

    public function hargaCetak(): BelongsTo
    {
        return $this->belongsTo(HargaCetakOutdoor::class, 'KdCtk', 'KdCtk');
    }
}
