<?php

namespace App\Models;

use App\Traits\HasItemStageProgress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderArtworkDetail extends Model
{
    use HasItemStageProgress;

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
        'qty_desain',
        'qty_cetak',
        'qty_finishing',
        'qty_qc',
        'qty_bungkus',
        'qty_siap_diambil',
        'qty_selesai',
        'stage_entered_at',
        'PisauTurun',
        'JumlahKertas',
        'TebalKertas',
    ];

    protected function casts(): array
    {
        return [
            'Panjang' => 'float',
            'Lebar' => 'float',
            'PisauTurun' => 'integer',
            'JumlahKertas' => 'integer',
            'TebalKertas' => 'integer',
            'qty_desain' => 'integer',
            'qty_cetak' => 'integer',
            'qty_finishing' => 'integer',
            'qty_qc' => 'integer',
            'qty_bungkus' => 'integer',
            'qty_siap_diambil' => 'integer',
            'qty_selesai' => 'integer',
            'stage_entered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderArtwork::class, 'order_artwork_id');
    }

    public function orderTypeSlug(): string
    {
        return 'artwork';
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(HargaArtwork::class, 'KdProd', 'KdProd');
    }
}
