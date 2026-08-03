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
        // One payslip per employee per period. Generated as 'draft' from
        // gaji_pegawai's standing config (proses gajian), then becomes
        // 'dibayar' once the cashier actually pays it out — that's the
        // moment it creates a pengeluaran row and posts to the ledger.
        Schema::create('slip_gaji', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('periode', 7); // YYYY-MM
            $table->decimal('gaji_pokok', 12, 2);
            $table->decimal('tunjangan', 12, 2)->default(0);
            $table->decimal('potongan', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->enum('status', ['draft', 'dibayar'])->default('draft');
            $table->enum('cara_bayar', ['tunai', 'qris', 'transfer'])->nullable();
            $table->string('no_referensi', 50)->nullable();
            $table->timestamp('dibayar_at')->nullable();
            $table->foreignId('dibayar_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('pengeluaran_id')->nullable();
            $table->string('no_trans_jurnal', 14)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'periode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slip_gaji');
    }
};
