<?php

namespace Database\Seeders;

use App\Models\JurnalEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JurnalJanuariSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = database_path('seeders/jurnal_januari.json');
        if (!file_exists($filePath)) {
            $this->command->error("File jurnal_januari.json tidak ditemukan!");
            return;
        }

        $json = file_get_contents($filePath);
        $entries = json_decode($json, true);

        if (empty($entries)) {
            $this->command->error("Data jurnal kosong!");
            return;
        }

        // Clean existing Jurnal entries to prevent double seeding
        DB::table('am')->truncate();

        $this->command->info("Seeding " . count($entries) . " baris jurnal umum...");

        // Chunk insert for performance and safety
        $chunks = array_chunk($entries, 100);
        foreach ($chunks as $chunk) {
            JurnalEntry::insert($chunk);
        }

        $this->command->info("Seeding jurnal umum selesai!");
    }
}
