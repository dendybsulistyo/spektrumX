<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'Pengambilan-Barang')->first();
        if (! $role) {
            return;
        }

        $permissions = json_decode($role->permissions ?: '[]', true) ?: [];
        if (! in_array('laporan-order.view', $permissions, true)) {
            $permissions[] = 'laporan-order.view';
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values($permissions))]);
        }
    }

    public function down(): void
    {
        $role = DB::table('roles')->where('name', 'Pengambilan-Barang')->first();
        if (! $role) {
            return;
        }

        $permissions = array_values(array_filter(
            json_decode($role->permissions ?: '[]', true) ?: [],
            fn (string $permission) => $permission !== 'laporan-order.view'
        ));
        DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
    }
};
