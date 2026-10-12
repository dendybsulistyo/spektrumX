<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menghubungkan Lembar Kerja CS dengan order yang dibuat darinya, agar
 * riwayat lembar kerja bisa menampilkan nomor order. Kolom kosong untuk
 * lembar kerja lama; rollback cukup menghapus kedua kolom.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_service_job_sheets', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('claimed_order_type');
            $table->string('order_number', 30)->nullable()->after('order_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('customer_service_job_sheets', function (Blueprint $table) {
            $table->dropIndex(['order_number']);
            $table->dropColumn(['order_id', 'order_number']);
        });
    }
};
