<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengaturan_keuangan', function (Blueprint $table) {
            $table->boolean('auto_print_sales_order')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_keuangan', function (Blueprint $table) {
            $table->dropColumn('auto_print_sales_order');
        });
    }
};
