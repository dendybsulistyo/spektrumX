<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanKeuangan extends Model
{
    protected $table = 'pengaturan_keuangan';

    protected $fillable = [
        'nama_perusahaan',
        'alamat_perusahaan',
        'npwp_perusahaan',
        'is_pkp',
        'auto_print_sales_order',
        'tarif_ppn_default',
        'nomor_seri_faktur_terakhir',
    ];

    protected function casts(): array
    {
        return [
            'is_pkp' => 'boolean',
            'auto_print_sales_order' => 'boolean',
            'tarif_ppn_default' => 'float',
        ];
    }

    /**
     * Singleton settings row — same pattern as KonfigurasiJasaPotong.
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate([], ['tarif_ppn_default' => 11.00]);
    }
}
