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
        DB::statement('ALTER TABLE order_indoor MODIFY NoOrder VARCHAR(20) NOT NULL');
        DB::statement('ALTER TABLE order_indoor_detail MODIFY BrsOrder VARCHAR(25) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE order_indoor MODIFY NoOrder VARCHAR(11) NOT NULL');
        DB::statement('ALTER TABLE order_indoor_detail MODIFY BrsOrder VARCHAR(13) NOT NULL');
    }
};
