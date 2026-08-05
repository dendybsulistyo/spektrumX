<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors konfigurasi_jasa_potong (Indoor) but kept as a separate
     * singleton table since the X value for Artwork's Jasa Potong products
     * is independent of Indoor's.
     */
    public function up(): void
    {
        Schema::create('konfigurasi_jasa_potong_artwork', function (Blueprint $table) {
            $table->id();
            $table->decimal('nilai_x', 12, 2)->default(0);
            $table->timestamps();
        });

        DB::table('konfigurasi_jasa_potong_artwork')->insert([
            'nilai_x' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('order_artwork_detail', function (Blueprint $table) {
            $table->unsignedInteger('PisauTurun')->nullable()->after('Qty');
            $table->unsignedInteger('JumlahKertas')->nullable()->after('PisauTurun');
            $table->unsignedInteger('TebalKertas')->nullable()->after('JumlahKertas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_artwork_detail', function (Blueprint $table) {
            $table->dropColumn(['PisauTurun', 'JumlahKertas', 'TebalKertas']);
        });

        Schema::dropIfExists('konfigurasi_jasa_potong_artwork');
    }
};
