<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * "File Gabungan" turned out not to be an actual file upload — it's a
     * plain text field. Renaming the column so its name doesn't keep
     * implying a stored file path. MySQL 5.7 here needs CHANGE COLUMN
     * (no RENAME COLUMN shorthand until 8.0).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE order_status_notes CHANGE COLUMN file_path file_gabungan VARCHAR(255) NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE order_status_notes CHANGE COLUMN file_gabungan file_path VARCHAR(255) NULL DEFAULT NULL');
    }
};
