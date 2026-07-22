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
        Schema::table('order_outdoor', function (Blueprint $table) {
            $table->timestamp('cancel_requested_at')->nullable()->after('status');
            $table->unsignedBigInteger('cancel_requested_by')->nullable()->after('cancel_requested_at');
            $table->text('cancel_reason')->nullable()->after('cancel_requested_by');
            $table->timestamp('cancel_approved_at')->nullable()->after('cancel_reason');
            $table->unsignedBigInteger('cancel_approved_by')->nullable()->after('cancel_approved_at');

            $table->foreign('cancel_requested_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancel_approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_outdoor', function (Blueprint $table) {
            $table->dropForeign(['cancel_requested_by']);
            $table->dropForeign(['cancel_approved_by']);
            $table->dropColumn(['cancel_requested_at', 'cancel_requested_by', 'cancel_reason', 'cancel_approved_at', 'cancel_approved_by']);
        });
    }
};
