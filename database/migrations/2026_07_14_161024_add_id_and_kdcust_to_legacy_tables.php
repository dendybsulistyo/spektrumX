<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aman_customer_reguler')) {
            Schema::table('aman_customer_reguler', function (Blueprint $table) {
                $table->id()->first();
                $table->string('KdCust', 6)->nullable()->unique()->after('id');
            });
        }

        if (Schema::hasTable('z_produk_NamaProduk_INDOOR_CEK')) {
            Schema::table('z_produk_NamaProduk_INDOOR_CEK', function (Blueprint $table) {
                $table->id()->first();
            });
        }

        if (Schema::hasTable('indm')) {
            Schema::table('indm', function (Blueprint $table) {
                $table->id()->first();
            });
        }

        if (Schema::hasTable('indd_order_indoor_Seting_SPEK')) {
            Schema::table('indd_order_indoor_Seting_SPEK', function (Blueprint $table) {
                $table->id()->first();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('aman_customer_reguler')) {
            Schema::table('aman_customer_reguler', function (Blueprint $table) {
                $table->dropColumn(['id', 'KdCust']);
            });
        }

        if (Schema::hasTable('z_produk_NamaProduk_INDOOR_CEK')) {
            Schema::table('z_produk_NamaProduk_INDOOR_CEK', function (Blueprint $table) {
                $table->dropColumn('id');
            });
        }

        if (Schema::hasTable('indm')) {
            Schema::table('indm', function (Blueprint $table) {
                $table->dropColumn('id');
            });
        }

        if (Schema::hasTable('indd_order_indoor_Seting_SPEK')) {
            Schema::table('indd_order_indoor_Seting_SPEK', function (Blueprint $table) {
                $table->dropColumn('id');
            });
        }
    }
};