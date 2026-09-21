<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_service_job_sheets', function (Blueprint $table) {
            $table->foreignId('claimed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable()->after('claimed_by');
            $table->string('claimed_order_type', 10)->nullable()->after('claimed_at');
            $table->index(['claimed_at', 'claimed_order_type']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_service_job_sheets', function (Blueprint $table) {
            $table->dropForeign(['claimed_by']);
            $table->dropColumn(['claimed_by', 'claimed_at', 'claimed_order_type']);
        });
    }
};
