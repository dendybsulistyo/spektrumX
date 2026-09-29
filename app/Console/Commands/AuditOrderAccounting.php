<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $orderReconciliation = collect([
            'indoor' => 'order_indoor',
            'outdoor' => 'order_outdoor',
            'artwork' => 'order_artwork',
        ])->flatMap(function (string $table, string $type) use ($from) {
            $payments = DB::table('order_payments')
                ->where('order_type', $type)
                ->selectRaw('order_id, SUM(jumlah) paid_total')
                ->groupBy('order_id');

            return DB::table($table.' as o')
                ->leftJoinSub($payments, 'p', fn ($join) => $join->on('p.order_id', '=', 'o.id'))
                ->when($from, fn ($query) => $query->whereDate('o.TglOrder', '>=', $from))
                ->whereRaw('ABS(COALESCE(p.paid_total, 0) - COALESCE(o.jumlah_dibayar, 0)) > 0.01')
                ->get(['o.id', 'o.NoOrder', 'o.status_bayar', 'o.jumlah_dibayar', 'o.jumlah_piutang', DB::raw('COALESCE(p.paid_total, 0) payment_rows_total')])
                ->map(fn ($row) => ['order_type' => $type, ...((array) $row)]);
        })->values();

        $purchaseReconciliation = DB::table('accounting_purchases')
            ->when($from, fn ($query) => $query->whereDate('tanggal', '>=', $from))
            ->whereRaw('ABS(total - (jumlah_dibayar + jumlah_hutang + jumlah_retur)) > 0.01')
            ->get(['id', 'tanggal', 'nomor_bukti', 'total', 'jumlah_dibayar', 'jumlah_hutang', 'jumlah_retur']);

        $missingJournalReferences = collect([
            ['table' => 'accounting_purchases', 'column' => 'no_trans_jurnal', 'label' => 'purchase'],
            ['table' => 'accounting_purchase_payments', 'column' => 'no_trans_jurnal', 'label' => 'purchase_payment'],
            ['table' => 'accounting_purchase_returns', 'column' => 'no_trans_jurnal', 'label' => 'purchase_return'],
            ['table' => 'jurnal_manual', 'column' => 'no_trans_jurnal', 'label' => 'manual_journal'],
            ['table' => 'pengeluaran', 'column' => 'no_trans_jurnal', 'label' => 'expense'],
            ['table' => 'slip_gaji', 'column' => 'no_trans_jurnal', 'label' => 'payroll'],
            ['table' => 'final_sales_discounts', 'column' => 'journal_transaction_number', 'label' => 'final_sales_discount'],
        ])->filter(fn (array $source) => Schema::hasTable($source['table']) && Schema::hasColumn($source['table'], $source['column']))
            ->flatMap(function (array $source) use ($from) {
                return DB::table($source['table'].' as source')
                    ->leftJoin('am as journal', 'journal.NoTrans', '=', 'source.'.$source['column'])
                    ->whereNotNull('source.'.$source['column'])
                    ->when($from && Schema::hasColumn($source['table'], 'tanggal'), fn ($query) => $query->whereDate('source.tanggal', '>=', $from))
                    ->whereNull('journal.NoTrans')
                    ->get(['source.id', 'source.'.$source['column'].' as no_trans'])
                    ->map(fn ($row) => ['source' => $source['label'], 'id' => $row->id, 'no_trans' => $row->no_trans]);
            })->values();

        $paidOrdersWithoutJournal = collect([
            'indoor' => 'order_indoor',
            'outdoor' => 'order_outdoor',
            'artwork' => 'order_artwork',
        ])->flatMap(function (string $table, string $type) use ($from) {
            return DB::table($table.' as o')
                ->leftJoin('am as journal', 'journal.Bukti', '=', 'o.NoOrder')
                ->when($from, fn ($query) => $query->whereDate('o.TglOrder', '>=', $from))
                ->where('o.jumlah_dibayar', '>', 0)
                ->whereNull('journal.NoTrans')
                ->distinct()
                ->get(['o.id', 'o.NoOrder', 'o.status_bayar', 'o.jumlah_dibayar'])
                ->map(fn ($row) => ['order_type' => $type, ...((array) $row)]);
        })->values();

        $report = ['from' => $from, 'historical_demo_invoices' => $demoCount,
            'context' => 'Invoice demo berasal dari impor Excel, bukan bukti seluruh workflow operasional telah dijalankan. Selisih jurnal historis perlu ditelusuri ke sumber impor; tidak otomatis menunjukkan kegagalan workflow baru. Filter tanggal tidak membedakan asal jurnal.',
            'scope' => 'Jurnal am sesuai filter tanggal (jika diberikan); kesamaan referensi, deskripsi, tanggal dan total merupakan kandidat, bukan bukti duplikasi.',
            'journal_groups' => $journal->count(),
            'unbalanced' => $unbalanced,
            'duplicate_candidates' => $duplicates,
            'order_payment_mismatches' => $orderReconciliation,
            'purchase_balance_mismatches' => $purchaseReconciliation,
            'missing_journal_references' => $missingJournalReferences,
            'paid_orders_without_journal' => $paidOrdersWithoutJournal,
        ];
        $path = storage_path('logs/order-accounting-audit-'.now()->format('Ymd-His-u').'.json');
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));
        $this->line(json_encode([
            'journal_groups' => $journal->count(),
            'unbalanced' => $unbalanced->count(),
            'duplicate_candidates' => $duplicates->count(),
            'order_payment_mismatches' => $orderReconciliation->count(),
            'purchase_balance_mismatches' => $purchaseReconciliation->count(),
            'missing_journal_references' => $missingJournalReferences->count(),
            'paid_orders_without_journal' => $paidOrdersWithoutJournal->count(),
            'report' => $path,
        ]));

        return self::SUCCESS;
    }
}
