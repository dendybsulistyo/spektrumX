<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_service_job_sheets', function (Blueprint $table) {
            $table->boolean('is_urgent')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('customer_service_job_sheets', function (Blueprint $table) {
            $table->dropColumn('is_urgent');
        });
    }
};
