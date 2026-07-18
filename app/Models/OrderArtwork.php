<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderArtwork extends Model
{
    protected $table = 'order_artwork';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'NoOrder',
        'TglOrder',
        'KdCust',
        'Cetak',
        'total',
        'status_bayar',
        'metode_bayar',
        'jumlah_dibayar',
        'jumlah_piutang',
        'kasir_user_id',
        'dibayar_at',
        'status',
        'desain_by',
        'desain_at',
        'cetak_by',
        'cetak_at',
        'qc_by',
        'qc_at',
        'diambil_at',
    ];

    protected function casts(): array
    {
        return [
            'TglOrder' => 'date',
            'Cetak' => 'boolean',
            'total' => 'float',
            'jumlah_dibayar' => 'float',
            'jumlah_piutang' => 'float',
            'dibayar_at' => 'datetime',
            'desain_at' => 'datetime',
            'cetak_at' => 'datetime',
            'qc_at' => 'datetime',
            'diambil_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'KdCust', 'KdCust');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderArtworkDetail::class);
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_user_id');
    }

    public function desainBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'desain_by');
    }

    public function cetakBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cetak_by');
    }

    public function qcBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qc_by');
    }
}
