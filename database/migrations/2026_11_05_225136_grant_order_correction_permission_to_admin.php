<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSION = 'order-correction.manage';

    /**
     * Run the migrations.
     */
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (DB::table('roles')->get() as $role) {
            $permissions = array_values(array_filter(
                json_decode($role->permissions ?: '[]', true) ?: [],
                fn (string $permission): bool => $permission !== self::PERMISSION,
            ));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        }
    }
};
