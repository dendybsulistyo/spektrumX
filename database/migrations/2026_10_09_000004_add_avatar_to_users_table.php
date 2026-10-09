<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Pilihan editor (untuk diedit ulang) + SVG hasil yang sudah disaring.
            $table->json('avatar_options')->nullable()->after('role_id');
            $table->mediumText('avatar_svg')->nullable()->after('avatar_options');
            $table->timestamp('avatar_updated_at')->nullable()->after('avatar_svg');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_options', 'avatar_svg', 'avatar_updated_at']);
        });
    }
};
