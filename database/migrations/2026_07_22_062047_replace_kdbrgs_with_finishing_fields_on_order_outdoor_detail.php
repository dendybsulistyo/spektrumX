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
            $table->dropColumn('KdBrgs');
            $table->boolean('ada_finishing')->nullable()->after('Fins');
            $table->string('jenis_finishing', 50)->nullable()->after('ada_finishing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_outdoor_detail', function (Blueprint $table) {
            $table->dropColumn(['ada_finishing', 'jenis_finishing']);
            $table->string('KdBrgs', 8)->nullable();
        });
    }
};
