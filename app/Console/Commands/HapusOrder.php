<?php

namespace App\Console\Commands;

use App\Models\DeletedOrderLog;
use App\Models\PeriodeTutupBuku;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus order uji coba tertentu beserta urusan keuangannya (jurnal ber-Bukti
 * No. Order, pembayaran, dokumen SO/DO/INV, plafon VIP terpakai).
 *
 *   php artisan spektrum:hapus-order IND.2.261103018              → hanya tampilkan
 *   php artisan spektrum:hapus-order IND.2.261103018 --jalankan --alasan="Dummy uji stand banner"
 *
 * Salinan lengkap order disimpan dulu ke deleted_order_logs (menu Riwayat Order Dihapus).
 */
class HapusOrder extends Command
{
    protected $signature = 'spektrum:hapus-order {nomor* : No. Order} {--jalankan : benar-benar menghapus (tanpa ini hanya menampilkan)} {--alasan= : alasan penghapusan (wajib saat menghapus)} {--oleh= : nama yang menghapus (default user server)}';

    protected $description = 'Hapus order dummy tertentu beserta jurnal, pembayaran, dokumen & plafon VIP-nya';

    private const ORDERS = [
        'indoor' => ['order_indoor', 'order_indoor_detail', 'order_indoor_id'],
        'outdoor' => ['order_outdoor', 'order_outdoor_detail', 'order_outdoor_id'],
        'artwork' => ['order_artwork', 'order_artwork_detail', 'order_artwork_id'],
    ];

    /** Tabel turunan order dengan kolom (order_type, order_id); sama dengan spektrum:hapus-transaksi. */
    private const PER_ORDER = [
        'payment_method_corrections', 'order_payments', 'reprint_logs', 'reprint_requests',
        'order_status_notes', 'sales_order_prints', 'sales_transaction_archives', 'final_sales_discounts',
        'order_financial_adjustments', 'order_rework_requests', 'order_pickup_signatures',
        'order_comments', 'order_comment_reads', 'customer_service_job_sheets', 'laporan_ppn_final_items',
        'order_late_logs',
    ];

    public function handle(): int
    {
        $run = (bool) $this->option('jalankan');
        $plans = [];

        foreach (array_unique($this->argument('nomor')) as $nomor) {
            $found = $this->find($nomor);
            if (! $found) {
                $this->error("Order {$nomor} tidak ditemukan.");

                return self::FAILURE;
            }
            [$type, $order] = $found;
            if ($problem = $this->blocker($type, $order)) {
                $this->error("{$order->NoOrder}: {$problem}");

                return self::FAILURE;
            }
            $plans[] = [$type, $order];
        }

        foreach ($plans as [$type, $order]) {
            $this->info("{$order->NoOrder} ({$type}) · customer {$order->KdCust} · status {$order->status} / {$order->status_bayar} · piutang Rp ".number_format((float) $order->jumlah_piutang, 0, ',', '.'));
            $this->table(['Data', $run ? 'Dihapus' : 'Akan dihapus'], collect($this->counts($type, $order))
                ->filter()->map(fn ($n, $label) => [$label, $n])->values()->all());
        }

        if (! $run) {
            $this->info('Belum ada yang dihapus. Tambahkan --jalankan untuk menghapus.');

            return self::SUCCESS;
        }
        if (! Schema::hasTable('deleted_order_logs')) {
            $this->error('Tabel arsip deleted_order_logs belum ada; jalankan migrasi dulu.');

            return self::FAILURE;
        }
        $alasan = trim((string) ($this->option('alasan') ?: $this->ask('Alasan penghapusan')));
        if ($alasan === '') {
            $this->error('Alasan wajib diisi.');

            return self::FAILURE;
        }
        if ($this->ask('Ketik HAPUS untuk melanjutkan') !== 'HAPUS') {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }
        $oleh = trim((string) $this->option('oleh')) ?: (get_current_user() ?: 'server');

        DB::transaction(function () use ($plans, $alasan, $oleh) {
            foreach ($plans as [$type, $order]) {
                $this->archive($type, $order, $alasan, $oleh);
                $this->delete($type, $order);
            }
        });
        $this->info('Selesai.');

        return self::SUCCESS;
    }

    /** @return array{0: string, 1: object}|null */
    private function find(string $nomor): ?array
    {
        foreach (self::ORDERS as $type => [$table]) {
            $order = DB::table($table)->where('NoOrder', $nomor)->first();
            if ($order) {
                return [$type, $order];
            }
        }

        return null;
    }

