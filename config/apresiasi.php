<?php

/*
 * Maskot ucapan terima kasih tiap jam (muncul dari pojok kiri bawah).
 * Matikan dengan APRESIASI_AKTIF=false di .env, lalu php artisan config:cache.
 */
return [
    'aktif' => (bool) env('APRESIASI_AKTIF', true),

    // Semua nama disebut (keputusan user 2 Nov 2026); batas ini hanya pengaman agar gelembung tidak meluber.
    'maks_nama' => 60,
];
