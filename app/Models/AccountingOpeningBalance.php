<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingOpeningBalance extends Model
{
    protected $table = 'accounting_opening_balances';

    protected $fillable = ['periode', 'NoAkun', 'kode_bantu', 'debet', 'kredit', 'keterangan'];

    protected function casts(): array
    {
        return ['debet' => 'float', 'kredit' => 'float'];
    }
}
