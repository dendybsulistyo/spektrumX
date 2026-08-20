<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Allow Indoor orders to use the same DP payment state as Outdoor. */
    public function up(): void
    {
        DB::statement("ALTER TABLE order_indoor MODIFY status_bayar ENUM('belum_bayar','lunas','hutang','dp') NOT NULL DEFAULT 'lunas'");
        DB::statement("ALTER TABLE order_indoor MODIFY metode_bayar ENUM('tunai','hutang','dp') NULL");
    }

    public function down(): void
    {
        // Do not silently rewrite recorded DP transactions during rollback.
        DB::statement("ALTER TABLE order_indoor MODIFY status_bayar ENUM('belum_bayar','lunas','hutang','dp') NOT NULL DEFAULT 'lunas'");
        DB::statement("ALTER TABLE order_indoor MODIFY metode_bayar ENUM('tunai','hutang','dp') NULL");
    }
};
