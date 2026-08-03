<?php

namespace App\Models;

use App\Traits\HasDiskonNota;
use App\Traits\HasStageProgress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderOutdoor extends Model
{
    use HasDiskonNota, HasStageProgress;

    protected $table = 'order_outdoor';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'NoOrder',
        'TglOrder',
        'KdCust',
        'KdOpr',
        'created_by',
        'Cetak',
        'total',
        'status_bayar',
        'metode_bayar',
        'cara_bayar',
        'no_referensi',
        'jumlah_dibayar',
        'jumlah_piutang',
        'kasir_user_id',
        'dibayar_at',
        'status',
        'desain_by',
        'desain_at',
        'cetak_by',
        'cetak_at',
        'finishing_by',
        'finishing_at',
        'qc_by',
        'qc_at',
        'bungkus_by',
        'bungkus_at',
        'diambil_at',
        'pengambilan_by',
        'cancel_requested_at',
        'cancel_requested_by',
        'cancel_reason',
        'cancel_approved_at',
        'cancel_approved_by',
        'replacement_order_id',
        'invoice_voided_at',
        'replacement_credit',
        'topup_amount',
        'cashback_amount',
        'diskon_persen',
        'diskon_requested_persen',
        'diskon_alasan',
        'diskon_requested_at',
        'diskon_requested_by',
        'diskon_approved_at',
        'diskon_approved_by',
        'diskon_rejected_at',
        'diskon_rejected_by',
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
            'finishing_at' => 'datetime',
            'qc_at' => 'datetime',
            'bungkus_at' => 'datetime',
            'diambil_at' => 'datetime',
            'cancel_requested_at' => 'datetime',
            'cancel_approved_at' => 'datetime',
            'invoice_voided_at' => 'datetime',
            'replacement_credit' => 'float',
            'topup_amount' => 'float',
            'cashback_amount' => 'float',
            'diskon_persen' => 'float',
            'diskon_requested_persen' => 'float',
            'diskon_requested_at' => 'datetime',
            'diskon_approved_at' => 'datetime',
            'diskon_rejected_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'KdCust', 'KdCust');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderOutdoorDetail::class);
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

    public function finishingBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finishing_by');
    }

    public function qcBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qc_by');
    }

    public function bungkusBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bungkus_by');
    }

    public function pengambilanBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengambilan_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cancelRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancel_requested_by');
    }

    public function cancelApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancel_approved_by');
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replacement_order_id');
    }

    public function replacement(): HasMany
    {
        return $this->hasMany(self::class, 'replacement_order_id');
    }
}
