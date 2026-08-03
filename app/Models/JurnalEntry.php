<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One debit/credit line in the legacy `am` general ledger table. A single
 * business transaction (a payment, an expense) is always posted as 2+ rows
 * sharing the same NoTrans, where total Debet === total Kredit — see
 * App\Services\AccountingService::post().
 */
class JurnalEntry extends Model
{
    protected $table = 'am';

    public $timestamps = false;

    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = [
        'Perio',
        'NoTrans',
        'TgTrans',
        'Bukti',
        'KetMT',
        'NoAkun',
        'Debet',
        'Kredit',
        'KdBantu',
    ];

    protected function casts(): array
    {
        return [
            'Debet' => 'float',
            'Kredit' => 'float',
        ];
    }
}
