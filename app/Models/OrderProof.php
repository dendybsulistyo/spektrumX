<?php

namespace App\Models;

use App\Services\OrderDocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Order proof: contoh kecil tanpa tagihan. Alur: layout → selesai (operator Layout) →
 * jadi_order (dibuatkan satu order reguler) atau ditutup (customer tidak jadi).
 */
class OrderProof extends Model
{
    public const TYPE_LABELS = ['outdoor' => 'Outdoor', 'indoor' => 'Indoor', 'artwork' => 'Artwork'];

    /** Kode di nomor proof, mengikuti kode order reguler (OUT.1 / IND.2 / ART.3). */
    public const TYPE_CODES = ['outdoor' => 1, 'indoor' => 2, 'artwork' => 3];

    public const STATUS_LABELS = [
        'layout' => 'Di Layout',
        'selesai' => 'Proof selesai',
        'jadi_order' => 'Sudah jadi order',
        'ditutup' => 'Ditutup',
    ];

    protected $fillable = [
        'NoOrder', 'order_type', 'KdCust', 'catatan', 'status', 'created_by',
        'layout_done_at', 'layout_done_by',
        'converted_order_type', 'converted_order_id', 'converted_at', 'converted_by',
        'closed_at', 'closed_by', 'close_reason',
    ];

    protected function casts(): array
    {
        return [
            'layout_done_at' => 'datetime',
            'converted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderProofItem::class)->orderBy('id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'KdCust', 'KdCust');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function layoutDoneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'layout_done_by');
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** Order reguler hasil proof ini (indoor & artwork dibuat lewat form Order Indoor). */
    public function convertedOrder(): ?Model
    {
        $model = OrderDocumentService::MODELS[$this->converted_order_type] ?? null;

        return $model && $this->converted_order_id ? $model::query()->find($this->converted_order_id) : null;
    }

    /** Form order reguler yang dipakai untuk membuat order dari proof ini. */
    public function orderFormType(): string
    {
        return $this->order_type === 'outdoor' ? 'outdoor' : 'indoor';
    }

    public static function fromOrder(string $type, int $id): ?self
    {
        return self::query()->where('converted_order_type', $type)->where('converted_order_id', $id)->first();
    }

    /**
     * Pilihan produk/bahan per tipe proof: outdoor = KdCtk (printer · bahan), indoor/artwork = KdProd.
     *
     * @return array{outdoor: Collection<string, string>, indoor: Collection<string, string>, artwork: Collection<string, string>}
     */
    public static function itemOptions(): array
    {
        $printers = PrinterOutdoor::orderBy('NoUrut')->pluck('NmPrn', 'KdPrn');
        $bahan = BahanCetakOutdoor::orderBy('NoUrut')->pluck('NmBhn', 'NoCetak');

        return [
            'outdoor' => HargaCetakOutdoor::orderBy('KdCtk')->pluck('KdCtk')
                ->mapWithKeys(fn ($code) => [$code => ($printers[substr($code, 0, 2)] ?? substr($code, 0, 2)).' · '.($bahan[substr($code, 2, 2)] ?? substr($code, 2, 2))])
                ->sort(),
            'indoor' => Produk::orderBy('NoUrut')->get(['KdProd', 'NmProd'])->mapWithKeys(fn ($produk) => [$produk->KdProd => $produk->NmProd]),
            'artwork' => HargaArtwork::orderBy('NoUrut')->get(['KdProd', 'NmProd'])->mapWithKeys(fn ($produk) => [$produk->KdProd => $produk->NmProd]),
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->order_type] ?? ucfirst($this->order_type);
    }
}
