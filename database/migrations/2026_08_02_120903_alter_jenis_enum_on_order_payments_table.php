<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE order_payments MODIFY jenis ENUM('lunas','dp','pelunasan_dp','pelunasan_hutang','nota_pengganti') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE order_payments MODIFY jenis ENUM('lunas','dp','pelunasan_dp','nota_pengganti') NOT NULL");
    }
};
