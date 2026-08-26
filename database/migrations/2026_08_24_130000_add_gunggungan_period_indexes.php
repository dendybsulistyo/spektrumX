<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['order_indoor', 'order_outdoor'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                // Gunggungan filters a month's paid orders by these two fields.
                $table->index(['TglOrder', 'status_bayar'], 'gunggungan_period_payment_index');
            });
        }
    }

    public function down(): void
    {
        foreach (['order_indoor', 'order_outdoor'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropIndex('gunggungan_period_payment_index');
            });
        }
    }
};
