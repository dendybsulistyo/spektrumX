<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Rekap Transaksi Bulanan (menu Analitik) — diberikan ke role admin (pengelola role). */
return new class extends Migration
{
    private const KEY = 'rekap-bulanan.view';

    public function up(): void
    {
        foreach (DB::table('roles')->get(['id', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            if (in_array('roles.manage', $permissions, true) && ! in_array(self::KEY, $permissions, true)) {
                $permissions[] = self::KEY;
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values($permissions))]);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->get(['id', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values(array_diff($permissions, [self::KEY])))]);
        }
    }
};
