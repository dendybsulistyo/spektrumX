<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends Model
{
    protected $table = 'produk_indoor';

    public $timestamps = false;

    /**
     * isPjLb codes — determine how an order line for this product is priced
     * and whether Panjang/Lebar are collected on the order form.
     */
    public const PJLB_QTY = 1;

    public const PJLB_AREA = 2;

    public const PJLB_DIMENSION_ONLY = 3;

    public const PJLB_QTY_ALT = 4;

    public const PJLB_LABELS = [
        self::PJLB_QTY => 'Qty × Harga',
        self::PJLB_AREA => 'Panjang × Lebar × Qty × Harga',
        self::PJLB_DIMENSION_ONLY => 'Qty × Harga (Panjang/Lebar dicatat, tidak memengaruhi harga)',
        self::PJLB_QTY_ALT => 'Qty × Harga',
    ];

    /**
     * Inline styles (not Tailwind classes) for the compact isPjLb badge —
     * the pre-built Vite CSS bundle doesn't include color utilities that
     * weren't already used elsewhere when it was compiled, so bg-orange-*
     * and bg-purple-100 silently render unstyled. Inline style sidesteps that.
     */
    public const PJLB_BADGE_STYLES = [
        self::PJLB_QTY => 'background-color:#f3f4f6;color:#4b5563',
        self::PJLB_AREA => 'background-color:#ffedd5;color:#c2410c',
        self::PJLB_DIMENSION_ONLY => 'background-color:#f3e8ff;color:#7e22ce',
        self::PJLB_QTY_ALT => 'background-color:#f3f4f6;color:#4b5563',
    ];

    /** Badge text shown in tables — code 2 gets a descriptive "Pj x Lb" tag since it drives pricing. */
    public const PJLB_BADGE_TEXT = [
        self::PJLB_QTY => 'Tidak',
        self::PJLB_AREA => 'Pj x Lb',
        self::PJLB_DIMENSION_ONLY => 'Cek',
        self::PJLB_QTY_ALT => 'Qty = Harga',
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
     * isPjLb and isHPilih are legacy code columns, not 0/1 booleans:
     * isPjLb: 1 = Qty×Harga, 2 = P×L×Qty×Harga, 3 = P×L dicatat saja
     *         (tidak memengaruhi harga), 4 = Qty×Harga.
     * isHPilih: 1 = Ya (pakai harga bertingkat), 2 = Tidak.
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
     * Whether the order form should collect Panjang/Lebar for this product
     * (codes 2 and 3 — code 3 records dimensions without using them in price).
     */
    public function needsDimensionInput(): bool
    {
        return in_array($this->isPjLb, [self::PJLB_AREA, self::PJLB_DIMENSION_ONLY], true);
    }

    public function pjLbLabel(): string
    {
        return self::PJLB_LABELS[$this->isPjLb] ?? '-';
    }

    public function pjLbBadgeStyle(): string
    {
        return self::PJLB_BADGE_STYLES[$this->isPjLb] ?? 'background-color:#f3f4f6;color:#9ca3af';
    }

    public function pjLbBadgeText(): string
    {
        return self::PJLB_BADGE_TEXT[$this->isPjLb] ?? (string) $this->isPjLb;
    }
}
