<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Operator selection is being removed from the Order Indoor form —
     * KdOpr is no longer collected for new orders, so it can no longer be
     * required at the DB level. Existing historical values are untouched.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `order_indoor` MODIFY `KdOpr` VARCHAR(4) NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `order_indoor` MODIFY `KdOpr` VARCHAR(4) NOT NULL DEFAULT ''");
    }
};
