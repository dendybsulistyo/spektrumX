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
        Schema::table('order_indoor_detail', function (Blueprint $table) {
            $table->unsignedBigInteger('order_indoor_id')->nullable()->after('BrsOrder');
            $table->string('stage', 20)->default('desain')->after('KdStat');
            $table->unsignedInteger('qty_diproses')->default(0)->after('Qty');
            $table->timestamp('stage_entered_at')->nullable()->after('stage');
            $table->index('order_indoor_id');
            $table->index(['order_indoor_id', 'stage']);
        });

        Schema::table('order_outdoor_detail', function (Blueprint $table) {
            $table->string('stage', 20)->default('desain')->after('qty_diproses');
            $table->timestamp('stage_entered_at')->nullable()->after('stage');
            $table->index(['order_outdoor_id', 'stage']);
        });

        Schema::table('order_artwork_detail', function (Blueprint $table) {
            $table->string('stage', 20)->default('desain')->after('Qty');
            $table->unsignedInteger('qty_diproses')->default(0)->after('stage');
            $table->timestamp('stage_entered_at')->nullable()->after('stage');
            $table->index(['order_artwork_id', 'stage']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_indoor_detail', function (Blueprint $table) {
            $table->dropIndex(['order_indoor_id', 'stage']);
            $table->dropIndex(['order_indoor_id']);
            $table->dropColumn(['order_indoor_id', 'stage', 'qty_diproses', 'stage_entered_at']);
        });

        Schema::table('order_outdoor_detail', function (Blueprint $table) {
            $table->dropIndex(['order_outdoor_id', 'stage']);
            $table->dropColumn(['stage', 'stage_entered_at']);
        });

        Schema::table('order_artwork_detail', function (Blueprint $table) {
            $table->dropIndex(['order_artwork_id', 'stage']);
            $table->dropColumn(['stage', 'qty_diproses', 'stage_entered_at']);
        });
    }
};
