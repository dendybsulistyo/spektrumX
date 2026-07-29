<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderOutdoorCetakUnit extends Model
{
    protected $table = 'order_outdoor_cetak_units';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_outdoor_detail_id',
        'unit_index',
        'cetak_by',
        'cetak_at',
    ];

    protected function casts(): array
    {
        return [
            'cetak_at' => 'datetime',
        ];
    }

    public function detail(): BelongsTo
    {
        return $this->belongsTo(OrderOutdoorDetail::class, 'order_outdoor_detail_id');
    }

    public function cetakBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cetak_by');
    }
}
