<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Hapus customer dipisah dari customers.manage (keputusan user 2 Nov 2026): awalnya hanya admin. */
return new class extends Migration
{
    private const PERMISSION = 'customers.delete';

    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'admin')->first();
        if (! $role) {
            return;
        }

        $permissions = json_decode($role->permissions ?: '[]', true) ?: [];
        if (! in_array(self::PERMISSION, $permissions, true)) {
            $permissions[] = self::PERMISSION;
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values($permissions))]);
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
