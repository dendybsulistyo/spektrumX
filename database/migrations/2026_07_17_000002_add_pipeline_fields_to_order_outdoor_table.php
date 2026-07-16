<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_outdoor', function (Blueprint $table) {
            $table->decimal('total', 12, 2)->nullable()->after('Cetak');

            $table->enum('status_bayar', ['belum_bayar', 'lunas', 'hutang'])->default('lunas')->after('total');
            $table->enum('metode_bayar', ['tunai', 'hutang'])->nullable()->after('status_bayar');
            $table->decimal('jumlah_dibayar', 12, 2)->default(0)->after('metode_bayar');
            $table->decimal('jumlah_piutang', 12, 2)->default(0)->after('jumlah_dibayar');
            $table->foreignId('kasir_user_id')->nullable()->after('jumlah_piutang')->constrained('users')->nullOnDelete();
            $table->timestamp('dibayar_at')->nullable()->after('kasir_user_id');

            $table->enum('status', [
                'baru', 'dibayar', 'desain', 'cetak', 'qc', 'siap_diambil', 'selesai', 'batal',
            ])->default('selesai')->after('dibayar_at');

            $table->foreignId('desain_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('desain_at')->nullable();
            $table->foreignId('cetak_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cetak_at')->nullable();
            $table->foreignId('qc_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('qc_at')->nullable();
            $table->timestamp('diambil_at')->nullable();
        });

        DB::table('order_outdoor')->update([
            'status' => 'selesai',
            'status_bayar' => 'lunas',
        ]);
    }

    public function down(): void
    {
        Schema::table('order_outdoor', function (Blueprint $table) {
            $table->dropForeign(['kasir_user_id']);
            $table->dropForeign(['desain_by']);
            $table->dropForeign(['cetak_by']);
            $table->dropForeign(['qc_by']);

            $table->dropColumn([
                'total',
                'status_bayar',
                'metode_bayar',
                'jumlah_dibayar',
                'jumlah_piutang',
                'kasir_user_id',
                'dibayar_at',
                'status',
                'desain_by',
                'desain_at',
                'cetak_by',
                'cetak_at',
                'qc_by',
                'qc_at',
                'diambil_at',
            ]);
        });
    }
};
