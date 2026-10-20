# Rangkuman Fitur dan Menu SpektrumX

**Laporan untuk Owner**  
**Posisi aplikasi: 19 Oktober 2026**

## 1. Ringkasan Eksekutif

SpektrumX adalah sistem operasional dan keuangan percetakan yang menghubungkan proses dari penerimaan customer sampai pelaporan manajemen. Aplikasi mencakup pembuatan order Indoor dan Outdoor, pembayaran, persetujuan transaksi khusus, alur produksi, penyerahan barang, dokumen SO/DO/Invoice, piutang, pembelian, persediaan, akuntansi, serta analitik kinerja.

Manfaat utama bagi perusahaan:

- Order dapat ditelusuri dari awal sampai selesai.
- Status pekerjaan terlihat lintas bagian secara real time.
- Pembayaran tunai, transfer, QRIS, DP, dan piutang VIP tercatat terstruktur.
- Transaksi khusus seperti diskon, hutang, pembatalan, refund, dan nota pengganti memiliki proses persetujuan.
- Aktivitas pengguna dan perpindahan status tersimpan sebagai jejak audit.
- Keuangan operasional terhubung dengan jurnal dan laporan akuntansi.
- Owner memperoleh ringkasan omzet, transaksi, piutang, produksi, operator, dan printer.
- Hak akses dapat dibatasi sesuai jabatan dan tanggung jawab pengguna.

## 2. Alur Bisnis Utama

Alur kerja utama dalam SpektrumX adalah:

1. Customer dan harga produk dikelola pada Master Data.
2. Order Indoor atau Outdoor dibuat dari transaksi langsung atau lembar kerja Customer Service.
3. File dan informasi pekerjaan diteruskan kepada bagian terkait.
4. Kasir memproses pembayaran lunas, DP, atau hutang VIP.
5. Bila diperlukan, diskon dan hutang di luar ketentuan masuk proses approval.
6. Order bergerak melalui tahap Layout/Desain, Cetak, Finishing, Back Office/QC, Bungkus, dan Pengambilan.
7. Sistem mencatat riwayat operator, waktu, jumlah unit, komentar, serta perubahan status.
8. SO, Delivery Order, Invoice, dan kwitansi tersedia sesuai tahap transaksi.
9. Penerimaan, piutang, potongan, refund, pembelian, dan transaksi lain masuk ke laporan keuangan dan akuntansi.
10. Owner dan manajemen memantau hasil melalui Dashboard dan menu Analitik.

## 3. Dashboard Utama

Dashboard memberikan gambaran cepat kondisi usaha dan produksi.

Fitur yang tersedia:

- Ringkasan jumlah order masuk.
- Status pembayaran: belum bayar, lunas, DP, dan hutang VIP.
- Sebaran order pada setiap tahap produksi.
- Peringatan order terlambat lebih dari 3 x 24 jam.
- Resume dan pagination daftar order terlambat.
- Monitoring 50 order aktif terbaru.
- Order batal tetap ditampilkan di bagian atas sebagai warning merah.
- Filter Indoor, Outdoor, Batal, tanggal, dan pencarian order/customer.
- Riwayat proses per order, termasuk petugas dan catatan aktivitas.

## 4. Master Data

| Menu | Fungsi Bisnis |
|---|---|
| Customer | Mengelola identitas, alamat, kontak, dan profil customer. |
| Customer Aktif | Melihat customer yang masih aktif bertransaksi. |
| Customer VIP & Limit | Menetapkan plafon piutang dan mengontrol pemakaian limit. |
| Data Divisi | Mengelompokkan produk dan pekerjaan berdasarkan divisi produksi. |
| Produk Indoor | Mengelola produk, satuan, rumus harga, harga minimum, dan harga bertingkat. |
| Bahan Artwork | Mengelola produk/bahan artwork dan struktur harganya. |
| Printer Outdoor | Mengelola mesin/printer yang dipakai untuk produksi Outdoor. |
| Bahan Outdoor | Mengelola bahan cetak Outdoor. |
| Standar Harga Outdoor | Mengelola matriks harga bahan dan printer. |
| Harga Khusus VIP | Mengatur harga khusus Outdoor per customer VIP. |
| Jasa Potong Indoor/Artwork | Mengatur komponen biaya tetap jasa potong. |

## 5. Transaksi dan Customer Service

### Order Indoor

- Pembuatan dan pencarian order Indoor.
- Satu order dapat memuat produk Indoor dan Artwork.
- Perhitungan harga berdasarkan luas, jumlah, harga minimum, harga bertingkat, atau rumus khusus.
- Penentuan divisi produksi berdasarkan produk/jenis pekerjaan.
- Status order batal tetap tersimpan dan ditampilkan sebagai warning.
- Pembuatan nota pengganti tanpa menghapus histori nota asal.

### Order Outdoor

- Pembuatan order berdasarkan bahan, printer, ukuran, jumlah, dan finishing.
- Dukungan harga standar dan harga khusus customer VIP.
- Komentar/diskusi pada order untuk koordinasi antarbagian.
- Pembatalan dan nota pengganti dengan histori yang dapat ditelusuri.

