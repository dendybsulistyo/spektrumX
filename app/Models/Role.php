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
            'kategori-produk-indoor.view' => 'Lihat data divisi indoor',
            'kategori-produk-indoor.manage' => 'Tambah/ubah/hapus divisi indoor',
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
        ],
        'Order Outdoor' => [
            'order-outdoor.view' => 'Lihat order outdoor',
            'order-outdoor.manage' => 'Buat/ubah/hapus order outdoor',
            'order-outdoor.approve-cancel' => 'Setujui/tolak pengajuan pembatalan order outdoor',
        ],
        'Order Artwork' => [
            'order-artwork.view' => 'Lihat order artwork',
            'order-artwork.manage' => 'Buat/ubah/hapus order artwork',
        ],
        'File Monitor' => [
            'file-monitor.view' => 'Lihat monitoring file masuk (order indoor/outdoor/artwork)',
        ],
        'Kasir' => [
            'kasir.view' => 'Lihat antrian kasir',
            'kasir.manage' => 'Proses pembayaran order',
        ],
        'Operator Desain' => [
            'order-desain.view' => 'Lihat antrian desain',
            'order-desain.manage' => 'Update status desain',
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
        'Jasa Potong' => [
            'jasa-potong.manage' => 'Kelola nilai X (biaya tetap) Jasa Potong',
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
