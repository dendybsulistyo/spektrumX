# SO, DO, Invoice — implementasi 8 September 2026

## Alur yang tersedia

- Nomor IND.2./OUT.1. tetap menjadi nomor order sekaligus SO.
- Pengambilan per item menghasilkan DO. Tombol **Serahkan beberapa item dalam satu DO** menggabungkan beberapa item dari SO yang sama dalam satu penyerahan. Jumlah tiap item dapat dikurangi; item dapat dihapus dari penyerahan tersebut.
- DO menggunakan `DO.1.<tanggal+urutan SO>-<indeks>` untuk outdoor dan kode 2 untuk indoor. Dokumen menyimpan snapshot barang, jumlah, customer, penerima dan tanda tangan.
- Hutang VIP yang telah diproses kasir dapat diambil sebelum lunas. DP reguler tidak dapat diambil sebelum lunas.
- Invoice dibuat otomatis dalam transaksi pembayaran/pelunasan, satu per SO. Outdoor memakai `INV.1.`, indoor `INV.2.`. Artwork lama yang masih memiliki jalur kasir sendiri memakai `INV.3.` untuk mempertahankan cakupan laporan terdahulu.
- Invoice memakai jumlah pembayaran yang benar-benar dicatat, bukan menghitung ulang harga katalog saat dokumen dibuka. Detail barang dan nilai diskon disimpan dalam snapshot; rincian harga historis per item tidak direkonstruksi dari harga katalog sekarang.
- Halaman **Dokumen SO / DO / Invoice** tersedia dari Kasir dan Pengambilan, berisi pencarian nomor, tampilan dokumen dan cetak/simpan PDF melalui browser. Tidak ada pengiriman email/WhatsApp otomatis.
- Cetak otomatis SO tetap mengikuti Pengaturan → Cetak Nota Pesanan / SO. DO dan invoice baru hanya dicetak atas tindakan pengguna.

## Konsistensi transaksi

- Pembayaran mengunci order dan plafon sebelum memproses data terbaru. Status, pembayaran, jurnal dan invoice commit bersama. Kegagalan invoice juga membatalkan pelunasan.
- Pengambilan mengunci order lalu detail. Token permintaan unik menolak penggunaan ulang untuk data berbeda dan mengembalikan DO yang sama untuk kiriman ulang identik. DO, perpindahan jumlah, catatan, tanda tangan dan status selesai berada dalam satu transaksi.
- Urutan kunci tahap produksi disamakan dengan pengambilan: order lalu detail.
- Pembatalan/persetujuan rework membaca ulang data terkunci; persetujuan ulang tidak memicu refund kedua.
- DO dan penerbitan invoice tidak membuat jurnal tambahan. Hutang memakai jurnal piutang pada saat kredit diproses; pelunasan mendebet kas dan mengkredit piutang, tanpa mengulang penjualan.
- Refund DP pada jalur pembatalan rework membalik kewajiban uang muka. Pembatalan hutang yang belum lunas pada jalur tersebut membalik penjualan/piutang dan melepaskan plafon.

## Rekap PPN dan pembatalan

Gunggungan, rekap omzet, serta laporan PPN live/draft/ekspor membaca invoice aktif, memakai tanggal invoice dan nilai snapshot. DP/hutang tanpa invoice tidak masuk. Order batal/nota hangus dikecualikan. Dokumen yang dibatalkan tetap dapat dilihat dengan penanda pembatalan.

Draft harus sesuai invoice aktif saat difinalkan; draft lama perlu disimpan ulang. Laporan yang sudah final tetap merupakan arsip historis dan tidak ditulis ulang otomatis akibat perubahan/pembatalan setelahnya. Koreksi periode final memerlukan rekonsiliasi bagian keuangan.

## Migrasi dan audit lokal

- Migrasi: `2026_09_08_000003_create_order_documents.php`.
- `php artisan orders:backfill-invoices` memeriksa; tambahkan `--apply` untuk menyimpan. Tidak membuat jurnal. Memproses order dengan tanggal pembayaran, mengutamakan bukti pembayaran final; DP/hutang tanpa bukti pelunasan dilewati. Data lama tanpa tanggal pembayaran tidak dipaksakan menjadi invoice.
- Sebanyak **1.686 invoice historis** dibuat di database lokal. Validasi: 1.686 aktif, nol invoice ganda. Gunggungan dan PPN Agustus sama-sama 15 invoice.
- `php artisan orders:audit-accounting` memeriksa seluruh ledger `am`, dikelompokkan per periode/NoTrans. Laporan JSON tersimpan di `storage/logs/order-accounting-audit-*.json`.
- Audit 178 kelompok jurnal: **24 kelompok memiliki selisih debit/kredit**, nol kandidat duplikasi berdasarkan referensi/deskripsi/tanggal/total yang sama. Temuan ini memerlukan penelusuran format impor/bukti asli; tidak otomatis berarti 24 transaksi salah. Tidak ada jurnal historis dihapus atau dikoreksi otomatis.

## Batas verifikasi

Tes otomatis mencakup pengambilan bertahap, kiriman ulang, beberapa item dalam satu DO, hutang VIP, penolakan DP, invoice tunggal, snapshot tetap, pengecualian invoice batal, rollback pengambilan/pelunasan, dan refund DP. Query laporan juga dijalankan pada MySQL lokal. Dialog cetak fisik dan race request paralel di MySQL belum diuji melalui browser. Audit kesamaan total bukan pembuktian menyeluruh ketiadaan duplikasi.

## Konteks demo dan validasi transaksi baru

Pengguna mengonfirmasi bahwa 1.686 invoice backfill adalah data impor Excel untuk demo. Migrasi `2026_09_08_000004_label_historical_demo_invoices.php` hanya memberi metadata asal pada invoice tanpa petugas yang dibuat sebelum audit backfill 8 September 2026 21:25:22; tidak mengubah nilai, tanggal invoice, atau jurnal. Invoice ini tetap masuk penyajian Gunggungan/PPN. Label demo terlihat pada daftar dan tampilan dokumen.

Migrasi `2026_09_08_000005_restore_demo_invoice_dates.php` memperbaiki tanggal invoice demo ke `dibayar_at` order hasil impor. Timestamp pembuatan baris pembayaran tidak dipakai karena mencerminkan waktu import, bukan tanggal transaksi Excel. Backfill dengan opsi `--demo` juga memakai aturan ini.

Invoice baru dari workflow pembayaran memakai `snapshot.origin = operational`. Backfill berikutnya memakai `historical_import`, atau `historical_demo` jika perintah dijalankan dengan `--demo`. Pemanggilan ulang tidak mengganti asal invoice yang sudah ada.

Audit menyertakan jumlah invoice demo serta penjelasan konteksnya. Opsi `orders:audit-accounting --from=YYYY-MM-DD` membatasi tanggal jurnal untuk pemeriksaan periode transaksi baru; filter ini bukan klasifikasi asal jurnal dan tidak otomatis menyembunyikan temuan historis. Jurnal lama belum dinyatakan benar hanya karena bersumber dari demo.

Validasi integrasi tambahan: VIP mengambil 5 + 5 pada dua tanggal, belum masuk rekap invoice; kemudian melunasi, plafon kembali tersedia, satu invoice operasional muncul pada tanggal lunas, jurnal debit/kredit seimbang, dan kiriman ulang tidak menambah invoice/jurnal. Pengujian ini menjalankan service workflow, DO, invoice, dan accounting bersama pada database SQLite terisolasi; belum merupakan pengujian HTTP/browser penuh atau race paralel MySQL.
