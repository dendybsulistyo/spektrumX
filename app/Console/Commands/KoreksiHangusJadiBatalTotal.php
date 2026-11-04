<?php

namespace App\Console\Commands;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Models\User;
use App\Services\OrderCancellationRefund;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Koreksi nota yang terlanjur disetujui "hangus, ganti nota" padahal nota
 * penggantinya dibuat sebagai order biasa. Nota lama dipindah ke jalur
 * Batal Total memakai OrderCancellationRefund (jurnal & refund sama persis
 * dengan tombol Batal Total), lalu hilang dari tab Nota Hangus.
 *
 *   php artisan spektrum:koreksi-hangus-batal IND.2.261103072 --refund=47800 --cara=tunai --user=email@user           → hanya tampilkan
 *   php artisan spektrum:koreksi-hangus-batal IND.2.261103072 --refund=47800 --cara=tunai --user=email@user --jalankan
 */
class KoreksiHangusJadiBatalTotal extends Command
{
    protected $signature = 'spektrum:koreksi-hangus-batal {nomor : No. Order yang hangus} {--refund= : nominal uang yang dikembalikan} {--cara=tunai : tunai/transfer} {--referensi= : no. referensi transfer} {--user= : email user pencatat refund} {--keterangan= : tambahan catatan} {--jalankan : benar-benar menyimpan (tanpa ini hanya menampilkan)}';

    protected $description = 'Ubah nota hangus (tanpa nota pengganti resmi) menjadi Batal Total beserta refund & jurnalnya';

    private const MODELS = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];

    public function handle(OrderCancellationRefund $refundService): int
    {
        [$type, $order] = $this->find($this->argument('nomor')) ?? [null, null];
        if (! $order) {
            $this->error('Order '.$this->argument('nomor').' tidak ditemukan.');

            return self::FAILURE;
        }
        if ($order->status !== 'batal' || ! $order->invoice_voided_at) {
            $this->error("{$order->NoOrder} bukan nota hangus (status {$order->status}).");

            return self::FAILURE;
        }
        if ($order->replacement_order_id) {
            $this->error("{$order->NoOrder} sudah punya nota pengganti resmi; koreksi manual.");

            return self::FAILURE;
        }

        $user = User::query()->where('email', $this->option('user'))->first();
        if (! $user) {
            $this->error('User pencatat (--user) tidak ditemukan.');

            return self::FAILURE;
        }

        $input = ['refund_amount' => $this->option('refund'), 'refund_method' => $this->option('cara'), 'refund_reference' => $this->option('referensi')];
        try {
            $refund = OrderCancellationRefund::validated($input, $order);
        } catch (ValidationException $e) {
            $this->error(collect($e->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }

        $this->info("{$order->NoOrder} ({$type}) · {$order->status_bayar} · dibayar Rp ".$this->rupiah(OrderCancellationRefund::refundable($order))
            .' · piutang Rp '.$this->rupiah(OrderCancellationRefund::openReceivable($order)));
        $this->line('Refund Rp '.$this->rupiah($refund['refund']).' ('.($refund['cara_bayar'] ?? '-').') dicatat oleh '.$user->name.', bertanggal hari ini.');

        if (! $this->option('jalankan')) {
            $this->info('Belum ada yang disimpan. Tambahkan --jalankan untuk menyimpan.');

            return self::SUCCESS;
        }
        if (! $this->confirm('Lanjutkan koreksi?')) {
            return self::FAILURE;
        }

        Auth::setUser($user);

        $note = DB::transaction(function () use ($order, $type, $refund, $refundService, $user) {
            $order = $order->newQuery()->lockForUpdate()->findOrFail($order->id);
            $order->update(['invoice_voided_at' => null]);
            $note = $refundService->process($order, $type, $refund);

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'pembatalan',
                'action' => 'disetujui',
                'catatan' => trim('Dikoreksi menjadi batal total (nota pengganti dibuat sebagai order biasa). '.$note.' '.$this->option('keterangan')),
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            return $note;
        }, attempts: 3);

        $this->info("{$order->NoOrder} sekarang batal total. {$note}");

        return self::SUCCESS;
    }

    /** @return array{0: string, 1: Model}|null */
    private function find(string $nomor): ?array
    {
        foreach (self::MODELS as $type => $model) {
            if ($order = $model::query()->where('NoOrder', $nomor)->first()) {
                return [$type, $order];
            }
        }

        return null;
    }

    private function rupiah(float $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}
