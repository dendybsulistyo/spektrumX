<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aman_bahan_1_NamaBahan_dan_Nomor_Urut', function (Blueprint $table) {
            $table->string('NoCetak', 2)->nullable()->after('NoUrut');
        });

        // Duplicate NoUrut into NoCetak as a zero-padded 2-digit string.
        DB::statement("UPDATE `aman_bahan_1_NamaBahan_dan_Nomor_Urut` SET NoCetak = LPAD(NoUrut, 2, '0')");
    }

    public function down(): void
    {
        Schema::table('aman_bahan_1_NamaBahan_dan_Nomor_Urut', function (Blueprint $table) {
            $table->dropColumn('NoCetak');
        });
    }
};
