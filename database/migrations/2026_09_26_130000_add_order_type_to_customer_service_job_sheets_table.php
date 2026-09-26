<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_service_job_sheets', function (Blueprint $table) {
            $table->string('order_type', 10)->nullable()->after('notes');
            $table->index(['claimed_at', 'order_type']);
        });

        DB::table('customer_service_job_sheets')
            ->whereNotNull('claimed_order_type')
            ->update(['order_type' => DB::raw('claimed_order_type')]);
    }

    public function down(): void
    {
        Schema::table('customer_service_job_sheets', function (Blueprint $table) {
            $table->dropIndex(['claimed_at', 'order_type']);
            $table->dropColumn('order_type');
        });
    }
};