    private function blocker(string $type, object $order): ?string
    {
        if (Schema::hasColumn('order_payments', 'batch_id')
            && DB::table('order_payments')->where('order_type', $type)->where('order_id', $order->id)->whereNotNull('batch_id')->exists()) {
            return 'ikut pembayaran gabungan (batch) yang jurnalnya dipakai order lain; hapus manual.';
        }
        $closed = DB::table('am')->where('Bukti', $order->NoOrder)->pluck('TgTrans')
            ->first(fn ($tanggal) => PeriodeTutupBuku::isClosed((string) $tanggal));

        return $closed ? 'jurnalnya ada di periode '.substr((string) $closed, 0, 7).' yang sudah tutup buku.' : null;
    }

    /** @return array<string, int> */
    private function counts(string $type, object $order): array
    {
        [$table, $detailTable, $fk] = self::ORDERS[$type];
        $counts = [
            $table => 1,
            $detailTable => DB::table($detailTable)->where($fk, $order->id)->count(),
            'am (jurnal)' => DB::table('am')->where('Bukti', $order->NoOrder)->count(),
            'order_documents (SO/DO/INV)' => $this->documents($type, $order)->count(),
        ];
        foreach ($this->perOrderTables() as $related) {
            $counts[$related] = $this->related($related, $type, $order)->count();
        }
        if ($this->creditToRelease($order) > 0) {
            $counts['plafon VIP dikembalikan (Rp)'] = (int) $this->creditToRelease($order);
        }

        return $counts;
    }

    private function delete(string $type, object $order): void
    {
        [$table, $detailTable, $fk] = self::ORDERS[$type];

        $credit = $this->creditToRelease($order);
        if ($credit > 0) {
            $limit = DB::table('customer_limits')->where('KdCust', $order->KdCust)->lockForUpdate()->first();
            DB::table('customer_limits')->where('KdCust', $order->KdCust)
                ->update(['Total' => max(0, (float) $limit->Total - $credit)]);
        }

        $documentIds = $this->documents($type, $order)->pluck('id');
        if ($documentIds->isNotEmpty() && Schema::hasTable('order_document_prints')) {
            DB::table('order_document_prints')->whereIn('order_document_id', $documentIds)->delete();
        }
        foreach ($this->perOrderTables() as $related) {
            $this->related($related, $type, $order)->delete();
        }
        $this->documents($type, $order)->delete();
        DB::table('am')->where('Bukti', $order->NoOrder)->delete();
        DB::table($detailTable)->where($fk, $order->id)->delete();
        if (Schema::hasColumn($table, 'replacement_order_id')) {
            DB::table($table)->where('replacement_order_id', $order->id)->update(['replacement_order_id' => null]);
        }
        DB::table($table)->where('id', $order->id)->delete();
    }

    /** Salinan order sebelum dihapus, untuk menu Riwayat Order Dihapus. */
    private function archive(string $type, object $order, string $alasan, string $oleh): void
    {
        [, $detailTable, $fk] = self::ORDERS[$type];
        $customerName = Schema::hasTable('customers')
            ? DB::table('customers')->where('KdCust', $order->KdCust)->value('NmCust')
            : null;

        DeletedOrderLog::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'no_order' => $order->NoOrder,
            'kd_cust' => $order->KdCust,
            'customer_name' => $customerName,
            'tgl_order' => $order->TglOrder ?? null,
            'status' => $order->status,
            'status_bayar' => $order->status_bayar,
            'total' => (float) ($order->total ?? 0),
            'kredit_dikembalikan' => $this->creditToRelease($order),
            'alasan' => mb_substr($alasan, 0, 255),
            'dihapus_oleh' => mb_substr($oleh, 0, 100),
            'snapshot' => [
                'order' => (array) $order,
                'items' => DB::table($detailTable)->where($fk, $order->id)->get()->map(fn ($row) => (array) $row)->all(),
                'payments' => Schema::hasTable('order_payments') ? $this->related('order_payments', $type, $order)->get()->map(fn ($row) => (array) $row)->all() : [],
                'documents' => $this->documents($type, $order)->get()->map(fn ($row) => (array) $row)->all(),
                'journals' => DB::table('am')->where('Bukti', $order->NoOrder)->get()->map(fn ($row) => (array) $row)->all(),
            ],
            'deleted_at' => now(),
        ]);
    }

    /** Plafon VIP yang masih terpakai oleh order hutang ini. */
    private function creditToRelease(object $order): float
    {
        return $order->status_bayar === 'hutang' && DB::table('customer_limits')->where('KdCust', $order->KdCust)->exists()
            ? max(0, (float) $order->jumlah_piutang)
            : 0.0;
    }

    private function documents(string $type, object $order): Builder
    {
        return DB::table('order_documents')->where('order_type', $type)->where('order_id', $order->id);
    }

    private function related(string $table, string $type, object $order): Builder
    {
        return DB::table($table)->where('order_type', $type)->where('order_id', $order->id);
    }

    /** @return array<int, string> */
    private function perOrderTables(): array
    {
        return array_values(array_filter(self::PER_ORDER, fn (string $table) => Schema::hasTable($table)));
    }
}
