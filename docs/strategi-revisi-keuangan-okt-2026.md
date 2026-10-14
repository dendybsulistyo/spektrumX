# Strategi Revisi Keuangan — catatan "Tolong revisi sudah jadi berubah lagi"

Disusun 13 Oktober 2026 dari catatan tulisan tangan keuangan (10 poin).
**Belum ada kode yang diubah untuk poin 4–10.** Dokumen ini berisi tafsiran tiap poin, kondisi sistem saat ini (sudah dicek ke kode dan data server), rencana perbaikan, dan pertanyaan yang perlu dijawab keuangan.

---

## Ringkasan status

| No | Catatan | Status |
|---|---|---|
| 1 | Penyesuaian Kas: Debet/Kredit hilang, berubah jadi Sumber Dana | ✅ **Selesai** (Posisi Debet/Kredit kembali, Sumber Dana dihapus) |
| 2 | Potongan penjualan berubah lagi, nominal tidak masuk | 🟡 **Hampir selesai**, menunggu 1 keputusan (lihat poin 2) |
| 3 | Beda menu → beda akun di rekap penerimaan per user | ✅ **Selesai** (tercatat atas akun yang login, akun bersama dinonaktifkan, 1 akun 1 komputer) |
| 4 | Penyesuaian metode pembayaran: harusnya khusus tunai, tagihan tidak masuk | 🔴 Belum — penyebab sudah ketemu |
| 5 | Rekap Order Belum Di-Invoice: periode per bulan | 🔴 Belum — mudah |
| 6 | Invoice yang dikeluarkan belum masuk Rekap Transaksi Harian & Penerimaan Piutang | 🔴 Belum — penyebab sudah ketemu (sama dengan poin 9) |
| 7 | Nota batal: sisa tagihan harus 0/– atau tidak masuk Invoice per Customer | 🟠 Belum — **perlu contoh** dari keuangan |
| 8 | Nota/Invoice per Customer: hanya invoice, pakai tanggal invoice | 🔴 Belum — perlu 1 kepastian |
| 9 | Rekap Transaksi Harian: berdasarkan invoice yang disimpan/dilunasi | 🔴 Belum — penyebab sudah ketemu |
| 10 | Kolom nota dibuat per nota, bukan per item | 🟠 Belum — **perlu kepastian laporan mana** |

---

## Poin 1 — Penyesuaian Kas ✅

- Pilihan **Posisi Debet/Kredit** sudah dikembalikan; kolom **Sumber Dana** dihapus (sumber dana & rekening bank hanya 1).
- Jurnal otomatis mengikuti posisi. Sudah di server.

## Poin 2 — Potongan penjualan 🟡

Sudah dikerjakan: angka potongan **hanya di kolom Kredit**, muncul **saat ada uang bergerak**:
- Nota **DP/hutang** → muncul di hari **pelunasan** (Debet = tagihan sebelum potongan, Kredit = potongan).
- Nota **sudah lunas** → muncul di hari itu sebagai **Refund potongan penjualan** (Kredit).

**Keputusan yang ditunggu:**
1. Di hari potongan diberikan pada nota DP/hutang, perlu **baris keterangan tanpa angka** ("…akan dikurangkan saat pelunasan")?
2. Pencegahan agar **Penyesuaian Kas tidak dipakai untuk mencatat potongan**: cukup keterangan di form, atau ditambah penolakan otomatis bila alasan berisi kata "potongan"/"diskon"?

## Poin 3 — Akun di rekap per user ✅

- Penyesuaian Kas dulu selalu tercatat atas nama Yovita → sekarang atas nama **akun yang login**.
- Akun bersama (admin keuangan, Test Admin, akuntansi) **dinonaktifkan**; **1 akun hanya bisa login di 1 komputer**.
- Izin **Input Penyesuaian Kas** sekarang terpisah; akun aktif yang punya hanya **Yovita**.

---

## Poin 4 — Koreksi Metode Pembayaran (menu Penyesuaian Nota Order / DP)

**Tafsiran:** menu ini untuk mengoreksi pembayaran yang **tercatat tunai** padahal sebenarnya QRIS/Transfer/Debit. Jadi yang tampil seharusnya **hanya pembayaran tunai**, termasuk pembayaran **tagihan (hutang VIP)**.

