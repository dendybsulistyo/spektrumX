<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_outdoor', function (Blueprint $table) {
            $table->foreignId('replacement_order_id')->nullable()->after('cancel_approved_by')->constrained('order_outdoor')->nullOnDelete();
            $table->timestamp('invoice_voided_at')->nullable()->after('replacement_order_id');
            $table->decimal('replacement_credit', 12, 2)->default(0)->after('invoice_voided_at');
            $table->decimal('topup_amount', 12, 2)->default(0)->after('replacement_credit');
            $table->decimal('cashback_amount', 12, 2)->default(0)->after('topup_amount');
        });
    }

    public function down(): void
    {
        Schema::table('order_outdoor', function (Blueprint $table) {
            $table->dropForeign(['replacement_order_id']);
            $table->dropColumn(['replacement_order_id', 'invoice_voided_at', 'replacement_credit', 'topup_amount', 'cashback_amount']);
        });
    }
};
