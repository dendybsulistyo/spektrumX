<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HargaArtwork extends Model
{
    protected $table = 'harga_artwork';

    /**
     * isPjLb codes, same convention as Produk (produk_indoor): determines how
     * an order line for this product is priced and whether Panjang/Lebar are
     * collected on the order form. Code 4 ("Jasa Potong") uses its own
     * formula via konfigurasi_jasa_potong_artwork, same shape as Indoor's
     * but with an independent X value — see OrderPricingService::lineTotalArtwork().
     */
    public const PJLB_QTY = 1;

    public const PJLB_AREA = 2;

    public const PJLB_QTY_ALT = 4;

    public const PJLB_LABELS = [
        self::PJLB_QTY => 'Qty × Harga',
        self::PJLB_AREA => 'Panjang × Lebar × Qty × Harga',
        self::PJLB_QTY_ALT => 'Jasa Potong (Qty = Harga)',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'KdProd',
        'KdDivs',
        'NmProd',
        'NoUrut',
        'HargaStd',
        'HargaMin',
        'Satuan',
        'isPjLb',
        'isHPilih',
    ];

    /**
     * isPjLb and isHPilih are legacy code columns, not 0/1 booleans — see
     * PJLB_QTY/PJLB_AREA above. isHPilih: 1 = Ya, 2 = Tidak.
     */
    protected function casts(): array
    {
        return [
            'HargaStd' => 'float',
            'HargaMin' => 'float',
            'isPjLb' => 'integer',
            'isHPilih' => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'KdDivs', 'KdDivs');
    }

    /**
     * Whether an order line for this product is priced by area (P × L × Qty)
     * rather than by quantity alone.
     */
    public function isAreaPriced(): bool
    {
        return $this->isPjLb === self::PJLB_AREA;
    }

    /**
     * Whether the order form should collect Panjang/Lebar for this product.
     */
    public function needsDimensionInput(): bool
    {
        return $this->isPjLb === self::PJLB_AREA;
    }

    public function isJasaPotong(): bool
    {
        return $this->isPjLb === self::PJLB_QTY_ALT;
    }
}
