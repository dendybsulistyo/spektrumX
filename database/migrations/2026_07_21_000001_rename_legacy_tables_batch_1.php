<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 1 of readable-name renames for cryptic legacy table names.
     * Schema::rename is an in-place MySQL RENAME TABLE — data, indexes, and
     * FKs are preserved, nothing is copied or dropped.
     */
    private array $renames = [
        'aman_customer_reguler' => 'customers',
        'aman_editor' => 'operators',
        'aman_printer' => 'printers',
        'aman_divisi_Master_Produk_Indoor' => 'kategori_produk_indoor',
        'z_produk_NamaProduk_INDOOR_CEK' => 'produk_indoor',
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
