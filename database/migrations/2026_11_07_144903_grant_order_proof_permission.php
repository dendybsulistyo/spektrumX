<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Buat & kelola order proof (7 Nov 2026): admin, customer-service, operator (Operator File). */
return new class extends Migration
{
    private const PERMISSION = 'order-proof.manage';

    private const ROLES = ['admin', 'customer-service', 'operator'];

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