### Customer Service dan Lembar Kerja

- Penerimaan permintaan dan informasi pekerjaan dari customer.
- Pembuatan lembar kerja sebelum menjadi order.
- Klaim lembar kerja oleh petugas agar tidak dikerjakan ganda.
- Pengisian informasi file, bahan, ukuran, finishing, dan instruksi pekerjaan.
- Penerusan order dari Customer Service menuju Kasir atau proses berikutnya.
- Antrean pembayaran khusus Customer Service.

## 6. Kasir, Pembayaran, dan Piutang

Fitur kasir meliputi:

- Antrean order yang menunggu pembayaran.
- Pembayaran Tunai, Transfer, QRIS, dan Debit/Card.
- Pembayaran lunas dan uang muka/DP.
- Pelunasan sisa DP.
- Transaksi hutang customer VIP berdasarkan plafon.
- Cicilan dan pelunasan piutang.
- Pembayaran beberapa nota customer sekaligus.
- Ringkasan nominal sebelum pembayaran diproses.
- Pencatatan kasir penerima pembayaran dan waktu transaksi.
- Penyesuaian cara bayar dengan jejak koreksi.
- Rekap kasir per pengguna dan rekap gabungan.

Kontrol piutang yang tersedia:

- Daftar piutang berjalan.
- Pembayaran Piutang per customer.
- Rekap Piutang Customer.
- Piutang per Customer.
- Buku Piutang Customer.
- Rekap Limit Piutang.
- Total Tagihan.
- Penerimaan Piutang Harian.
- Laporan order customer yang masih memiliki piutang.

## 7. Approval dan Kontrol Transaksi Khusus

SpektrumX memisahkan pengajuan dan persetujuan agar transaksi sensitif tidak diproses sepihak.

| Approval | Kontrol yang Diberikan |
|---|---|
| Pembatalan Order | Pengajuan alasan, persetujuan/penolakan, refund, dan histori pembatalan. |
| Diskon | Pengajuan diskon dan keputusan approver. |
| Hutang VIP | Persetujuan hutang, termasuk kasus yang memerlukan otorisasi. |
| Ulang Proses | Pengajuan dan approval pekerjaan yang harus diproses ulang. |
| Potongan Penjualan Akhir | Koreksi nilai penjualan setelah syarat order dan dokumen terpenuhi. |
| Penyesuaian Kas | Pencatatan koreksi kas dengan otorisasi khusus. |
| Penyesuaian Nota/Invoice | Koreksi keuangan order dengan jejak transaksi. |

Pembatalan tidak menghapus sejarah order. Sistem tetap menyimpan nota asal, penerimaan sebelumnya, refund, approver, alasan, dan hubungan dengan nota pengganti bila ada.

## 8. Dashboard Operator dan Produksi

Produksi menggunakan antrean per tahap sehingga setiap bagian hanya melihat pekerjaan yang relevan.

| Tahap/Menu | Fungsi |
|---|---|
| Penerima File | Memantau file dan lembar kerja yang masuk. |
| Layout/Desain | Memproses desain, nama file, dan pekerjaan gabungan. |
| Cetak | Mengelola antrean dan progres pencetakan. |
| Finishing | Mengelola pekerjaan finishing setelah cetak. |
| Back Office/QC | Memeriksa kualitas dan kelengkapan hasil produksi. |
| Bungkus | Mengelola proses pembungkusan dan rekap bungkus Outdoor. |
| Pengambilan Barang | Mencatat serah terima barang dan penyelesaian order. |

Fitur pendukung produksi:

- Progres dapat dicatat per item dan jumlah unit.
- Nama operator dan waktu pengerjaan terekam.
- Order terlambat dapat dipantau dari Dashboard.
- Order batal dikeluarkan dari antrean aktif tetapi tetap tersedia dalam histori.
- Komentar antarbagian membantu koordinasi tanpa mengubah data transaksi.
- Papan Pantau menyediakan tampilan lintas tahap secara read-only.

## 9. Dokumen Penjualan

Menu **Dokumen SO / DO / Invoice** mendukung:

- Sales Order sebagai dokumen awal transaksi.
- Delivery Order saat barang diserahkan.
- Invoice untuk transaksi yang memenuhi ketentuan.
- Invoice customer reguler setelah lunas.
- Invoice customer VIP untuk transaksi hutang sesuai plafon dan aturan perusahaan.
- Kwitansi setelah invoice dinyatakan lunas.
- Pencatatan cetak dokumen dan kontrol cetak ulang.
- Penandaan dokumen void pada order yang dibatalkan.
- Arsip dokumen yang dapat dicari berdasarkan nomor dan customer.

## 10. Keuangan dan Akuntansi

### Kas dan Penerimaan

- Rekap Kas Harian Spektrum.
- Rekap Kasir Harian per User.
- Laporan Kasir Harian.
- Ekspor laporan kas ke Excel.
- Penyesuaian Kas.
- Rekap penerimaan Tunai, Transfer, QRIS, Debit, DP, piutang, refund, dan transaksi lain-lain.
- Pemisahan aktivitas kas tunai dan penerimaan non-tunai.

