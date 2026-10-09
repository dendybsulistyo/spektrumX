<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menu "Pajak & Akuntansi" kini dipisah per hak akses (menu.perpajakan /
 * menu.akuntansi). Role yang sudah punya keuangan.view diberi keduanya
 * supaya tampilannya tidak berubah; admin bisa mencabut salah satunya.
 */
return new class extends Migration
{
    private const KEYS = ['menu.perpajakan', 'menu.akuntansi'];

    public function up(): void
    {
        foreach (DB::table('roles')->get(['id', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            if (! in_array('keuangan.view', $permissions, true)) {
                continue;
            }
            $merged = array_values(array_unique([...$permissions, ...self::KEYS]));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($merged)]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->get(['id', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            $filtered = array_values(array_diff($permissions, self::KEYS));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($filtered)]);
        }
    }
};
