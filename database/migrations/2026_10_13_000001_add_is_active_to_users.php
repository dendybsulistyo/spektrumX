<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur "Nonaktifkan Akun": akun nonaktif tidak bisa login, riwayat
 * transaksinya tetap utuh. Semua akun yang ada tetap aktif (default true).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role_id');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->unsignedBigInteger('deactivated_by')->nullable()->after('deactivated_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'deactivated_at', 'deactivated_by']);
        });
    }
};
