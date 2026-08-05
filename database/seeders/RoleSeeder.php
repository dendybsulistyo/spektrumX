<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::updateOrCreate(['name' => 'admin'], [
            'label' => 'Admin',
            'permissions' => array_keys(Role::allPermissionKeys()),
        ]);

        Role::updateOrCreate(['name' => 'kasir'], [
            'label' => 'Kasir',
            'permissions' => [
                'customers.view', 'produk.view', 'kategori.view', 'order-indoor.view', 'order-outdoor.view', 'order-artwork.view',
                'kasir.view', 'kasir.manage', 'pengambilan.view', 'pengambilan.manage',
            ],
        ]);

        Role::updateOrCreate(['name' => 'operator'], [
            'label' => 'Operator/Staff Order',
            'permissions' => [
                'customers.view', 'produk.view', 'kategori.view', 'kategori-produk-indoor.view', 'kategori-produk-indoor.manage', 'harga-artwork.view', 'order-indoor.view', 'order-indoor.manage',
                'bahan-outdoor.view', 'harga-cetak-outdoor.view', 'order-outdoor.view', 'order-outdoor.manage',
                'order-artwork.view', 'order-artwork.manage',
            ],
        ]);

        Role::updateOrCreate(['name' => 'operator-desain'], [
            'label' => 'Operator Desain',
            'permissions' => ['order-indoor.view', 'order-outdoor.view', 'order-artwork.view', 'order-desain.view', 'order-desain.manage'],
        ]);

        Role::updateOrCreate(['name' => 'operator-cetak'], [
            'label' => 'Operator Cetak',
            'permissions' => ['order-indoor.view', 'order-outdoor.view', 'order-artwork.view', 'order-cetak.view', 'order-cetak.manage'],
        ]);

        Role::updateOrCreate(['name' => 'operator-qc'], [
            'label' => 'Operator QC',
            'permissions' => ['order-indoor.view', 'order-outdoor.view', 'order-artwork.view', 'order-qc.view', 'order-qc.manage'],
        ]);

        Role::updateOrCreate(['name' => 'owner'], [
            'label' => 'Owner',
            'permissions' => [
                'customers.view', 'produk.view', 'kategori-produk-indoor.view', 'kategori.view', 'harga-artwork.view', 'operators.view', 'printers.view', 'order-indoor.view',
                'bahan-outdoor.view', 'kategori-bahan-outdoor.view', 'harga-cetak-outdoor.view', 'harga-cetak-outdoor-khusus.view', 'harga-cetak-outdoor-khusus.manage', 'printer-outdoor.view', 'bahan-cetak-outdoor.view', 'order-outdoor.view', 'order-artwork.view',
                'file-monitor.view', 'kasir.view', 'order-desain.view', 'order-cetak.view', 'order-qc.view', 'pengambilan.view', 'data-warehouse.view',
                'jasa-potong.manage', 'jasa-potong-artwork.manage',
            ],
        ]);
    }
}
