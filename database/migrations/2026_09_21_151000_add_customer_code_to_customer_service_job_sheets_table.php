<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_service_job_sheets', function (Blueprint $table) {
            $table->string('customer_code', 20)->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('customer_service_job_sheets', function (Blueprint $table) {
            $table->dropColumn('customer_code');
        });
    }
};
