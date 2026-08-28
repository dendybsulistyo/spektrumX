<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaporanPpnFinal extends Model
{
    protected $table = 'laporan_ppn_final';

    protected $fillable = ['periode', 'tarif_ppn', 'status', 'created_by', 'finalized_at', 'finalized_by'];

    protected function casts(): array
    {
        return ['tarif_ppn' => 'float', 'finalized_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(LaporanPpnFinalItem::class);
    }
}