**Kondisi sekarang (dicek di kode):**
- Daftar menampilkan **semua cara bayar** (tunai, QRIS, transfer, debit) — tidak disaring tunai.
- Hanya nota berstatus **DP & Lunas** yang diambil. Nota **hutang yang masih dicicil** tidak ikut → pembayaran cicilan tagihan **tidak muncul**.

**Rencana:**
- Saring hanya pembayaran **tunai**.
- Ikutkan nota **hutang** (cicilan/pelunasan tagihan).
- Pastikan jurnal koreksi untuk pembayaran tagihan tetap benar (D Bank, K Kas — sama seperti koreksi lain) dan 3 laporan kas ikut berubah.

**Risiko:** kecil. Koreksi yang sudah pernah dibuat tetap tampil di Histori.

**Pertanyaan:** pembayaran yang **sudah pernah dikoreksi** (misal tunai → QRIS) apakah perlu tetap tampil agar bisa dikoreksi ulang/dibatalkan, atau cukup di Histori?

## Poin 5 — Rekap Order Belum Di-Invoice: periode per bulan

**Kondisi:** filter pakai **Dari tanggal – Sampai tanggal**.
**Rencana:** ganti jadi pilihan **Bulan** (seperti Rekap Transaksi Bulanan), bawaan bulan ini.
**Pertanyaan:** perlu tetap ada pilihan "semua bulan" / rentang lebih dari 1 bulan, atau cukup per bulan?

## Poin 6 & 9 — Rekap Transaksi Harian berdasarkan INVOICE

**Tafsiran:** Rekap Transaksi Harian harus berisi **invoice yang disimpan/dilunasi pada tanggal itu** (lunas dan tagihan), bukan order yang **dibuat** pada tanggal itu.

**Penyebab (dicek ke kode & data server):**
- Laporan saat ini menyaring berdasarkan **tanggal order** (TglOrder).
- Sejak 1 Oktober, **200 dari 466 invoice (43%)** terbit di **tanggal berbeda** dari tanggal ordernya (order dibuat hari ini, dibayar besok/lusa). Invoice seperti ini masuk ke tanggal order, **bukan** tanggal invoice → di hari invoice dikeluarkan, "tidak masuk".

**Rencana:**
- Dasar tanggal diganti ke **tanggal invoice / tanggal diproses kasir**:
  - Nota **lunas** → tanggal invoice terbit (= tanggal dilunasi).
  - Nota **tagihan (hutang VIP)** → tanggal disimpan kasir sebagai hutang. *(Catatan: invoice resmi baru terbit saat tagihan lunas; jadi untuk tagihan dipakai tanggal diproses kasir.)*
- **Rekap Transaksi Bulanan** ikut disesuaikan agar total bulanan = jumlah harian.
- Penerimaan Piutang Harian: lihat pertanyaan di bawah.

**Penting:** dulu bang berpesan *"Rekap Transaksi Harian dibiarkan saja, jangan disentuh"*. Poin 9 ini mengubah dasar laporan tersebut → **perlu konfirmasi bang** bahwa perubahan ini sekarang memang diminta.

**Risiko:** angka laporan hari-hari yang sudah lewat akan **bergeser** (transaksi pindah dari tanggal order ke tanggal invoice). Total per bulan bisa ikut bergeser di akhir bulan. Laporan yang sudah dicetak tidak akan sama lagi.

**Pertanyaan:**
1. Untuk **tagihan (hutang VIP)**, tanggal yang dipakai: saat **disimpan kasir sebagai hutang** (usul saya), atau saat **tagihan dilunasi**?
2. Untuk **DP**: masuk Rekap Transaksi Harian di tanggal **DP dibayar**, atau baru di tanggal **pelunasan** (saat invoice terbit)?
3. "Invoice belum masuk **Penerimaan Piutang**": yang dimaksud invoice **tagihan baru** (customer VIP ambil barang, belum bayar) harus tampil di Penerimaan Piutang Harian di hari itu? Saat ini menu itu hanya menampilkan **uang piutang yang diterima** hari itu + sisa piutang.

## Poin 7 — Nota/Invoice batal di Invoice per Customer

**Kondisi:** laporan Nota/Invoice per Customer **sudah mengecualikan** order berstatus batal. Jadi kemungkinan yang dimaksud adalah kasus lain, misalnya:
- nota yang **menunggu persetujuan batal** (belum disetujui), atau
- **nota lama yang dihanguskan** untuk nota pengganti, atau
- laporan lain ("customer umum belum lunas").

