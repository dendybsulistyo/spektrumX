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
            'permissions' => ['customers.view', 'produk.view', 'order-indoor.view'],
        ]);

        Role::updateOrCreate(['name' => 'operator'], [
            'label' => 'Operator/Staff Order',
            'permissions' => ['customers.view', 'produk.view', 'order-indoor.view', 'order-indoor.manage'],
        ]);

        Role::updateOrCreate(['name' => 'owner'], [
            'label' => 'Owner',
            'permissions' => ['customers.view', 'produk.view', 'operators.view', 'printers.view', 'order-indoor.view'],
        ]);
    }
}
