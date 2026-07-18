# Manual Penggunaan Aplikasi SpektrumX

Dokumen ini adalah panduan penggunaan aplikasi SpektrumX, yaitu aplikasi web based untuk administrasi percetakan. Manual ini disusun berdasarkan fitur yang ada di folder aplikasi `spektrumX`.

## 1. Gambaran Umum

SpektrumX digunakan untuk mengelola proses kerja percetakan dari master data, input order, pembayaran, proses produksi, QC, pengambilan barang, sampai monitoring dan analitik.

Fitur utama aplikasi:

- Dashboard monitoring order.
- Master data customer, produk indoor, harga indoor, harga artwork, bahan outdoor, harga outdoor, operator, dan printer.
- Transaksi order indoor dan order outdoor.
- Antrian kasir untuk pembayaran tunai atau hutang VIP.
- Pipeline produksi: desain, cetak, QC, dan pengambilan barang.
- Invoice order.
- Chat antar user.
- Role dan permission.
- Data Warehouse untuk analisis omzet, customer, dan produk.
- Pengaturan rumus jasa potong.

## 2. Akses Aplikasi

### Login

1. Buka aplikasi SpektrumX.
2. Sistem akan mengarahkan ke halaman login jika belum masuk.
3. Masukkan email dan password.
4. Klik tombol login.
5. Setelah berhasil, sistem membuka halaman `Dashboard`.

Catatan: registrasi publik dinonaktifkan. Akun dibuat oleh admin melalui menu `User`.

### Logout

1. Klik nama pengguna di kanan atas.
2. Klik `Logout`.
3. Sistem akan mengakhiri sesi.

### Profil

1. Klik nama pengguna di kanan atas.
2. Klik `Profil`.
3. Pengguna dapat mengubah informasi profil dan password.

## 3. Role dan Hak Akses

Menu yang terlihat bergantung pada permission role masing-masing user. Contoh role bawaan:

- `Admin`: memiliki seluruh permission.
- `Kasir`: mengakses customer, produk, order, kasir, dan pengambilan barang.
- `Operator/Staff Order`: mengakses customer, produk, harga, bahan, dan pembuatan order.
- `Operator Desain`: mengakses antrian desain.
- `Operator Cetak`: mengakses antrian cetak.
- `Operator QC`: mengakses antrian QC.
- `Owner`: mengakses monitoring, data warehouse, dan beberapa pengaturan.

Jika user tidak melihat menu tertentu, periksa role dan permission pada menu `Role & Akses`.

## 4. Navigasi Utama

Sidebar aplikasi dikelompokkan menjadi beberapa bagian:

- `Dashboard`
- `Master Data`
- `Transaksi`
- `Dashboard Operator`
- `Analitik`
- `Pengaturan`

Di bagian kanan atas terdapat profil user. Di kanan bawah terdapat tombol chat yang dapat dibuka untuk mengirim pesan ke user lain.

## 5. Dashboard

Menu: `Dashboard`

Dashboard menampilkan ringkasan order dan monitoring order terbaru.

Kartu ringkasan:

- `Order Masuk`
- `Menunggu Bayar`
- `Lunas`
- `VIP`
- `Proses Desain`
- `Proses Cetak`
- `Proses QC`
- `Siap Diambil`
- `Selesai`
- `Telat > 3x24 Jam`

Tabel `Monitoring Order Terbaru` menampilkan 30 order terbaru dengan informasi:

- No order.
- Tipe order: Indoor atau Outdoor.
- Customer.
- Waktu masuk.
- Status produksi.
- Status pembayaran.
- Lama proses.
- Petugas kasir, desain, cetak, dan QC.

Order dianggap telat jika belum masuk status `siap_diambil`, `selesai`, atau `batal` setelah lebih dari 72 jam sejak dibuat.

## 6. Status Order

Status produksi yang digunakan:

- `baru`: order baru dibuat dan menunggu pembayaran.
- `desain`: order sudah dibayar dan masuk antrian desain.
- `cetak`: order selesai dari desain dan masuk antrian cetak.
- `qc`: order selesai dari cetak dan masuk antrian QC.
- `siap_diambil`: order lulus QC dan siap diserahkan ke customer.
- `selesai`: barang sudah diserahkan ke customer.
- `batal`: order dibatalkan.

Status pembayaran:

- `belum_bayar`: order belum diproses kasir.
- `lunas`: order dibayar tunai penuh.
- `hutang`: order menjadi piutang customer VIP.

## 7. Master Data Customer

