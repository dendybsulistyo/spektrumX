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
        'Operator' => [
            'operators.view' => 'Lihat data operator',
            'operators.manage' => 'Tambah/ubah/hapus operator',
        ],
        'Printer' => [
            'printers.view' => 'Lihat data printer',
            'printers.manage' => 'Tambah/ubah/hapus printer',
        ],
        'Order Indoor' => [
            'order-indoor.view' => 'Lihat order',
            'order-indoor.manage' => 'Buat/ubah/hapus order',
        ],
        'Pengaturan' => [
            'roles.manage' => 'Kelola role & user',
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