**Rencana:** tunggu contoh, lalu: sisa tagihan nota batal = **0 / "–"**, atau nota batal **tidak ditampilkan** sama sekali.

**Pertanyaan (perlu contoh):**
1. **Nomor nota** yang batal tapi masih tampil dengan sisa tagihan.
2. "**Customer umum belum lunas**" itu menu/laporan apa? (nama menu atau foto layar)
3. Pilih: nota batal **ditampilkan dengan sisa 0/–**, atau **tidak ditampilkan**?

## Poin 8 — Nota/Invoice per Customer: hanya invoice

**Tafsiran:**
- Yang masuk hanya **invoice** (nota yang sudah diproses kasir), **bukan** order yang belum dibayar.
- Kolom **Tgl Order** dan **No. Order** tidak perlu ditampilkan.
- **Tanggal** = tanggal invoice disimpan/dilunasi; filter periode juga berdasarkan tanggal itu.

**Kondisi:** laporan saat ini memuat semua order (termasuk belum bayar & DP), menampilkan tanggal & nomor order, dan filter periode pakai tanggal order.

**Rencana:** ubah sesuai tafsiran di atas.

**Pertanyaan:** catatan menyebut "nota yang **belum lunas** tidak boleh masuk". Apakah **tagihan VIP (hutang)** — yang memang belum lunas — **ikut ditampilkan** (karena itu justru piutang yang perlu dipantau per customer), atau benar-benar **hanya invoice lunas**?

## Poin 10 — Kolom Nota per nota, bukan per item

**Tafsiran:** di sebuah laporan, satu nota dengan beberapa item tampil berulang per item; diminta **1 baris per nota**.

**Pertanyaan (perlu kepastian):** laporan **mana**? Kemungkinan Rekap Transaksi Harian (saat ini menampilkan rincian per item, Panjang×Lebar×Qty), Rekap Transaksi Bulanan, atau Laporan Order. Kalau dibuat per nota, **rincian item (ukuran, qty, harga) apakah tetap perlu**, atau cukup total per nota?

---

## Usulan urutan pengerjaan

1. **Poin 4** (koreksi metode bayar) — penyebab jelas, dampak kecil.
2. **Poin 5** (filter per bulan) — mudah.
3. **Poin 6 & 9** (Rekap Harian + Bulanan berbasis invoice) — setelah konfirmasi bang & jawaban pertanyaan tanggal tagihan/DP.
4. **Poin 8** (Invoice per Customer) — setelah kepastian tagihan VIP ikut atau tidak.
5. **Poin 7 & 10** — setelah ada contoh / kepastian laporan.
6. **Poin 2** — setelah 2 keputusan potongan.

Setiap perubahan: diuji dengan data server (read-only), dibandingkan angka sebelum/sesudah, dan bisa di-revoke.

---

## Daftar pertanyaan untuk keuangan (ringkas)

1. **Potongan (poin 2):** perlu baris keterangan tanpa angka di hari potongan diberikan (nota DP/hutang)? Dan Penyesuaian Kas perlu menolak alasan berisi "potongan/diskon"?
2. **Koreksi metode bayar (poin 4):** pembayaran yang sudah dikoreksi tetap tampil di daftar, atau cukup di Histori?
3. **Rekap Order Belum Di-Invoice (poin 5):** cukup per bulan, atau perlu juga rentang/semua?
4. **Rekap Transaksi Harian (poin 6 & 9):**
   a. Tagihan VIP masuk di tanggal **disimpan kasir** atau tanggal **dilunasi**?
   b. DP masuk di tanggal **DP dibayar** atau tanggal **pelunasan**?
   c. "Invoice belum masuk Penerimaan Piutang" maksudnya apa (tagihan baru perlu tampil)?
   d. **(untuk bang)** konfirmasi Rekap Transaksi Harian boleh diubah (dulu dipesan jangan disentuh).
5. **Nota batal (poin 7):** contoh nomor nota, menu "customer umum belum lunas" yang mana, dan nota batal ditampilkan 0/– atau disembunyikan?
6. **Invoice per Customer (poin 8):** tagihan VIP (belum lunas) ikut ditampilkan atau hanya lunas?
7. **Kolom per nota (poin 10):** laporan yang mana, dan rincian item masih perlu atau cukup total per nota?
