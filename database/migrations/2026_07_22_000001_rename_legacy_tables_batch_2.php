<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 2 of readable-name renames for cryptic legacy table names.
     * Schema::rename is an in-place MySQL RENAME TABLE — data, indexes, and
     * FKs are preserved, nothing is copied or dropped.
     */
    private array $renames = [
        'hcetak_outdoor' => 'harga_cetak_outdoor',
        'gd_Master_Barang_Outdoor' => 'bahan_outdoor',
        'aman_gd_grup_Bahan' => 'kategori_bahan_outdoor',
        'limitp_' => 'customer_limits',
        'aman_bahan_1_NamaBahan_dan_Nomor_Urut' => 'bahan_cetak_outdoor',
    ];

    public function up(): void
    {
        foreach ($this->renames as $from => $to) {
            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->renames as $from => $to) {
            if (Schema::hasTable($to) && ! Schema::hasTable($from)) {
                Schema::rename($to, $from);
            }
        }
    }
};
