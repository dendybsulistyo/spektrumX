<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanPpnFinalItem extends Model
{
    protected $table = 'laporan_ppn_final_items';

    protected $fillable = ['laporan_ppn_final_id', 'order_type', 'order_id', 'tanggal_lunas', 'no_order', 'customer', 'total', 'dpp', 'ppn'];

    protected function casts(): array
    {
        return ['tanggal_lunas' => 'datetime', 'total' => 'float', 'dpp' => 'float', 'ppn' => 'float'];
    }
}
