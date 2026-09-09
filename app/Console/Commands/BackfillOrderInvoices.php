<?php

namespace App\Console\Commands;

use App\Models\OrderPayment;
use App\Services\OrderDocumentService;
use Illuminate\Console\Command;

class BackfillOrderInvoices extends Command
{
    protected $signature = 'orders:backfill-invoices {--demo : Tandai impor historis sebagai data demo} {--apply : Simpan invoice; default hanya memeriksa}';

    protected $description = 'Menerbitkan invoice historis dengan bukti tanggal pelunasan, tanpa membuat jurnal';

    public function handle(OrderDocumentService $documents): int
    {
        $counts = ['eligible' => 0, 'created' => 0, 'missing_settlement_date' => 0, 'missing_paid_amount' => 0];
        foreach (OrderDocumentService::MODELS as $type => $model) {
            $model::where('status_bayar', 'lunas')->where('jumlah_piutang', '<=', 0)
                ->where('status', '!=', 'batal')->whereNull('invoice_voided_at')
                ->whereNotNull('dibayar_at')
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('order_documents')->where('kind', 'inv')->where('order_type', $type)->whereColumn('order_id', (new $model)->getTable().'.id'))
                ->chunkById(250, function ($orders) use ($documents, $type, &$counts) {
                    foreach ($orders as $order) {
                        $settled = OrderPayment::where('order_type', $type)->where('order_id', $order->id)
                            ->whereIn('jenis', ['lunas', 'pelunasan_dp', 'pelunasan_hutang', 'nota_pengganti'])->max('created_at');
                        $at = $this->option('demo')
                            ? $order->dibayar_at
                            : ($settled ?: (in_array($order->metode_bayar, ['dp', 'hutang'], true) ? null : $order->dibayar_at));
                        if (! $at) {
                            $counts['missing_settlement_date']++;

                            continue;
                        }
                        if ((float) $order->jumlah_dibayar <= 0 && (float) $order->total > 0) {
                            $counts['missing_paid_amount']++;

                            continue;
                        }
                        $counts['eligible']++;
                        if ($this->option('apply') && $documents->issueInvoice($order, $at, $this->option('demo') ? 'historical_demo' : 'historical_import')) {
                            $counts['created']++;
                        }
                    }
                });
        }
        $this->line(json_encode($counts));

        return self::SUCCESS;
    }
}
