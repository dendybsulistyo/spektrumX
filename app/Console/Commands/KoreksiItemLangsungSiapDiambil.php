<?php

namespace App\Console\Commands;

use App\Models\BahanCetakOutdoor;
use App\Models\User;
use App\Services\OrderCorrectionService;
use App\Services\OrderDocumentService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Item yang secara fisik sudah jadi tetapi masih tertahan di antrean (mis.
 * laminasi pada order sebelum fitur "Lewati Layout & Cetak") dipindahkan
 * langsung ke Siap Diambil, lalu status order dihitung ulang.
 *
 *   php artisan spektrum:koreksi-item-siap OUT.1.261103017 --user=email@user              → hanya tampilkan
 *   php artisan spektrum:koreksi-item-siap OUT.1.261103017 --user=email@user --jalankan
 *
 * Tanpa --item: semua item outdoor yang bahannya bertanda Lewati Layout & Cetak.
 */
class KoreksiItemLangsungSiapDiambil extends Command
{
    protected $signature = 'spektrum:koreksi-item-siap {nomor : No. Order} {--item=* : id item (boleh lebih dari satu)} {--user= : email user pencatat} {--jalankan : benar-benar menyimpan (tanpa ini hanya menampilkan)}';

    protected $description = 'Pindahkan item yang tertahan di antrean langsung ke Siap Diambil (koreksi)';

    private const OPEN_STAGES = ['desain', 'cetak', 'finishing', 'qc', 'bungkus'];

    public function handle(OrderCorrectionService $corrections): int
    {
        [$type, $order] = $this->find($this->argument('nomor')) ?? [null, null];
        if (! $order) {
            $this->error('Order '.$this->argument('nomor').' tidak ditemukan.');

            return self::FAILURE;
        }
        $user = User::query()->where('email', (string) $this->option('user'))->first();
        if (! $user) {
            $this->error('Isi --user dengan email user pencatat yang terdaftar.');

            return self::FAILURE;
        }

        $itemIds = array_map('intval', (array) $this->option('item'));
        if (! $itemIds && $type !== 'outdoor') {
            $this->error('Untuk order selain outdoor, sebutkan --item=<id>.');

            return self::FAILURE;
        }
        $skipBahan = BahanCetakOutdoor::query()->where('lewati_layout_cetak', true)->pluck('NoCetak')->all();

        $items = $order->detailItems()
            ->filter(fn ($item) => $itemIds
                ? in_array($item->id, $itemIds, true)
                : in_array(substr((string) $item->KdCtk, 2, 2), $skipBahan, true))
            ->filter(fn ($item) => collect(self::OPEN_STAGES)->sum(fn ($stage) => $item->qtyAt($stage)) > 0)
            ->values();

        if ($items->isEmpty()) {
            $this->info("Tidak ada item {$order->NoOrder} yang perlu dipindahkan.");

            return self::SUCCESS;
        }

        $this->line("{$order->NoOrder} · status sekarang: {$order->status}");
        $this->table(['ID', 'Item', 'Posisi sekarang', 'Menjadi'], $items->map(fn ($item) => [
            $item->id,
            $item->NmFile ?? $item->Judul ?? '-',
            collect(self::OPEN_STAGES)->filter(fn ($stage) => $item->qtyAt($stage) > 0)
                ->map(fn ($stage) => $item->qtyAt($stage).' di '.$stage)->implode(', '),
            collect(self::OPEN_STAGES)->sum(fn ($stage) => $item->qtyAt($stage)) + $item->qtyAt('siap_diambil').' di siap_diambil',
        ])->all());

        if (! $this->option('jalankan')) {
            $this->warn('Belum disimpan. Jalankan ulang dengan --jalankan untuk menyimpan.');

            return self::SUCCESS;
        }
        if (! $this->confirm('Pindahkan item di atas ke Siap Diambil?')) {
            return self::FAILURE;
        }

        // recalculateStatus() mencatat operator tahap dari user yang login.
        Auth::setUser($user);

        foreach ($items as $item) {
            $corrections->moveTo(
                $item,
                'siap_diambil',
                'Item sudah jadi, dipindah langsung ke Siap Diambil melalui command koreksi.',
                $user->id,
            );
        }

        $this->info("{$order->NoOrder} sekarang berstatus: ".$order->fresh()->status);

        return self::SUCCESS;
    }

    /** @return array{0: string, 1: Model}|null */
    private function find(string $number): ?array
    {
        foreach (OrderDocumentService::MODELS as $type => $model) {
            if ($order = $model::query()->where('NoOrder', $number)->first()) {
                return [$type, $order];
            }
        }

        return null;
    }
}
