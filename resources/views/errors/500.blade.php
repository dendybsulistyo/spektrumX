@include('errors.layout-ruang-cetak', [
    'code' => '500',
    'icon' => '🛠️',
    'title' => 'Ada kendala di sistem',
    'message' => 'Maaf, terjadi kesalahan saat memproses permintaan ini. Kejadiannya sudah tercatat otomatis. Coba ulangi beberapa saat lagi.',
    'detail' => null,
    'hint' => 'Kalau terus berulang, kirim <b>screenshot halaman ini</b> ke admin supaya bisa segera dicek.',
])
