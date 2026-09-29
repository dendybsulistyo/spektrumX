<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const BACKUP_TABLE = 'customer_dedup_backups_20260929';

    private const EXPECTED_CUSTOMERS = 36047;

    private const EXPECTED_VIP = 135;

    private const EXPECTED_DELETIONS = 13642;

    public function up(): void
    {
        if (Schema::hasTable(self::BACKUP_TABLE)) {
            throw new RuntimeException('Tabel backup deduplikasi customer sudah ada.');
        }

        $customers = DB::table('customers')->get()->map(fn ($row): array => (array) $row);
        $vipCodes = DB::table('customer_limits')->pluck('KdCust')->map(fn ($code): string => (string) $code)->flip();

        if ($customers->count() !== self::EXPECTED_CUSTOMERS || $vipCodes->count() !== self::EXPECTED_VIP) {
            throw new RuntimeException(sprintf(
                'Audit customer berubah. Ditemukan %d customer dan %d VIP; migrasi mengharapkan %d customer dan %d VIP.',
                $customers->count(),
                $vipCodes->count(),
                self::EXPECTED_CUSTOMERS,
                self::EXPECTED_VIP,
            ));
        }

        $referenceCounts = $this->referenceCounts();
        $deletions = $this->deletions($customers, $vipCodes, $referenceCounts);

        if ($deletions->count() !== self::EXPECTED_DELETIONS) {
            throw new RuntimeException(sprintf(
                'Hasil audit duplikasi berubah. Ditemukan %d calon hapus; migrasi mengharapkan %d.',
                $deletions->count(),
                self::EXPECTED_DELETIONS,
            ));
        }

        if ($deletions->contains(fn (array $item): bool => $vipCodes->has((string) $item['customer']['KdCust']))) {
            throw new RuntimeException('Migrasi dibatalkan karena calon hapus mengandung customer VIP.');
        }

        if ($deletions->contains(fn (array $item): bool => ($referenceCounts[(string) $item['customer']['KdCust']] ?? 0) > 0)) {
            throw new RuntimeException('Migrasi dibatalkan karena calon hapus masih mempunyai referensi data.');
        }

        Schema::create(self::BACKUP_TABLE, function (Blueprint $table): void {
            $table->unsignedBigInteger('customer_id')->primary();
            $table->string('duplicate_code', 6)->unique();
            $table->string('retained_code', 6);
            $table->longText('customer_json');
        });

        DB::transaction(function () use ($deletions): void {
            foreach ($deletions->chunk(500) as $chunk) {
                DB::table(self::BACKUP_TABLE)->insert($chunk->map(fn (array $item): array => [
                    'customer_id' => $item['customer']['id'],
                    'duplicate_code' => $item['customer']['KdCust'],
                    'retained_code' => $item['retained_code'],
                    'customer_json' => json_encode($item['customer'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ])->all());

                DB::table('customers')->whereIn('id', $chunk->pluck('customer.id'))->delete();
            }

            if (DB::table('customers')->count() !== self::EXPECTED_CUSTOMERS - self::EXPECTED_DELETIONS) {
                throw new RuntimeException('Jumlah customer setelah deduplikasi tidak sesuai perhitungan audit.');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP_TABLE)) {
            return;
        }

        DB::transaction(function (): void {
            DB::table(self::BACKUP_TABLE)
                ->orderBy('customer_id')
                ->chunk(500, function ($backups): void {
                    $rows = $backups->map(fn ($backup): array => json_decode(
                        $backup->customer_json,
                        true,
                        512,
                        JSON_THROW_ON_ERROR,
                    ))->all();

                    DB::table('customers')->insert($rows);
                });
        });

        Schema::drop(self::BACKUP_TABLE);
    }

    /**
     * @return array<string, int>
     */
    private function referenceCounts(): array
    {
        $database = DB::getDatabaseName();
        $tables = collect(DB::select(
            'SELECT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = ? AND TABLE_NAME <> ? ORDER BY TABLE_NAME',
            [$database, 'KdCust', 'customers'],
        ))->pluck('TABLE_NAME');

        $counts = [];
        foreach ($tables as $table) {
            foreach (DB::table($table)->select('KdCust', DB::raw('COUNT(*) AS aggregate'))->whereNotNull('KdCust')->groupBy('KdCust')->get() as $row) {
                $code = (string) $row->KdCust;
                $counts[$code] = ($counts[$code] ?? 0) + (int) $row->aggregate;
            }
        }

        return $counts;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $customers
     * @param  Collection<string, int>  $vipCodes
     * @param  array<string, int>  $referenceCounts
     * @return Collection<int, array{customer: array<string, mixed>, retained_code: string}>
     */
    private function deletions(Collection $customers, Collection $vipCodes, array $referenceCounts): Collection
    {
        return $customers
            ->groupBy(fn (array $customer): string => $this->identityKey($customer))
            ->filter(function (Collection $group, string $key): bool {
                [$name, $phone] = array_pad(explode('|', $key, 3), 3, '');

                return $name !== '' && strlen($phone) >= 8 && $group->count() > 1;
            })
            ->flatMap(function (Collection $group) use ($vipCodes, $referenceCounts): Collection {
                $ranked = $group->sort(function (array $left, array $right) use ($vipCodes, $referenceCounts): int {
                    $leftCode = (string) $left['KdCust'];
                    $rightCode = (string) $right['KdCust'];
                    $leftRank = [$vipCodes->has($leftCode) ? 1 : 0, $referenceCounts[$leftCode] ?? 0, -((int) $left['id'])];
                    $rightRank = [$vipCodes->has($rightCode) ? 1 : 0, $referenceCounts[$rightCode] ?? 0, -((int) $right['id'])];

                    return $rightRank <=> $leftRank;
                })->values();

                $retainedCode = (string) $ranked->first()['KdCust'];

                return $ranked->slice(1)
                    ->filter(function (array $customer) use ($vipCodes, $referenceCounts): bool {
                        $code = (string) $customer['KdCust'];

                        return ! $vipCodes->has($code) && ($referenceCounts[$code] ?? 0) === 0;
                    })
                    ->map(fn (array $customer): array => [
                        'customer' => $customer,
                        'retained_code' => $retainedCode,
                    ]);
            })
            ->values();
    }

    /**
     * Identitas audit: nama, telepon, dan alamat yang sama setelah formatnya dinormalisasi.
     */
    private function identityKey(array $customer): string
    {
        $name = $this->normalizeText($customer['NmCust'] ?? null);
        $address = $this->normalizeText($customer['Alamat'] ?? null);
        $phone = preg_replace('/\D+/', '', (string) ($customer['Telp'] ?? ''));

        if (str_starts_with($phone, '62')) {
            $phone = '0'.substr($phone, 2);
        }

        return $name.'|'.$phone.'|'.$address;
    }

    private function normalizeText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }
};
