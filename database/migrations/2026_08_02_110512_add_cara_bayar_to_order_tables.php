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
        // cara_bayar tracks the actual payment channel (tunai/qris/transfer),
        // separate from metode_bayar which tracks the payment arrangement
        // (tunai=lunas/hutang/dp). Applies whenever money actually changes
        // hands — full payment or DP — not for hutang since nothing is paid
        // yet. no_referensi records the QRIS/transfer reference so the
        // cashier has something to reconcile against later.
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->enum('cara_bayar', ['tunai', 'qris', 'transfer'])->nullable()->after('metode_bayar');
                $table->string('no_referensi', 50)->nullable()->after('cara_bayar');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['cara_bayar', 'no_referensi']);
            });
        }
    }
};
