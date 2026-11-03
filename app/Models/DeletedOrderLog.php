<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Arsip order yang dihapus (spektrum:hapus-order); hanya dibaca, tidak pernah diubah. */
class DeletedOrderLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_type',
        'order_id',
        'no_order',
        'kd_cust',
        'customer_name',
        'tgl_order',
        'status',
        'status_bayar',
        'total',
        'kredit_dikembalikan',
        'alasan',
        'dihapus_oleh',
        'snapshot',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'tgl_order' => 'date',
            'total' => 'float',
            'kredit_dikembalikan' => 'float',
            'snapshot' => 'array',
            'deleted_at' => 'datetime',
        ];
    }
}
