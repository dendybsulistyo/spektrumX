<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditOrderAccounting extends Command
{
    protected $signature = 'orders:audit-accounting {--from= : Tanggal awal jurnal YYYY-MM-DD}';

    protected $description = 'Audit read-only kandidat jurnal ganda dan jurnal tidak seimbang';

    public function handle(): int
    {
        $from = $this->option('from');
        if ($from && (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $from) || ! checkdate((int) substr($from, 5, 2), (int) substr($from, 8, 2), (int) substr($from, 0, 4)))) {
            $this->error('Tanggal --from harus valid dalam format YYYY-MM-DD.');

            return self::FAILURE;
        }
        $journal = DB::table('am')->when($from, fn ($query) => $query->where('TgTrans', '>=', $from))->selectRaw('Perio, NoTrans, MIN(Bukti) Bukti, MIN(KetMT) KetMT, MIN(TgTrans) TgTrans, SUM(Debet) debet, SUM(Kredit) kredit')
            ->groupBy('Perio', 'NoTrans')->get();
        $unbalanced = $journal->filter(fn ($row) => abs($row->debet - $row->kredit) > 0.01)->values();
        $duplicates = $journal->groupBy(fn ($row) => json_encode([$row->Bukti, $row->KetMT, $row->TgTrans, $row->debet, $row->kredit]))
            ->filter(fn ($rows) => $rows->pluck('NoTrans')->unique()->count() > 1)->values();
        $demoCount = DB::table('order_documents')->where('kind', 'inv')->where('snapshot->origin', 'historical_demo')->count();
        $report = ['from' => $from, 'historical_demo_invoices' => $demoCount,
            'context' => 'Invoice demo berasal dari impor Excel, bukan bukti seluruh workflow operasional telah dijalankan. Selisih jurnal historis perlu ditelusuri ke sumber impor; tidak otomatis menunjukkan kegagalan workflow baru. Filter tanggal tidak membedakan asal jurnal.',
            'scope' => 'Jurnal am sesuai filter tanggal (jika diberikan); kesamaan referensi, deskripsi, tanggal dan total merupakan kandidat, bukan bukti duplikasi.',
            'journal_groups' => $journal->count(), 'unbalanced' => $unbalanced, 'duplicate_candidates' => $duplicates];
        $path = storage_path('logs/order-accounting-audit-'.now()->format('Ymd-His').'.json');
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));
        $this->line(json_encode(['journal_groups' => $journal->count(), 'unbalanced' => $unbalanced->count(), 'duplicate_candidates' => $duplicates->count(), 'report' => $path]));

        return self::SUCCESS;
    }
}
