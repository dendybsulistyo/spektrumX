<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /**
     * Semua permission yang tersedia di aplikasi, dikelompokkan per modul.
     * Dipakai untuk render checkbox di form Role, dan untuk validasi.
     *
     * @var array<string, array<string, string>>
     */
    public const PERMISSION_GROUPS = [
        'Customer' => [
            'customers.view' => 'Lihat data customer',
            'customers.manage' => 'Tambah/ubah/hapus customer',
        ],
        'Produk' => [
            'produk.view' => 'Lihat data produk',
            'produk.manage' => 'Tambah/ubah/hapus produk',
        ],
        'Kategori Produk Indoor' => [
            'kategori-produk-indoor.view' => 'Lihat data divisi (dipakai bareng Indoor & Artwork)',
            'kategori-produk-indoor.manage' => 'Tambah/ubah/hapus divisi (dipakai bareng Indoor & Artwork)',
        ],
        'Bahan Artwork' => [
            'harga-artwork.view' => 'Lihat harga artwork',
            'harga-artwork.manage' => 'Tambah/ubah/hapus harga artwork',
        ],
        'Daftar Harga Indoor' => [
            'kategori.view' => 'Lihat daftar harga indoor',
            'kategori.manage' => 'Tambah/ubah/hapus daftar harga indoor',
        ],
        'Operator' => [
            'operators.view' => 'Lihat data operator',
            'operators.manage' => 'Tambah/ubah/hapus operator',
        ],
        'Printer' => [
            'printers.view' => 'Lihat data printer',
            'printers.manage' => 'Tambah/ubah/hapus printer',
        ],
        'Printer Outdoor' => [
            'printer-outdoor.view' => 'Lihat data printer outdoor',
            'printer-outdoor.manage' => 'Tambah/ubah/hapus printer outdoor',
        ],
        'Bahan Cetak Outdoor' => [
            'bahan-cetak-outdoor.view' => 'Lihat data bahan cetak outdoor',
            'bahan-cetak-outdoor.manage' => 'Tambah/ubah/hapus bahan cetak outdoor',
        ],
        'Order Indoor' => [
            'order-indoor.view' => 'Lihat order',
            'order-indoor.manage' => 'Buat/ubah/hapus order',
            'order-indoor.approve-cancel' => 'Setujui/tolak pengajuan pembatalan order indoor',
        ],
        'Order Outdoor' => [
            'order-outdoor.view' => 'Lihat order outdoor',
            'order-outdoor.manage' => 'Buat/ubah/hapus order outdoor',
            'order-outdoor.approve-cancel' => 'Setujui/tolak pengajuan pembatalan order outdoor',
        ],
        'Order Artwork' => [
            'order-artwork.view' => 'Lihat order artwork',
            'order-artwork.manage' => 'Buat/ubah/hapus order artwork',
            'order-artwork.approve-cancel' => 'Setujui/tolak pengajuan pembatalan order artwork',
        ],
        'File Monitor' => [
            'file-monitor.view' => 'Lihat monitoring file masuk (order indoor/outdoor/artwork)',
        ],
        'Kasir' => [
            'kasir.view' => 'Lihat antrian kasir',
            'kasir.manage' => 'Proses pembayaran order',
            'kasir.approve-diskon' => 'Setujui/tolak pengajuan diskon nota kasir',
            'kasir.approve-hutang' => 'Setujui/tolak pengajuan hutang customer VIP yang melebihi plafon',
            'kasir.replacement.manage' => 'Buat nota pengganti',
        ],
        'Operator Desain' => [
            'order-desain.view' => 'Lihat antrian desain',
            'order-desain.manage' => 'Update status desain',
            'order-desain.nmfile-manage' => 'Ubah nama file desain outdoor (khusus Operator File)',
        ],
        'Operator Cetak' => [
            'order-cetak.view' => 'Lihat antrian cetak',
            'order-cetak.manage' => 'Update status cetak',
        ],
        'Operator Finishing' => [
            'order-finishing.view' => 'Lihat antrian finishing',
            'order-finishing.manage' => 'Update status finishing',
        ],
        'Operator QC' => [
            'order-qc.view' => 'Lihat antrian QC',
            'order-qc.manage' => 'Update status QC',
        ],
        'Operator Bungkus' => [
            'order-bungkus.view' => 'Lihat antrian bungkus',
            'order-bungkus.manage' => 'Update status bungkus',
        ],
        'Pengambilan Barang' => [
            'pengambilan.view' => 'Lihat daftar pengambilan barang',
            'pengambilan.manage' => 'Konfirmasi serah terima barang',
        ],
        'Pembatalan & Ulang Proses' => [
            'order-rework.approve' => 'Setujui/tolak pengajuan pembatalan atau ulang proses order',
        ],
        'Bahan Outdoor' => [
            'bahan-outdoor.view' => 'Lihat bahan outdoor',
            'bahan-outdoor.manage' => 'Tambah/ubah/hapus bahan outdoor',
        ],
        'Kategori Bahan Outdoor' => [
            'kategori-bahan-outdoor.view' => 'Lihat kategori bahan outdoor',
            'kategori-bahan-outdoor.manage' => 'Tambah/ubah/hapus kategori bahan outdoor',
        ],
        'Harga Cetak Outdoor' => [
            'harga-cetak-outdoor.view' => 'Lihat harga cetak outdoor',
            'harga-cetak-outdoor.manage' => 'Tambah/ubah/hapus harga cetak outdoor',
        ],
        'Harga Khusus Customer VIP' => [
            'harga-cetak-outdoor-khusus.view' => 'Lihat harga khusus outdoor customer VIP',
            'harga-cetak-outdoor-khusus.manage' => 'Atur harga khusus outdoor per customer VIP',
        ],
        'Preview Cetak' => [
            'preview-cetak.view' => 'Pakai alat preview mockup cetak (kaos, mug, spanduk, dll)',
        ],
        'Pengaturan' => [
            'roles.manage' => 'Kelola role & user',
        ],
        'Data Warehouse' => [
            'data-warehouse.view' => 'Lihat dashboard data warehouse',
        ],
        'Monitoring Kinerja' => [
            'monitoring-kinerja.view' => 'Lihat monitoring kinerja staf',
        ],
        'Monitoring Transaksi' => [
            'monitoring-transaksi.view' => 'Lihat monitoring transaksi harian/mingguan/bulanan/tahunan',
        ],
        'Papan Pantau' => [
            'papan-pantau.view' => 'Lihat papan pantau produksi lintas tahap (read-only)',
        ],
        'Keuangan' => [
            'keuangan.view' => 'Lihat rekap kas harian & laporan keuangan',
            'pengeluaran.view' => 'Lihat catatan pengeluaran',
            'pengeluaran.manage' => 'Tambah/ubah/hapus catatan pengeluaran',
            'payroll.view' => 'Lihat data payroll/gajian',
            'payroll.manage' => 'Atur gaji pegawai & proses pembayaran gajian',
            'keuangan.tutup-buku' => 'Tutup/buka kembali buku periode (kunci transaksi keuangan bulan tersebut)',
            'keuangan.jurnal-manual' => 'Posting & batalkan jurnal penyesuaian manual',
            'keuangan.pengaturan' => 'Atur data perusahaan untuk keperluan pajak (NPWP, status PKP, tarif PPN default)',
        ],
        'Jasa Potong' => [
            'jasa-potong.manage' => 'Kelola nilai X (biaya tetap) Jasa Potong Indoor',
            'jasa-potong-artwork.manage' => 'Kelola nilai X (biaya tetap) Jasa Potong Artwork',
        ],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'label',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $key): bool
    {
        return in_array($key, $this->permissions ?? [], true);
    }

    /**
     * @return array<string, string>
     */
    public static function allPermissionKeys(): array
    {
        return collect(self::PERMISSION_GROUPS)->collapse()->all();
    }
}
