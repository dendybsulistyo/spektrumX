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
        foreach (['order_indoor', 'order_outdoor'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('payment_queue', 10)->default('kasir')->index();
                $table->timestamp('sent_to_cs_at')->nullable();
                $table->decimal('cs_transfer_amount', 15, 2)->nullable();
                $table->string('cs_payment_type', 12)->nullable();
                $table->foreignId('cs_processed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cs_processed_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['order_indoor', 'order_outdoor'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['cs_processed_by']);
                $table->dropColumn(['payment_queue', 'sent_to_cs_at', 'cs_transfer_amount', 'cs_payment_type', 'cs_processed_by', 'cs_processed_at']);
            });
        }
    }
};
