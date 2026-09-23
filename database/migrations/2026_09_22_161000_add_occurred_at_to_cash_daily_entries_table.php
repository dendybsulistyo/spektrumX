<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_daily_entries', function (Blueprint $table) {
            $table->dateTime('occurred_at')->nullable()->after('tanggal')->index();
        });
    }

    public function down(): void
    {
        Schema::table('cash_daily_entries', function (Blueprint $table) {
            $table->dropIndex(['occurred_at']);
            $table->dropColumn('occurred_at');
        });
    }
};
