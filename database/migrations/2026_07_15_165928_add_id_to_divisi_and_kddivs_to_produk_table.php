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
        Schema::table('aman_divisi_Master_Produk_Indoor', function (Blueprint $table) {
            $table->id()->first();
        });

        Schema::table('z_produk_NamaProduk_INDOOR_CEK', function (Blueprint $table) {
            $table->string('KdDivs', 2)->nullable()->after('KdProd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aman_divisi_Master_Produk_Indoor', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('z_produk_NamaProduk_INDOOR_CEK', function (Blueprint $table) {
            $table->dropColumn('KdDivs');
        });
    }
};
