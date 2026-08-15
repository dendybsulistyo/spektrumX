<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_rework_requests', function (Blueprint $table) {
            // Nullable: only meaningful for action='ulang'. Null also covers
            // any already-pending row created before this column existed —
            // approve() treats null as "move everything", matching the
            // previous whole-order behavior for those.
            $table->unsignedInteger('qty')->nullable()->after('target_stage');
        });
    }

    public function down(): void
    {
        Schema::table('order_rework_requests', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
