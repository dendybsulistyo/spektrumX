<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Status dengan "@semua": dianggap menyebut setiap user (selain pembuatnya). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_statuses', function (Blueprint $table) {
            $table->boolean('mention_all')->default(false)->after('mentions');
        });
    }

    public function down(): void
    {
        Schema::table('user_statuses', function (Blueprint $table) {
            $table->dropColumn('mention_all');
        });
    }
};
