<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE order_outdoor_detail MODIFY NmFile VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE order_outdoor_detail MODIFY NmFile VARCHAR(50) NOT NULL');
    }
};