Menu: `Customer`

Customer digunakan saat membuat order indoor dan outdoor.

### Melihat Customer

1. Buka menu `Customer`.
2. Gunakan kolom pencarian untuk mencari berdasarkan nama atau kode customer.
3. Data ditampilkan dalam tabel dan dapat dipaginasi.

### Menambah Customer

1. Buka menu `Customer`.
2. Klik tombol tambah atau `Create`.
3. Isi:
   - `Kode Customer`
   - `Nama Customer`
   - `Alamat`
   - `Kota`
   - `Telepon`
4. Untuk membuat kode otomatis, isi nama customer lalu klik `Buat Otomatis`.
5. Jika customer memiliki fasilitas hutang, centang `Customer VIP`.
6. Isi `Limit Piutang (Rp)` jika customer VIP.
7. Simpan data.

### Mengedit Customer

1. Klik aksi edit pada baris customer.
2. Ubah data yang diperlukan.
3. Simpan perubahan.

### Menghapus Customer

1. Klik aksi hapus pada baris customer.
2. Konfirmasi penghapusan.
3. Sistem juga menghapus data limit customer yang terkait.

### Customer VIP

Customer VIP adalah customer yang memiliki limit piutang. Saat pembayaran, metode `Hutang` hanya tersedia untuk customer VIP dan hanya bisa digunakan jika sisa limit masih mencukupi.

## 8. Master Data Produk Indoor

Menu: `Produk Indoor`

Produk indoor digunakan sebagai item pada order indoor.

### Menambah Produk Indoor

1. Buka `Produk Indoor`.
2. Klik tombol tambah.
3. Isi:
   - `Kode Produk`
   - `Nomor Urut`
   - `Kategori`
   - `Nama Produk`
   - `Harga Standar (Rp)`
   - `Harga Minimum (Rp)`
   - `Satuan`
   - `Cara Hitung Harga Order`
4. Jika perlu, centang `Pakai harga bertingkat sesuai jumlah qty`.
5. Simpan data.

### Cara Hitung Harga Order

Produk indoor memiliki beberapa metode hitung. Di form produk, pilihan ini ditampilkan sebagai kode `isPjLb`.

- Kode area menghitung harga dari panjang x lebar x qty.
- Kode qty menghitung harga dari qty.
- Kode khusus `Jasa Potong` menghitung dari rumus jasa potong.

Untuk produk `Jasa Potong`, order membutuhkan:

- Pisau Turun.
- Jumlah Kertas.
- Tebal Kertas.

## 9. Harga Indoor

Menu: `Harga Indoor`

Menu ini menampilkan daftar harga indoor dari produk indoor, termasuk harga bertingkat jika tersedia.

Gunakan menu ini untuk melihat referensi harga yang dipakai pada order indoor.

## 10. Harga Artwork

Menu: `Harga Artwork`

Harga artwork digunakan sebagai master harga jasa artwork/desain.

Field utama:

- `Kode Produk`
- `Nomor Urut`
- `Kategori`
- `Nama Produk`
- `Harga Standar (Rp)`
- `Harga Minimum (Rp)`
- `Satuan`
- Opsi `Harga dihitung dari Panjang x Lebar`
- Opsi `Pakai harga bertingkat sesuai jumlah qty`

Gunakan aksi tambah, edit, dan hapus sesuai kebutuhan.

## 11. Master Data Outdoor

### Kategori Outdoor

Menu: `Kategori Outdoor`

Digunakan untuk mengelompokkan bahan outdoor.

Field utama:

- `Kode Grup`
- `Nomor Urut`
- `Nama Grup Bahan`

### Bahan Outdoor

Menu: `Bahan Outdoor`

Digunakan sebagai pilihan bahan pada order outdoor.

Field utama:

- `Kode Bahan`
- `Nomor Urut`
- `Kategori`
- `Nama Bahan`
- `Keterangan`
- `Satuan`

### Printer

Menu: `Printer`

Digunakan untuk membangun matrix harga outdoor.

Field utama:

- `Kode Printer`
- `Nomor Urut`
- `Nama Printer`

### Harga Outdoor

Menu: `Harga Outdoor`

Halaman ini menampilkan matrix harga berdasarkan kombinasi bahan dan printer.

1. Buka `Harga Outdoor`.
2. Isi harga pada sel per kombinasi bahan dan printer.
3. Kosongkan sel jika ingin menghapus harga kombinasi tersebut.
4. Klik `Simpan Harga`.