### Akuntansi Penjualan

- Jurnal otomatis untuk transaksi penjualan dan pembayaran.
- Jurnal Umum.
- Buku Besar.
- Neraca Saldo.
- Buku Kas & Bank.
- Laba Rugi.
- Neraca.
- Perubahan Modal.
- Rekap Omset.
- Gunggungan.
- Jurnal Manual untuk penyesuaian yang berizin.
- Impor jurnal historis.
- Tutup Buku untuk mengunci periode keuangan.

### Pembelian, Supplier, dan Persediaan

- Master Supplier.
- Input dan perubahan transaksi pembelian.
- Pembelian tunai atau hutang supplier.
- Pelunasan hutang supplier.
- Retur pembelian.
- Laporan Pembelian.
- Laporan Hutang Supplier.
- Persediaan dan stok opname.
- Perhitungan serta laporan Harga Pokok Penjualan (HPP).

### Aset dan Pajak

- Pencatatan aset tetap.
- Perhitungan penyusutan aset.
- Rekap PPN.
- Penyimpanan draft laporan PPN.
- Finalisasi laporan PPN agar periode terkendali.
- Pengaturan identitas perusahaan, NPWP, status PKP, dan tarif PPN.

## 11. Laporan Penjualan dan Manajemen

Laporan yang tersedia antara lain:

- Rekap Transaksi Harian.
- Rekap Transaksi Bulanan.
- Laporan Omzet Kertas POD.
- Laporan Omzet CTP.
- Laporan Order Outdoor.
- Rekap Order Belum Di-Invoice.
- Rekap Potongan Penjualan.
- Nota/Invoice per Customer.
- Rekap Order Customer Lunas.
- Rekap Order Customer Piutang.
- Rekap Penerimaan.
- Rekap Omset.
- Laporan operator Indoor.
- Laporan kinerja printer.
- Monitoring transaksi harian, mingguan, bulanan, dan tahunan.

## 12. Analitik dan Monitoring Owner

| Menu | Informasi untuk Manajemen |
|---|---|
| Data Warehouse | Konsolidasi data transaksi untuk analisis bisnis. |
| Monitoring Kinerja | Produktivitas dan aktivitas staf per tahap. |
| Monitoring Transaksi | Tren transaksi berdasarkan periode. |
| Rekap Transaksi Bulanan | Ringkasan transaksi dan nilai bulanan. |
| Kinerja Printer | Omzet dan pemakaian berdasarkan printer. |
| Papan Pantau | Posisi order pada seluruh tahap produksi. |
| Dashboard | Kondisi order, pembayaran, keterlambatan, dan pembatalan terbaru. |

## 13. Kolaborasi Internal

- Chat antar pengguna.
- Status internal seperti status singkat 24 jam.
- Reaksi dan balasan status.
- Mention pengguna atau seluruh pengguna.
- Komentar pada order.
- Ringkasan aktivitas hari ini untuk operator, Customer Service, dan Kasir.
- Pencarian cepat menggunakan nomor order, invoice, atau informasi terkait.
- Avatar dan profil pengguna.

## 14. Administrasi dan Keamanan

- Login dan manajemen sesi pengguna.
- Registrasi publik dinonaktifkan; akun dikelola Admin.
- Role dan permission granular untuk melihat atau mengelola setiap modul.
- Pemisahan hak melihat, menginput, mengubah, menyetujui, dan memfinalkan.
- Aktivasi/nonaktif pengguna.
- Riwayat petugas pada pembayaran dan produksi.
- Jejak approval, penolakan, pembatalan, refund, dan perubahan status.
- Monitor Server dan status OPcache untuk administrator.
- Pengaturan format cetak Sales Order.

## 15. Menu Pendukung/Opsional

Fungsi berikut tersedia pada aplikasi, tetapi kemunculannya dapat bergantung pada konfigurasi menu dan hak akses:

- Preview Cetak/mockup.
- Payroll dan proses pembayaran gaji.
- Pengeluaran operasional.
- Monitor Server.
- Pengaturan cetak Sales Order.
- Pengaturan jasa potong.

## 16. Kesimpulan untuk Owner

SpektrumX telah berfungsi sebagai sistem terpadu untuk tiga kebutuhan utama perusahaan:

1. **Kontrol operasional:** memastikan order bergerak melalui tahap kerja yang jelas dan dapat dipantau.
2. **Kontrol transaksi dan keuangan:** memastikan pembayaran, piutang, pembatalan, pembelian, serta jurnal memiliki histori dan otorisasi.
3. **Informasi manajemen:** menyediakan laporan dan analitik untuk menilai omzet, produktivitas, keterlambatan, piutang, printer, dan kondisi keuangan.

Nilai utama sistem bagi owner adalah tersedianya satu sumber data yang menghubungkan order, orang yang mengerjakan, uang yang diterima, dokumen yang diterbitkan, serta hasil laporan perusahaan. Menu yang terlihat pada setiap pengguna tetap menyesuaikan role dan permission agar akses data sesuai tanggung jawab masing-masing.
