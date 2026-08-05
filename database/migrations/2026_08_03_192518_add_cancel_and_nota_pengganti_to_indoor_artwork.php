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
        // Mirrors order_outdoor's cancellation + nota pengganti columns —
        // see OrderOutdoorController for the reference implementation this
        // is being extended to cover Indoor and Artwork too.
        foreach (['order_indoor', 'order_artwork'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->timestamp('cancel_requested_at')->nullable();
                $table->foreignId('cancel_requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('cancel_reason')->nullable();
                $table->timestamp('cancel_approved_at')->nullable();
                $table->foreignId('cancel_approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('replacement_order_id')->nullable()->constrained($tableName)->nullOnDelete();
                $table->timestamp('invoice_voided_at')->nullable();
                $table->decimal('replacement_credit', 12, 2)->default(0);
                $table->decimal('topup_amount', 12, 2)->default(0);
                $table->decimal('cashback_amount', 12, 2)->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['order_indoor', 'order_artwork'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('cancel_requested_by');
                $table->dropConstrainedForeignId('cancel_approved_by');
                $table->dropConstrainedForeignId('replacement_order_id');
                $table->dropColumn([
                    'cancel_requested_at',
                    'cancel_reason',
                    'cancel_approved_at',
                    'invoice_voided_at',
                    'replacement_credit',
                    'topup_amount',
                    'cashback_amount',
                ]);
            });
        }
    }
};
