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
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // diskon_persen is the APPROVED percentage actually applied
                // at payment; diskon_requested_persen holds the value while
                // pending, kept separate so a rejection never accidentally
                // discounts anything.
                $table->decimal('diskon_persen', 5, 2)->nullable();
                $table->decimal('diskon_requested_persen', 5, 2)->nullable();
                $table->string('diskon_alasan', 255)->nullable();
                $table->timestamp('diskon_requested_at')->nullable();
                $table->foreignId('diskon_requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('diskon_approved_at')->nullable();
                $table->foreignId('diskon_approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('diskon_rejected_at')->nullable();
                $table->foreignId('diskon_rejected_by')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('diskon_requested_by');
                $table->dropConstrainedForeignId('diskon_approved_by');
                $table->dropConstrainedForeignId('diskon_rejected_by');
                $table->dropColumn([
                    'diskon_persen',
                    'diskon_requested_persen',
                    'diskon_alasan',
                    'diskon_requested_at',
                    'diskon_approved_at',
                    'diskon_rejected_at',
                ]);
            });
        }
    }
};
