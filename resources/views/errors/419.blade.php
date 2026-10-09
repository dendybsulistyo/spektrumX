@include('errors.layout-ruang-cetak', [
    'code' => '419',
    'icon' => '⏳',
    'title' => 'Sesi halaman sudah kedaluwarsa',
    'message' => 'Halaman ini terbuka cukup lama sehingga sesinya habis, jadi data yang dikirim belum tersimpan. Muat ulang halaman, lalu isi dan kirim sekali lagi.',
    'detail' => null,
    'hint' => 'Kalau sudah dimuat ulang tapi masih muncul, coba <b>logout lalu login</b> kembali.',
])
