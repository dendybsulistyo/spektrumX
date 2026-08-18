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
                // A VIP customer's hutang no longer gets flatly rejected once
                // it would push them past their plafon (customer_limits.Batas)
                // — it's held here for Admin/Admin Kasir sign-off instead. See
                // HasHutangApproval::hutangApprovalStatus().
                $table->string('hutang_catatan', 255)->nullable();
                $table->timestamp('hutang_requested_at')->nullable();
                $table->foreignId('hutang_requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('hutang_approved_at')->nullable();
                $table->foreignId('hutang_approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('hutang_rejected_at')->nullable();
                $table->foreignId('hutang_rejected_by')->nullable()->constrained('users')->nullOnDelete();
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
                $table->dropConstrainedForeignId('hutang_requested_by');
                $table->dropConstrainedForeignId('hutang_approved_by');
                $table->dropConstrainedForeignId('hutang_rejected_by');
                $table->dropColumn([
                    'hutang_catatan',
                    'hutang_requested_at',
                    'hutang_approved_at',
                    'hutang_rejected_at',
                ]);
            });
        }
    }
};
