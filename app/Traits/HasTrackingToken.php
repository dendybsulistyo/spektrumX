<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * Token acak untuk QR tracking customer (lihat config/tracking.php). Diberikan
 * otomatis saat order dibuat, hanya untuk order mulai tanggal tracking.mulai.
 */
trait HasTrackingToken
{
    public static function bootHasTrackingToken(): void
    {
        static::creating(function ($order) {
            if ($order->tracking_token) {
                return;
            }

            $tanggal = $order->TglOrder ? \Illuminate\Support\Carbon::parse($order->TglOrder)->toDateString() : now()->toDateString();
            if ($tanggal >= config('tracking.mulai')) {
                $order->tracking_token = Str::random(22);
            }
        });
    }
}
