<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $admin = DB::table('roles')->where('name', 'admin')->first();
        if (! $admin) {
            return;
        }

        $permissions = json_decode($admin->permissions ?: '[]', true) ?: [];
        if (! in_array('printer-performance.view', $permissions, true)) {
            $permissions[] = 'printer-performance.view';
            DB::table('roles')->where('id', $admin->id)->update(['permissions' => json_encode(array_values($permissions))]);
        }
    }

    public function down(): void
    {
        $admin = DB::table('roles')->where('name', 'admin')->first();
        if (! $admin) {
            return;
        }

        $permissions = array_values(array_filter(
            json_decode($admin->permissions ?: '[]', true) ?: [],
            fn (string $permission) => $permission !== 'printer-performance.view'
        ));
        DB::table('roles')->where('id', $admin->id)->update(['permissions' => json_encode($permissions)]);
    }
};
