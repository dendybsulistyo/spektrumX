<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('am', function (Blueprint $table) {
            $table->index('NoAkun', 'idx_am_no_akun');
        });
    }

    public function down(): void
    {
        Schema::table('am', function (Blueprint $table) {
            $table->dropIndex('idx_am_no_akun');
        });
    }
};
