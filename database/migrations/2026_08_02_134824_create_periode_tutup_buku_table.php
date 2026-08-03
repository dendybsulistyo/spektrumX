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
        // Locks a period (YYYY-MM) once month-end closing is done — any
        // Pengeluaran or Payroll write whose date/periode falls inside a
        // closed row here is rejected. Deleting a row here re-opens it.
        Schema::create('periode_tutup_buku', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7)->unique();
            $table->foreignId('ditutup_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamp('ditutup_at');
            $table->string('catatan', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periode_tutup_buku');
    }
};
