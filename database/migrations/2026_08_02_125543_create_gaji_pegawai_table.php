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
        // Standing monthly salary config per employee (users.id) —
        // the base that proses gajian starts from each period, editable any
        // time a raise/adjustment happens.
        Schema::create('gaji_pegawai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('gaji_pokok', 12, 2)->default(0);
            $table->decimal('tunjangan', 12, 2)->default(0);
            $table->string('catatan', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaji_pegawai');
    }
};
