<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $kasir = DB::table('roles')->where('name', 'kasir')->first();
        if (! $kasir) {
            return;
        }

        $permissions = json_decode($kasir->permissions ?: '[]', true) ?: [];
        if (! in_array('kasir.laporan-kasir-harian.view', $permissions, true)) {
            $permissions[] = 'kasir.laporan-kasir-harian.view';
            DB::table('roles')->where('id', $kasir->id)->update(['permissions' => json_encode(array_values($permissions))]);
        }
    }

    public function down(): void
    {
        $kasir = DB::table('roles')->where('name', 'kasir')->first();
        if (! $kasir) {
            return;
        }

        $permissions = array_values(array_filter(
            json_decode($kasir->permissions ?: '[]', true) ?: [],
            fn (string $permission) => $permission !== 'kasir.laporan-kasir-harian.view'
        ));
        DB::table('roles')->where('id', $kasir->id)->update(['permissions' => json_encode($permissions)]);
    }
};
