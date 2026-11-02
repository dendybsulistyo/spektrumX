<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Ringkasan Order dashboard: awalnya untuk admin, owner, admin-pajak & Keuangan; selanjutnya diatur lewat centang Role. */
return new class extends Migration
{
    private const ROLES = ['admin', 'owner', 'admin-pajak', 'Keuangan'];

    private const PERMISSION = 'dashboard.ringkasan.view';

    public function up(): void
    {
        foreach (DB::table('roles')->whereIn('name', self::ROLES)->get() as $role) {
            $permissions = json_decode($role->permissions ?: '[]', true) ?: [];
            if (! in_array(self::PERMISSION, $permissions, true)) {
                $permissions[] = self::PERMISSION;
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values($permissions))]);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->get() as $role) {
            $permissions = array_values(array_filter(
                json_decode($role->permissions ?: '[]', true) ?: [],
                fn (string $permission) => $permission !== self::PERMISSION
            ));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        }
    }
};
