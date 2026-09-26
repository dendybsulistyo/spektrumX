<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['order_indoor', 'order_outdoor', 'order_artwork'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->index('created_at', $tableName.'_dashboard_created_at_index');
                $table->index('dibayar_at', $tableName.'_final_discount_paid_at_index');
                $table->index(
                    ['diskon_approved_at', 'diskon_rejected_at', 'diskon_requested_at'],
                    $tableName.'_pending_discount_index'
                );
                $table->index(
                    ['hutang_approved_at', 'hutang_rejected_at', 'hutang_requested_at'],
                    $tableName.'_pending_debt_index'
                );
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex($tableName.'_dashboard_created_at_index');
                $table->dropIndex($tableName.'_final_discount_paid_at_index');
                $table->dropIndex($tableName.'_pending_discount_index');
                $table->dropIndex($tableName.'_pending_debt_index');
            });
        }
    }
};
