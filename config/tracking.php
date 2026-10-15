<?php

/*
 * Tracking order untuk customer (scan QR di Sales Order → lihat tahap).
 * Halaman & API ada di project terpisah `spektrum-tracking` (Cloudflare Worker).
 * spektrumX hanya MENGIRIM status keluar; server kantor tidak dibuka ke internet.
 */
return [

    // Kosongkan URL/secret untuk mematikan pengiriman (QR tetap tidak dicetak).
    'url' => rtrim((string) env('TRACKING_URL', ''), '/'),

    // Sama dengan TRACKING_SECRET di Cloudflare Worker.
    'secret' => (string) env('TRACKING_SECRET', ''),

    // Order dengan TglOrder sebelum tanggal ini tidak mendapat token/QR.
    'mulai' => env('TRACKING_MULAI', '2026-10-14'),

    // Ukuran & posisi QR di Sales Order (CForm). Diukur dari pojok kiri atas lembar.
    // Cetak QR dinyalakan terpisah dari pengiriman status, setelah posisinya dikalibrasi.
    'qr' => [
        'aktif' => (bool) env('TRACKING_QR_AKTIF', false),
        'ukuran_cm' => (float) env('TRACKING_QR_UKURAN_CM', 2.5),
        // Di bawah tulisan "INVOICE" pada CForm (dikalibrasi dari cetakan 15 Okt 2026).
        'kiri_cm' => (float) env('TRACKING_QR_KIRI_CM', 9.0),
        'atas_cm' => (float) env('TRACKING_QR_ATAS_CM', 1.2),
    ],

    /*
     * Status spektrumX → tahap yang dilihat customer (kode yang diterima Worker).
     * QC & bungkus digabung ke Finishing; "dibayar" masih Order diterima.
     */
    'tahap' => [
        'baru' => 'baru',
        'dibayar' => 'baru',
        'desain' => 'desain',
        'cetak' => 'cetak',
        'finishing' => 'finishing',
        'qc' => 'finishing',
        'bungkus' => 'finishing',
        'siap_diambil' => 'siap_diambil',
        'selesai' => 'selesai',
        'batal' => 'batal',
    ],
];
