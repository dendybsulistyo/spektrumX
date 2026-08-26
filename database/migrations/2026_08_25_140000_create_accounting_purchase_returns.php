<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_purchases', function (Blueprint $table) {
            $table->decimal('jumlah_retur', 18, 2)->default(0)->after('jumlah_hutang');
        });
        Schema::create('accounting_purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('accounting_purchases')->restrictOnDelete();
            $table->date('tanggal')->index();
            $table->string('nomor_bukti', 50)->unique();
            $table->enum('jenis', ['retur', 'batal']);
            $table->string('keterangan', 255);
            $table->decimal('dpp', 18, 2);
            $table->decimal('ppn', 18, 2)->default(0);
            $table->decimal('total', 18, 2);
            $table->decimal('offset_hutang', 18, 2)->default(0);
            $table->decimal('refund', 18, 2)->default(0);
            $table->enum('cara_refund', ['tunai', 'qris', 'transfer'])->nullable();
            $table->string('no_referensi', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('no_trans_jurnal', 14)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_purchase_returns');
        Schema::table('accounting_purchases', function (Blueprint $table) {
            $table->dropColumn('jumlah_retur');
        });
    }
};
