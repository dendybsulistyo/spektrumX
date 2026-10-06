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
        'harga_satuan_kasir',
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
            'harga_satuan_kasir' => 'float',
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

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'KdProd', 'KdProd');
    }

    public function produkArtwork(): BelongsTo
    {
        return $this->belongsTo(HargaArtwork::class, 'KdProd', 'KdProd');
    }

    /**
     * Nama divisi untuk tampilan antrean "By Divisi". Item artwork diambil
     * dari master Artwork; data lama yang tercatat 'indoor' tapi kodenya
     * hanya ada di master Artwork ikut jatuh ke master Artwork.
     */
    public function divisionName(): ?string
    {
        $produk = $this->isArtwork()
            ? $this->produkArtwork
            : ($this->produk ?? $this->produkArtwork);

        if ($produk?->kategori?->NmDivs) {
            return $produk->kategori->NmDivs;
        }

        // Produk artwork yang divisinya tak terbaca dikumpulkan di "Artwork";
        // selain itu tetap null agar tampil sebagai "Tanpa Divisi".
        return $this->isArtwork() || $this->produkArtwork ? 'Artwork' : null;
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
