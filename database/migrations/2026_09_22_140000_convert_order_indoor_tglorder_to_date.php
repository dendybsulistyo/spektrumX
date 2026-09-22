<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_indoor', function (Blueprint $table) {
            $table->date('TglOrder')->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_indoor', function (Blueprint $table) {
            $table->string('TglOrder', 10)->change();
        });
    }
};
