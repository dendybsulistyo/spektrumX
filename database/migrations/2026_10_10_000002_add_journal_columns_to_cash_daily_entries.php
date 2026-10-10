<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_daily_entries', function (Blueprint $table) {
            // Khusus penyesuaian kas: jenis, akun lawan, dan nomor jurnalnya.
            $table->string('adjustment_type', 20)->nullable()->after('source_key');
            $table->string('counter_account', 10)->nullable()->after('adjustment_type');
            $table->string('journal_number', 20)->nullable()->after('counter_account');
        });
    }

    public function down(): void
    {
        Schema::table('cash_daily_entries', function (Blueprint $table) {
            $table->dropColumn(['adjustment_type', 'counter_account', 'journal_number']);
        });
    }
};
