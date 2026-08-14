<?php

namespace App\Models;

use App\Traits\HasItemStageProgress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderIndoorDetail extends Model
{
    use HasItemStageProgress;

    protected $table = 'order_indoor_detail';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_indoor_id',
        'BrsOrder',
        'KdProd',
        'jenis_produk',
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
        'KdStat',
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
        return $this->belongsTo(OrderIndoor::class, 'order_indoor_id');
    }

    public function orderTypeSlug(): string
    {
        return 'indoor';
    }

    /**
     * Whether this line's KdProd/pricing should be looked up from
     * harga_artwork (HargaArtwork) instead of produk_indoor (Produk) — see
     * OrderPricingService::totalIndoor() and InvoiceController.
     */
    public function isArtwork(): bool
    {
        return $this->jenis_produk === 'artwork';
    }
}
