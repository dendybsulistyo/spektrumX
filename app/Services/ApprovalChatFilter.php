<?php

namespace App\Services;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pesan chat "Pengajuan …" (pembatalan/diskon/hutang) dikirim sebagai chat biasa
 * tanpa relasi ke pengajuannya. Filter ini menyembunyikan pesan pengajuan yang
 * sudah diputuskan: hanya pesan yang order-nya masih menunggu persetujuan yang
 * tetap tampil. Pesan chat biasa tidak tersentuh.
 */
class ApprovalChatFilter
{
    private const MODELS = [
        'indoor' => OrderIndoor::class,
        'outdoor' => OrderOutdoor::class,
        'artwork' => OrderArtwork::class,
    ];

    /**
     * @param  list<string>  $patterns  hasil pendingPatterns()
     */
    public static function apply(Builder $query, array $patterns): Builder
    {
        return $query->where(function (Builder $visible) use ($patterns): void {
            $visible->where('body', 'not like', 'Pengajuan %');

            foreach ($patterns as $pattern) {
                $visible->orWhere('body', 'like', $pattern);
            }
        });
    }

    /**
     * LIKE pattern untuk setiap pengajuan yang masih menunggu. Setelah nomor order
     * selalu ada spasi atau titik, jadi keduanya dipasang agar OUT.1 tidak ikut cocok
     * dengan OUT.10.
     *
     * @return list<string>
     */
    public static function pendingPatterns(): array
    {
        $patterns = [];
        $add = function (string $prefix, string $noOrder) use (&$patterns): void {
            $noOrder = addcslashes($noOrder, '%_\\');
            $patterns[] = "{$prefix}{$noOrder} %";
            $patterns[] = "{$prefix}{$noOrder}.%";
        };

        foreach (self::MODELS as $type => $model) {
            $model::query()->whereNotNull('cancel_requested_at')->whereNull('cancel_approved_at')
                ->pluck('NoOrder')
                ->each(fn ($no) => $add("Pengajuan pembatalan order {$type} ", $no));

            $model::query()->whereNotNull('diskon_requested_at')->whereNull('diskon_approved_at')->whereNull('diskon_rejected_at')
                ->pluck('NoOrder')
                ->each(fn ($no) => $add('Pengajuan diskon % untuk order ', $no));

            $model::query()->whereNotNull('hutang_requested_at')->whereNull('hutang_approved_at')->whereNull('hutang_rejected_at')
                ->pluck('NoOrder')
                ->each(fn ($no) => $add('Pengajuan hutang melebihi plafon untuk order ', $no));
        }

        return $patterns;
    }
}
