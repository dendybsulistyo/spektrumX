<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_status_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('order_detail_id')->nullable()->after('order_id');
            $table->index(['order_type', 'order_detail_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_notes', function (Blueprint $table) {
            $table->dropIndex(['order_type', 'order_detail_id']);
            $table->dropColumn('order_detail_id');
        });
    }
};
