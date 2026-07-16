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
        Schema::create('order_outdoor', function (Blueprint $table) {
            $table->id();
            $table->string('NoOrder', 20)->unique();
            $table->date('TglOrder');
            $table->string('KdCust', 6);
            $table->string('KdOpr', 4);
            $table->boolean('Cetak')->default(false);
            $table->timestamps();

            $table->index('KdCust');
        });

        Schema::create('order_outdoor_detail', function (Blueprint $table) {
            $table->id();
            $table->string('BrsOrder', 25)->unique();
            $table->foreignId('order_outdoor_id')->constrained('order_outdoor')->cascadeOnDelete();
            $table->string('NmFile', 50);
            $table->double('Panjang');
            $table->double('Lebar');
            $table->integer('Qty');
            $table->string('KdCtk', 4)->nullable();
            $table->string('KdBrgs', 8)->nullable();
            $table->string('Fins', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_outdoor_detail');
        Schema::dropIfExists('order_outdoor');
    }
};