Kode cetak outdoor dibentuk dari gabungan `Kode Printer` dan kode bahan cetak.

## 12. Operator

Menu: `Operator`

Operator digunakan pada order outdoor.

Field utama:

- `Kode Operator`
- `Nama Operator`
- `Operator aktif`

Nonaktifkan operator jika sudah tidak dipakai tetapi datanya tetap ingin disimpan.

## 13. Pengaturan Jasa Potong

Menu: `Jasa Potong`

Menu ini mengatur nilai `X` pada rumus jasa potong.

Rumus:

```text
Ongkos = ((Pisau Turun x Jumlah Kertas x Tebal Kertas) / 10) + X
```

Langkah mengubah nilai:

1. Buka menu `Jasa Potong`.
2. Isi `Nilai X (Rp)`.
3. Klik `Simpan`.

Nilai ini digunakan untuk produk indoor dengan cara hitung `Jasa Potong`.

## 14. Order Indoor

Menu: `Order Indoor`

Order indoor digunakan untuk mencatat pesanan produk indoor.

### Melihat Daftar Order Indoor

1. Buka `Order Indoor`.
2. Gunakan pencarian untuk mencari nomor order atau kode customer.
3. Tabel menampilkan:
   - No order.
   - Tanggal.
   - Customer.
   - Total.
   - Status.
   - Aksi edit atau hapus.

### Membuat Order Indoor

1. Buka `Order Indoor`.
2. Klik `+ Buat Order`.
3. Isi `Tanggal Order`.
4. Pilih customer melalui kolom pencarian.
5. Jika customer belum ada:
   - Ketik nama customer.
   - Pilih `+ Tambah customer baru`.
   - Isi nama, telepon, alamat, dan kota jika diperlukan.
   - Klik `Simpan & Pilih`.
6. Pada bagian `Item Order`, isi:
   - `Produk`
   - `Judul`
   - `Panjang`
   - `Lebar`
   - `Qty`
7. Klik `+ Tambah Item` untuk menambah baris item.
8. Periksa `Estimasi Total`.
9. Klik `Simpan Order`.

Catatan:

- Jika produk tidak membutuhkan ukuran, kolom panjang dan lebar otomatis tidak aktif.
- Jika produk merupakan `Jasa Potong`, isi data potong melalui tombol `Data Jasa Potong`.
- Judul item maksimal 30 karakter.

### Mengedit Order Indoor

1. Buka `Order Indoor`.
2. Klik ikon edit pada order.
3. Ubah tanggal, customer, atau item order.
4. Klik `Simpan Perubahan`.

### Menghapus Order Indoor

1. Buka `Order Indoor`.
2. Klik ikon hapus pada order.
3. Konfirmasi penghapusan.

## 15. Order Outdoor

Menu: `Order Outdoor`

Order outdoor digunakan untuk mencatat pesanan outdoor seperti cetak banner, stiker, dan pekerjaan berbasis bahan outdoor.

### Membuat Order Outdoor

1. Buka `Order Outdoor`.
2. Klik `+ Buat Order`.
3. Isi:
   - `Tanggal Order`
   - `Customer`
   - `Operator`
4. Pada bagian `Item Order`, isi:
   - `Nama File / Desain`
   - `Panjang (cm)`
   - `Lebar (cm)`
   - `Qty`
   - `Kode Cetak`
   - `Bahan`
   - `Catatan Finishing`
5. Klik `+ Tambah Item` jika order memiliki lebih dari satu item.
6. Simpan order.

Catatan:

- Panjang dan lebar outdoor diisi dalam centimeter.
- Harga outdoor dihitung dari harga cetak per meter persegi berdasarkan ukuran dan qty.
- `Kode Cetak`, `Bahan`, dan `Catatan Finishing` bersifat opsional pada form, tetapi sebaiknya diisi agar produksi jelas.

### Mengedit dan Menghapus Order Outdoor

Gunakan ikon edit atau hapus pada tabel `Order Outdoor`. Konfirmasi penghapusan saat diminta.

## 16. Kasir dan Pembayaran

Menu: `Kasir`

Kasir menampilkan order indoor dan outdoor dengan status pembayaran `belum_bayar`.

### Memproses Pembayaran

1. Buka menu `Kasir`.
2. Pilih tab `Indoor` atau `Outdoor`.
3. Klik `Bayar` pada order.
4. Periksa customer, item, dan total tagihan.
5. Pilih metode pembayaran:
   - `Tunai (Lunas)`
   - `Hutang (khusus VIP)`
