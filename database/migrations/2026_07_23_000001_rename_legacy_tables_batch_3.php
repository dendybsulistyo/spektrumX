<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Batch 3 of readable-name renames for cryptic legacy table names.
     * Schema::rename is an in-place MySQL RENAME TABLE — data, indexes, and
     * FKs are preserved, nothing is copied or dropped.
     */
    private array $renames = [
        'indm' => 'order_indoor',
        'indd_order_indoor_Seting_SPEK' => 'order_indoor_detail',
        'z_status_transaksi' => 'status_transaksi',
        'hspc_harga_special' => 'harga_khusus_customer',
        'hpilih' => 'harga_bertingkat',
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
