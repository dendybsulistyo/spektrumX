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
        Schema::table('aman_gd_grup_Bahan', function (Blueprint $table) {
            $table->id()->first();
        });

        Schema::table('gd_Master_Barang_Outdoor', function (Blueprint $table) {
            $table->id()->first();
            $table->string('KdGrup', 3)->nullable()->after('KdBrgs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aman_gd_grup_Bahan', function (Blueprint $table) {
            $table->dropColumn('id');
        });

        Schema::table('gd_Master_Barang_Outdoor', function (Blueprint $table) {
            $table->dropColumn(['id', 'KdGrup']);
        });
    }
};
