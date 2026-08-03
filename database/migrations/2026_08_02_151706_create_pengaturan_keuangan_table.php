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
        // Singleton settings row (one record, like konfigurasi_jasa_potong)
        // for company-wide tax/invoice config used across Keuangan reports.
        Schema::create('pengaturan_keuangan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan', 100)->nullable();
            $table->string('alamat_perusahaan', 255)->nullable();
            $table->string('npwp_perusahaan', 20)->nullable();
            $table->boolean('is_pkp')->default(false);
            $table->decimal('tarif_ppn_default', 5, 2)->default(11.00);
            $table->string('nomor_seri_faktur_terakhir', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_keuangan');
    }
};
