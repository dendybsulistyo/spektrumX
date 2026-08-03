<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengeluaran extends Model
{
    public const KATEGORI_LABELS = [
        'bahan_baku' => 'Bahan Baku',
        'gaji' => 'Gaji / Upah',
        'listrik_air' => 'Listrik & Air',
        'sewa' => 'Sewa',
        'transportasi' => 'Transportasi / BBM',
        'perawatan_alat' => 'Perawatan Alat',
        'lain_lain' => 'Lain-lain',
    ];

    public const CARA_BAYAR_LABELS = [
        'tunai' => 'Tunai',
        'qris' => 'QRIS',
        'transfer' => 'Transfer',
    ];

    protected $table = 'pengeluaran';

    protected $fillable = [
        'tanggal',
        'kategori',
        'keterangan',
        'jumlah',
        'cara_bayar',
        'no_referensi',
        'user_id',
        'no_trans_jurnal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kategoriLabel(): string
    {
        return self::KATEGORI_LABELS[$this->kategori] ?? $this->kategori;
    }
}
