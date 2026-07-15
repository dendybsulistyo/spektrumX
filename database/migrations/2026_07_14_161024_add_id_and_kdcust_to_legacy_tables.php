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
        // Tabel legacy ini tidak punya primary key asli (cuma index biasa),
        // jadi kita tambahkan id auto-increment supaya bisa dipakai Eloquent.
        Schema::table('aman_customer_reguler', function (Blueprint $table) {
            $table->id()->first();
            $table->string('KdCust', 6)->nullable()->unique()->after('id');
        });

        Schema::table('z_produk_NamaProduk_INDOOR_CEK', function (Blueprint $table) {
            $table->id()->first();
        });

        Schema::table('indm', function (Blueprint $table) {
            $table->id()->first();
        });

        Schema::table('indd_order_indoor_Seting_SPEK', function (Blueprint $table) {
            $table->id()->first();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aman_customer_reguler', function (Blueprint $table) {
            $table->dropColumn(['id', 'KdCust']);
        });

        Schema::table('z_produk_NamaProduk_INDOOR_CEK', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('indm', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('indd_order_indoor_Seting_SPEK', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
};
