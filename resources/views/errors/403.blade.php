@php
    // Pesan khusus dari abort(403, '...') ditampilkan; teks bawaan "Forbidden"/"This action is unauthorized." diabaikan.
    $raw = trim((string) ($exception?->getMessage() ?? ''));
    $detail = in_array($raw, ['', 'Forbidden', 'This action is unauthorized.', 'Unauthorized'], true) ? null : $raw;
@endphp
@include('errors.layout-ruang-cetak', [
    'code' => '403',
    'icon' => '🔒',
    'title' => 'Halaman ini belum bisa dibuka',
    'message' => 'Akun Anda belum punya akses ke halaman ini. Tidak ada yang rusak — hanya saja menu ini diatur khusus untuk peran tertentu.',
    'detail' => $detail,
    'hint' => 'Kalau halaman ini memang dibutuhkan untuk pekerjaan Anda, minta <b>admin</b> untuk menambahkan aksesnya di menu <b>Pengaturan → Role</b>.',
])
