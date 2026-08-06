<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderIndoorDetail extends Model
{
    protected $table = 'order_indoor_detail';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'BrsOrder',
        'KdProd',
        'jenis_produk',
        'NmProd',
        'Judul',
        'Panjang',
        'Lebar',
        'Qty',
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
        ];
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
