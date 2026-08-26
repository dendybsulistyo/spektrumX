<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingSupplier extends Model
{
    protected $table = 'accounting_suppliers';

    protected $fillable = ['kode_bantu', 'nama', 'npwp', 'alamat', 'saldo_awal', 'is_active'];

    protected function casts(): array
    {
        return ['saldo_awal' => 'float', 'is_active' => 'boolean'];
    }
}
