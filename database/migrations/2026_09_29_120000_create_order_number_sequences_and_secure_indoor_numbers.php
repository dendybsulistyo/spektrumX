<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('order_indoor')
            ->select('NoOrder')
            ->groupBy('NoOrder')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new RuntimeException('Tidak dapat memasang indeks unik: masih ada NoOrder Indoor yang duplikat.');
        }

        Schema::table('order_indoor', function (Blueprint $table): void {
            $table->unique('NoOrder', 'order_indoor_noorder_unique');
        });

        Schema::create('order_number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('order_type', 10);
            $table->date('order_date');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();
            $table->unique(['order_type', 'order_date'], 'order_number_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_number_sequences');
        Schema::table('order_indoor', function (Blueprint $table): void {
            $table->dropUnique('order_indoor_noorder_unique');
        });
    }
};
