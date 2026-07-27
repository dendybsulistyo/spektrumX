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
        Schema::table('order_indoor', function (Blueprint $table) {
            $table->index(['KdCust', 'TglOrder'], 'order_indoor_kdcust_tglorder_index');
        });

        Schema::table('order_outdoor', function (Blueprint $table) {
            $table->index(['KdCust', 'TglOrder'], 'order_outdoor_kdcust_tglorder_index');
        });

        Schema::table('order_artwork', function (Blueprint $table) {
            $table->index(['KdCust', 'TglOrder'], 'order_artwork_kdcust_tglorder_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_indoor', function (Blueprint $table) {
            $table->dropIndex('order_indoor_kdcust_tglorder_index');
        });

        Schema::table('order_outdoor', function (Blueprint $table) {
            $table->dropIndex('order_outdoor_kdcust_tglorder_index');
        });

        Schema::table('order_artwork', function (Blueprint $table) {
            $table->dropIndex('order_artwork_kdcust_tglorder_index');
        });
    }
};
