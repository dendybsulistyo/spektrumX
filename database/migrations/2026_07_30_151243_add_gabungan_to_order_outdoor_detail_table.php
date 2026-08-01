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
        Schema::table('order_outdoor_detail', function (Blueprint $table) {
            $table->string('gabungan', 255)->nullable()->after('Qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_outdoor_detail', function (Blueprint $table) {
            $table->dropColumn('gabungan');
        });
    }
};
