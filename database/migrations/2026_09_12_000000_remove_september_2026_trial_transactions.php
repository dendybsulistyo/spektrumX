<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Superseded by 2026_09_12_000001. Kept as a no-op because this
        // migration may already be recorded on a deployed database.
    }

    public function down(): void
    {
        // No data is changed by this compatibility migration.
    }
};
