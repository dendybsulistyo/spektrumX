<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_status_notes', function (Blueprint $table) {
            $table->unsignedInteger('qty')->nullable()->after('order_detail_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_status_notes', function (Blueprint $table) {
            $table->dropColumn('qty');
        });
    }
};
