<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pengosongan transaksi uji coba sebelum go-live (keputusan user 31 Okt 2026:
 * semua transaksi spektrumX adalah dummy, tanpa backup). Data master
 * (customer, produk, harga, akun, supplier, user/role, pengaturan) tidak disentuh.
 *
 *   php artisan spektrum:hapus-transaksi januari              → hanya hitung
 *   php artisan spektrum:hapus-transaksi januari --jalankan   → hapus s/d 31 Jan 2026
 *   php artisan spektrum:hapus-transaksi sesudah --jalankan   → hapus SEMUA sisanya + reset plafon & nomor urut
 */
class HapusTransaksiDummy extends Command
{
    protected $signature = 'spektrum:hapus-transaksi {tahap : januari | sesudah} {--jalankan : benar-benar menghapus (tanpa ini hanya menghitung)}';

    protected $description = 'Hapus transaksi dummy spektrumX bertahap (januari, lalu sesudah januari)';

    private const BATAS_JANUARI = '2026-01-31';

    private const ORDERS = [
        'indoor' => ['order_indoor', 'order_indoor_detail', 'order_indoor_id'],
        'outdoor' => ['order_outdoor', 'order_outdoor_detail', 'order_outdoor_id'],
        'artwork' => ['order_artwork', 'order_artwork_detail', 'order_artwork_id'],
    ];

    /** Tabel turunan order dengan kolom (order_type, order_id). Urutan = urutan hapus. */
    private const PER_ORDER = [
        'payment_method_corrections', 'order_payments', 'reprint_logs', 'reprint_requests',
        'order_status_notes', 'sales_order_prints', 'sales_transaction_archives', 'final_sales_discounts',
        'order_financial_adjustments', 'order_rework_requests', 'order_pickup_signatures',
        'order_comments', 'order_comment_reads', 'customer_service_job_sheets', 'laporan_ppn_final_items',
    ];

    /** Transaksi non-order berdasarkan tanggal: tabel => [kolom, jenis]. Urutan = urutan hapus. */
    private const BY_DATE = [
        'am' => ['TgTrans', 'date'],
        'jurnal_manual' => ['tanggal', 'date'],
        'cash_daily_entries' => ['tanggal', 'date'],
        'pengeluaran' => ['tanggal', 'date'],
        'slip_gaji' => ['periode', 'month'],
        'accounting_inventory_counts' => ['tanggal', 'date'],
        'accounting_opening_balances' => ['periode', 'month'],
        'laporan_ppn_final' => ['periode', 'month'],
        'periode_tutup_buku' => ['periode', 'month'],
        'accounting_period_locks' => ['periode', 'month'],
        'accounting_number_sequences' => ['sequence_date', 'date'],
        'order_number_sequences' => ['order_date', 'date'],
    ];

    private bool $run = false;

    /** @var array<string, int> */
    private array $counts = [];

    public function handle(): int
    {
        $tahap = $this->argument('tahap');
        if (! in_array($tahap, ['januari', 'sesudah'], true)) {
            $this->error('Tahap harus "januari" atau "sesudah".');

            return self::INVALID;
        }
        $januari = $tahap === 'januari';
        $this->run = (bool) $this->option('jalankan');

        if ($this->run) {
            $this->warn($januari
                ? 'Akan MENGHAPUS semua transaksi s/d 31 Januari 2026 (tanpa backup).'
                : 'Akan MENGHAPUS SEMUA transaksi yang tersisa, me-nol-kan plafon terpakai & nomor urut (tanpa backup).');
            if ($this->ask('Ketik HAPUS untuk melanjutkan') !== 'HAPUS') {
                $this->info('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        // DDL tidak dipakai, jadi seluruh penghapusan satu transaksi: gagal di tengah = tidak ada yang terhapus.
        DB::transaction(function () use ($januari) {
            $this->orders($januari);
            $this->purchases($januari);
            foreach (self::BY_DATE as $table => [$column, $kind]) {
                $this->apply($table, fn (Builder $q) => $januari
                    ? $q->where($column, '<=', $kind === 'month' ? substr(self::BATAS_JANUARI, 0, 7) : self::BATAS_JANUARI)
                    : $q);
            }
            if (! $januari) {
                // Sisa baris turunan yang ordernya sudah tidak ada (data yatim), termasuk job sheet CS tanpa order.
                $this->apply('order_document_prints', fn (Builder $q) => $q);
                foreach ([...self::PER_ORDER, 'order_documents'] as $table) {
                    $this->apply($table, fn (Builder $q) => $q);
                }
                foreach (self::ORDERS as [, $detailTable]) {
                    $this->apply($detailTable, fn (Builder $q) => $q);
                }
                $this->counts['customer_limits (plafon terpakai → 0)'] = DB::table('customer_limits')->where('Total', '!=', 0)->count();
                if ($this->run) {
                    DB::table('customer_limits')->where('Total', '!=', 0)->update(['Total' => 0]);
                }
            }
        });

        $this->table(['Tabel', $this->run ? 'Dihapus' : 'Akan dihapus'],
            collect($this->counts)->filter()->map(fn ($n, $t) => [$t, number_format($n, 0, ',', '.')])->values()->all());
        $this->info($this->run ? 'Selesai.' : 'Belum ada yang dihapus. Tambahkan --jalankan untuk menghapus.');

        return self::SUCCESS;
    }

    private function orders(bool $januari): void
    {
        foreach (self::ORDERS as $type => [$table, $detailTable, $fk]) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $ids = DB::table($table)->when($januari, fn ($q) => $q->where('TglOrder', '<=', self::BATAS_JANUARI))->pluck('id');
            $this->counts[$table] = ($this->counts[$table] ?? 0) + $ids->count();
            if ($ids->isEmpty()) {
                continue;
            }
            foreach ($ids->chunk(1000) as $chunk) {
                $documentIds = DB::table('order_documents')->where('order_type', $type)->whereIn('order_id', $chunk)->pluck('id');
                $this->apply('order_document_prints', fn (Builder $q) => $q->whereIn('order_document_id', $documentIds));
                foreach (self::PER_ORDER as $related) {
                    $this->apply($related, fn (Builder $q) => $q->where('order_type', $type)->whereIn('order_id', $chunk));
                }
                $this->apply('order_documents', fn (Builder $q) => $q->where('order_type', $type)->whereIn('order_id', $chunk));
                $this->apply($detailTable, fn (Builder $q) => $q->whereIn($fk, $chunk));
                if ($this->run) {
                    // Rujukan nota pengganti antar order (FK ke tabel yang sama).
                    DB::table($table)->whereIn('replacement_order_id', $chunk)->update(['replacement_order_id' => null]);
                    DB::table($table)->whereIn('id', $chunk)->delete();
                }
            }
        }
    }

    private function purchases(bool $januari): void
    {
        $ids = DB::table('accounting_purchases')->when($januari, fn ($q) => $q->where('tanggal', '<=', self::BATAS_JANUARI))->pluck('id');
        foreach (['accounting_purchase_lines', 'accounting_purchase_payments', 'accounting_purchase_returns'] as $child) {
            $this->apply($child, fn (Builder $q) => $q->whereIn('purchase_id', $ids));
        }
        $this->apply('accounting_purchases', fn (Builder $q) => $q->whereIn('id', $ids));
    }

    private function apply(string $table, \Closure $scope): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }
        $query = $scope(DB::table($table));
        $this->counts[$table] = ($this->counts[$table] ?? 0) + ($this->run ? $query->delete() : $query->count());
    }
}