6. Isi `Catatan` jika perlu.
7. Klik `Proses Pembayaran`.

Setelah pembayaran diproses:

- Status pembayaran menjadi `lunas` atau `hutang`.
- Petugas kasir dan waktu bayar dicatat.
- Status produksi order berubah menjadi `desain`.
- Order berpindah ke `Antrian Desain`.

### Pembayaran Hutang VIP

Metode `Hutang` hanya dapat dipilih jika:

- Customer berstatus VIP.
- Total order tidak melebihi sisa limit piutang.

Jika limit tidak cukup, sistem menolak pembayaran hutang.

### Invoice

Pada halaman pembayaran, klik `Lihat / Cetak Invoice` untuk membuka invoice.

Di halaman invoice:

1. Periksa nomor order, tanggal, customer, item, dan total.
2. Klik `Print Invoice` untuk mencetak.

## 17. Antrian Desain

Menu: `Antrian Desain`

Halaman ini menampilkan order yang sudah dibayar dan menunggu proses desain.

Langkah update:

1. Buka `Antrian Desain`.
2. Pilih tab `Indoor` atau `Outdoor`.
3. Klik `Update Status` pada order.
4. Pilih status aksi:
   - `Selesai`
   - `Lanjut`
5. Isi catatan jika perlu.
6. Klik `Simpan`.

Setelah disimpan:

- Petugas desain dan waktu desain dicatat.
- Status order berubah menjadi `cetak`.
- Order masuk ke `Antrian Cetak`.

## 18. Antrian Cetak

Menu: `Antrian Cetak`

Halaman ini menampilkan order yang sudah selesai dari desain dan siap dicetak.

Langkah update:

1. Buka `Antrian Cetak`.
2. Pilih tab `Indoor` atau `Outdoor`.
3. Klik `Update Status`.
4. Pilih `Selesai` atau `Lanjut`.
5. Isi catatan jika perlu.
6. Klik `Simpan`.

Setelah disimpan:

- Petugas cetak dan waktu cetak dicatat.
- Status order berubah menjadi `qc`.
- Order masuk ke `Antrian QC`.

## 19. Antrian QC

Menu: `Antrian QC`

Halaman ini menampilkan order yang sudah selesai dicetak dan perlu pemeriksaan kualitas.

Langkah update:

1. Buka `Antrian QC`.
2. Pilih tab `Indoor` atau `Outdoor`.
3. Klik `Update Status`.
4. Pilih `Selesai` atau `Lanjut`.
5. Isi catatan jika perlu.
6. Klik `Simpan`.

Setelah disimpan:

- Petugas QC dan waktu QC dicatat.
- Status order berubah menjadi `siap_diambil`.
- Order masuk ke `Pengambilan Barang`.

## 20. Pengambilan Barang

Menu: `Pengambilan Barang`

Halaman ini menampilkan order dengan status `siap_diambil`.

Langkah serah terima:

1. Buka `Pengambilan Barang`.
2. Pilih tab `Indoor` atau `Outdoor`.
3. Periksa customer dan status pembayaran.
4. Klik `Serahkan Barang`.
5. Konfirmasi bahwa barang sudah diserahkan ke customer.

Setelah dikonfirmasi:

- Status order berubah menjadi `selesai`.
- Waktu pengambilan dicatat.

## 21. Role & Akses

Menu: `Role & Akses`

Role digunakan untuk menentukan menu dan aksi yang bisa digunakan user.

### Menambah Role

1. Buka `Role & Akses`.
2. Klik tambah role.
3. Isi:
   - `Kode Role`
   - `Nama Role`
4. Centang permission yang diperlukan.
5. Simpan role.

### Mengedit Role

1. Klik edit pada role.
2. Ubah nama role atau permission.
3. Simpan perubahan.

### Menghapus Role

Role hanya dapat dihapus jika belum dipakai oleh user.

## 22. User

Menu: `User`

### Menambah User

1. Buka menu `User`.
2. Klik tambah user.
3. Isi:
   - `Nama`
   - `Email`
   - `Password`
   - `Role`
4. Simpan user.

### Mengedit User

1. Klik edit pada user.
2. Ubah nama, email, password, atau role.
3. Kosongkan password jika tidak ingin mengubah password.
4. Simpan perubahan.

### Menghapus User

1. Klik hapus pada user.
2. Konfirmasi penghapusan.

Catatan: user tidak dapat menghapus akunnya sendiri.

## 23. Data Warehouse

Menu: `Data Warehouse`

Data Warehouse digunakan untuk melihat analisis transaksi historis.

