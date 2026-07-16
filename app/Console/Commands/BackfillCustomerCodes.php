<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCustomerCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-customer-codes {--dry-run : Tampilkan preview tanpa update ke database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rekonstruksi KdCust di customers dengan mencocokkan nama dari tabel order lama';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Mengumpulkan pasangan KdCust + NmCust dari tabel order...');

        $pairs = collect()
            ->concat(DB::table('ordopr01_ada_nama_customer')->select('KdCust', 'NmCust')->get())
            ->concat(DB::table('ordopr04_')->select('KdCust', 'NmCust')->get());

        // Normalisasi nama (trim + uppercase) supaya pencocokan tidak sensitif spasi/kapital.
        $codeByName = $pairs
            ->map(fn ($row) => [
                'name' => mb_strtoupper(trim($row->NmCust)),
                'code' => trim($row->KdCust),
            ])
            ->filter(fn ($row) => $row['name'] !== '' && $row['code'] !== '')
            ->groupBy('name')
            ->filter(fn ($group) => $group->pluck('code')->unique()->count() === 1)
            ->map(fn ($group) => $group->first()['code']);

        $this->info("Ditemukan {$codeByName->count()} nama unik dengan kode customer yang jelas.");

        $this->info('Mengecek nama customer master yang unik...');

        $masterNameCounts = DB::table('customers')
            ->select('NmCust')
            ->get()
            ->groupBy(fn ($row) => mb_strtoupper(trim($row->NmCust)))
            ->map->count();

        $usedCodes = DB::table('customers')
            ->whereNotNull('KdCust')
            ->pluck('KdCust')
            ->flip();

        $updated = 0;
        $skippedAmbiguousMaster = 0;
        $skippedNoMatch = 0;
        $skippedCodeCollision = 0;

        DB::table('customers')
            ->whereNull('KdCust')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use (
                $codeByName, $masterNameCounts, $usedCodes, $dryRun,
                &$updated, &$skippedAmbiguousMaster, &$skippedNoMatch, &$skippedCodeCollision
            ) {
                foreach ($rows as $row) {
                    $normalized = mb_strtoupper(trim($row->NmCust));

                    if (($masterNameCounts[$normalized] ?? 0) > 1) {
                        $skippedAmbiguousMaster++;

                        continue;
                    }

                    $code = $codeByName->get($normalized);

                    if (! $code) {
                        $skippedNoMatch++;

                        continue;
                    }

                    if (isset($usedCodes[$code])) {
                        $skippedCodeCollision++;

                        continue;
                    }

                    $usedCodes[$code] = true;
                    $updated++;

                    if (! $dryRun) {
                        DB::table('customers')->where('id', $row->id)->update(['KdCust' => $code]);
                    }
                }
            });

        $this->newLine();
        $this->info(($dryRun ? '[DRY RUN] ' : '')."Selesai:");
        $this->line("  Berhasil di-backfill : {$updated}");
        $this->line("  Nama ambigu di master (>1 baris nama sama) : {$skippedAmbiguousMaster}");
        $this->line("  Tidak ketemu kode di tabel order lama : {$skippedNoMatch}");
        $this->line("  Kode sudah dipakai baris lain (tabrakan) : {$skippedCodeCollision}");

        return self::SUCCESS;
    }
}
