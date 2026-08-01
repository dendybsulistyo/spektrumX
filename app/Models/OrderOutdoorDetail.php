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
        'gabungan',
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

    /**
     * KdCtk is built as KdPrn (2 chars) + NoCetak (2 chars) — see
     * order-outdoor/_form.blade.php's syncKdCtk(). There's no real FK to
     * printers_outdoors, so the printer code has to be sliced back out.
     */
    public function printerCode(): ?string
    {
        return $this->KdCtk ? substr($this->KdCtk, 0, 2) : null;
    }

    public function bahanCode(): ?string
    {
        return $this->KdCtk ? substr($this->KdCtk, 2, 2) : null;
    }
}