Informasi yang tersedia:

- Total transaksi.
- Total omzet.
- Total customer.
- Trend omzet bulanan.
- Top customer.
- Top produk berdasarkan qty.
- Top produk berdasarkan omzet.
- Produk dengan qty terendah.
- Status pembayaran.
- Ringkasan customer VIP dan non-VIP.

Filter yang tersedia:

- Tanggal awal.
- Tanggal akhir.
- Pencarian customer.
- Status customer: semua, aktif, atau tidak aktif.

Catatan: beberapa breakdown produk/customer item berasal dari tabel transaksi historis dan tidak selalu mengikuti filter tanggal yang sama dengan KPI utama.

## 24. Chat Antar User

Setiap halaman aplikasi menampilkan tombol chat melayang di kanan bawah.

### Mengirim Pesan

1. Klik tombol chat.
2. Pilih user tujuan.
3. Tulis pesan pada kolom `Tulis pesan...`.
4. Klik `Kirim` atau tekan Enter.

### Membaca Pesan

- Angka merah pada tombol chat menunjukkan jumlah pesan belum dibaca.
- Daftar user menampilkan pesan terakhir dan jumlah unread per user.
- Membuka percakapan akan menandai pesan dari user tersebut sebagai sudah dibaca.

Panel chat dapat digeser posisinya. Posisi terakhir disimpan di browser.

## 25. Alur Operasional Utama

### Alur Order Normal

1. Staff membuat order indoor atau outdoor.
2. Order masuk status `baru` dan `belum_bayar`.
3. Kasir memproses pembayaran.
4. Order masuk `Antrian Desain`.
5. Operator desain menyelesaikan tahap desain.
6. Order masuk `Antrian Cetak`.
7. Operator cetak menyelesaikan tahap cetak.
8. Order masuk `Antrian QC`.
9. Operator QC menyelesaikan pemeriksaan.
10. Order masuk `Pengambilan Barang`.
11. Barang diserahkan ke customer.
12. Status order menjadi `selesai`.

### Alur Order Hutang VIP

1. Pastikan customer sudah ditandai sebagai `Customer VIP`.
2. Pastikan limit piutang cukup.
3. Buat order seperti biasa.
4. Pada kasir, pilih metode `Hutang (khusus VIP)`.
5. Sistem mencatat nilai piutang berjalan customer.
6. Order tetap lanjut ke produksi setelah pembayaran hutang diproses.

## 26. Troubleshooting

### Menu tidak muncul

Periksa role user di menu `User`, lalu periksa permission role di `Role & Akses`.

### Tidak bisa membuat order karena customer tidak ada

Untuk order indoor, gunakan fitur quick add di kolom customer. Untuk order outdoor, tambahkan customer terlebih dahulu melalui menu `Customer`.

### Metode hutang tidak bisa dipilih

Pastikan customer adalah VIP dan memiliki sisa limit piutang yang cukup.

### Harga order tidak sesuai

Periksa master data:

- Produk indoor.
- Harga minimum dan harga standar.
- Cara hitung harga produk.
- Harga outdoor matrix.
- Nilai X pada `Jasa Potong`.

### Order tidak muncul di antrian desain

Order baru harus diproses pembayaran terlebih dahulu di menu `Kasir`. Setelah pembayaran berhasil, order masuk ke antrian desain.

### Order tidak muncul di pengambilan barang

Order harus melewati desain, cetak, dan QC. Setelah QC selesai, status berubah menjadi `siap_diambil`.

### Chat tidak memperbarui pesan

Tutup dan buka kembali panel chat atau refresh halaman. Chat menggunakan polling berkala, sehingga pesan dapat muncul setelah beberapa detik.

## 27. Glosarium

- `Order Indoor`: order untuk produk indoor.
- `Order Outdoor`: order untuk produk outdoor.
- `No Order`: nomor unik pesanan.
- `Customer VIP`: customer dengan fasilitas limit piutang.
- `Limit Piutang`: batas maksimal hutang customer VIP.
- `Harga Bertingkat`: harga berbeda berdasarkan batas qty.
- `Jasa Potong`: produk khusus dengan rumus potong.
- `Kasir`: tahap pembayaran order.
- `Desain`: tahap pengerjaan desain.
- `Cetak`: tahap produksi cetak.
- `QC`: quality control sebelum barang siap diambil.
- `Pengambilan`: tahap serah terima barang ke customer.
- `Permission`: hak akses spesifik untuk menu atau aksi tertentu.

