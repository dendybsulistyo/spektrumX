<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_purchases', function (Blueprint $table) {
            $table->unsignedSmallInteger('termin_hari')->nullable()->after('no_referensi');
            $table->date('tanggal_terima_invoice')->nullable()->after('termin_hari');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_purchases', function (Blueprint $table) {
            $table->dropColumn(['termin_hari', 'tanggal_terima_invoice']);
        });
    }
};
