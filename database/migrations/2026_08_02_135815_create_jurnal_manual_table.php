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
        // Header/audit row for a manual adjusting entry posted into `am` —
        // the actual debit/credit lines live in `am` under no_trans_jurnal,
        // this just tracks who posted it and whether it's been reversed.
        Schema::create('jurnal_manual', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('keterangan', 255);
            $table->string('no_trans_jurnal', 14)->nullable();
            $table->enum('status', ['posted', 'dibatalkan'])->default('posted');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_manual');
    }
};
