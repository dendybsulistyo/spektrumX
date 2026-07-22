<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Down payment (DP) is an Outdoor-only facility, so only order_outdoor's
     * enums gain the 'dp' value — Indoor/Artwork keep requiring full payment.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE order_outdoor MODIFY status_bayar ENUM('belum_bayar','lunas','hutang','dp') NOT NULL DEFAULT 'lunas'");
        DB::statement("ALTER TABLE order_outdoor MODIFY metode_bayar ENUM('tunai','hutang','dp') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE order_outdoor MODIFY status_bayar ENUM('belum_bayar','lunas','hutang') NOT NULL DEFAULT 'lunas'");
        DB::statement("ALTER TABLE order_outdoor MODIFY metode_bayar ENUM('tunai','hutang') NULL");
    }
};
