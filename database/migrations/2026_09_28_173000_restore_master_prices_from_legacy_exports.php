<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BACKUP_TABLE = 'master_price_import_backups_20260928';

    private const TABLES = [
        'harga_cetak_outdoor',
        'harga_khusus_customer',
        'harga_bertingkat',
        'harga_cetak_outdoor_khusus',
    ];

    public function up(): void
    {
        $source = $this->source();
        $this->validateSource($source);

        Schema::create(self::BACKUP_TABLE, function (Blueprint $table): void {
            $table->string('table_name')->primary();
            $table->longText('rows_json');
        });

        DB::transaction(function () use ($source): void {
            foreach (self::TABLES as $table) {
                DB::table(self::BACKUP_TABLE)->insert([
                    'table_name' => $table,
                    'rows_json' => json_encode(DB::table($table)->get()->map(fn ($row) => (array) $row)->all(), JSON_THROW_ON_ERROR),
                ]);
            }

            DB::table('harga_cetak_outdoor')->delete();
            $this->insertChunks('harga_cetak_outdoor', $source['standard']);

            DB::table('harga_khusus_customer')->delete();
            $this->insertChunks('harga_khusus_customer', $source['special']);

            DB::table('harga_bertingkat')->delete();
            $this->insertChunks('harga_bertingkat', $source['tiered']);

            $now = now();
            $vipPrices = array_values(array_map(
                fn (array $row): array => [
                    'KdCust' => $row['KdCust'],
                    'KdCtk' => $row['KdCtk'],
                    'HargaStd' => $row['HargaSpc'],
                    'HargaMin' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                array_filter($source['special'], fn (array $row): bool => strlen($row['KdCtk']) === 4),
            ));

            DB::table('harga_cetak_outdoor_khusus')->delete();
            $this->insertChunks('harga_cetak_outdoor_khusus', $vipPrices);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        DB::transaction(function (): void {
            foreach (self::TABLES as $table) {
                $json = DB::table(self::BACKUP_TABLE)->where('table_name', $table)->value('rows_json');
                $rows = $json ? json_decode($json, true, 512, JSON_THROW_ON_ERROR) : [];

                DB::table($table)->delete();
                $this->insertChunks($table, $rows);
            }
        });

        Schema::drop(self::BACKUP_TABLE);
    }

    private function source(): array
    {
        $path = database_path('data/master-prices.json');
        $source = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($source) ? $source : throw new \RuntimeException('Data master harga tidak valid.');
    }

    private function validateSource(array $source): void
    {
        $expected = ['standard' => 195, 'special' => 2918, 'tiered' => 16];
        foreach ($expected as $group => $count) {
            if (! isset($source[$group]) || count($source[$group]) !== $count) {
                throw new \RuntimeException("Jumlah data {$group} tidak sesuai sumber ({$count}).");
            }
        }

        $standardKeys = [];
        foreach ($source['standard'] as $row) {
            $this->assertPositiveInteger($row['HargaStd'] ?? null, 'HargaStd');
            $this->assertPositiveInteger($row['HargaMin'] ?? null, 'HargaMin');
            if (! preg_match('/^\d{4}$/', (string) ($row['KdCtk'] ?? '')) || isset($standardKeys[$row['KdCtk']])) {
                throw new \RuntimeException('Kode harga standar kosong, tidak valid, atau duplikat.');
            }
            $standardKeys[$row['KdCtk']] = true;
        }

        $specialKeys = [];
        foreach ($source['special'] as $row) {
            $this->assertPositiveInteger($row['HargaSpc'] ?? null, 'HargaSpc');
            $key = ($row['KdCust'] ?? '').'|'.($row['KdCtk'] ?? '');
            if (! preg_match('/^\d{2}(\d{2})?$/', (string) ($row['KdCtk'] ?? '')) || isset($specialKeys[$key])) {
                throw new \RuntimeException('Kode harga khusus kosong, tidak valid, atau duplikat.');
            }
            $specialKeys[$key] = true;
        }

        $tierKeys = [];
        foreach ($source['tiered'] as $row) {
            $this->assertPositiveInteger($row['Harga'] ?? null, 'Harga bertingkat');
            if (! is_int($row['BatasA'] ?? null) || ! is_int($row['BatasZ'] ?? null) || $row['BatasA'] < 0 || $row['BatasZ'] < 0) {
                throw new \RuntimeException('Batas harga bertingkat tidak valid.');
            }
            $key = ($row['KdProd'] ?? '').'|'.$row['BatasA'];
            if (isset($tierKeys[$key])) {
                throw new \RuntimeException('Harga bertingkat duplikat.');
            }
            $tierKeys[$key] = true;
        }
    }

    private function assertPositiveInteger(mixed $value, string $field): void
    {
        if (! is_int($value) || $value <= 0) {
            throw new \RuntimeException("{$field} wajib berupa Rupiah bulat lebih dari nol.");
        }
    }

    private function insertChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            if ($chunk !== []) {
                DB::table($table)->insert($chunk);
            }
        }
    }
};
