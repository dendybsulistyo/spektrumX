<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderArtworkDetail extends Model
{
    protected $table = 'order_artwork_detail';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_artwork_id',
        'BrsOrder',
        'KdProd',
        'NmProd',
        'Judul',
        'Panjang',
        'Lebar',
        'Qty',
    ];

    protected function casts(): array
    {
        return [
            'Panjang' => 'float',
            'Lebar' => 'float',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderArtwork::class, 'order_artwork_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(HargaArtwork::class, 'KdProd', 'KdProd');
    }
}
