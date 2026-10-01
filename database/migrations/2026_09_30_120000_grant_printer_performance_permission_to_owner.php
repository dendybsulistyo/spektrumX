<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $owner = DB::table('roles')->where('name', 'owner')->first();
        if (! $owner) {
            return;
        }

        $permissions = json_decode($owner->permissions ?: '[]', true) ?: [];
        if (! in_array('printer-performance.view', $permissions, true)) {
            $permissions[] = 'printer-performance.view';
            DB::table('roles')->where('id', $owner->id)->update(['permissions' => json_encode(array_values($permissions))]);
        }
    }

    public function down(): void
    {
        $owner = DB::table('roles')->where('name', 'owner')->first();
        if (! $owner) {
            return;
        }

        $permissions = array_values(array_filter(
            json_decode($owner->permissions ?: '[]', true) ?: [],
            fn (string $permission) => $permission !== 'printer-performance.view'
        ));
        DB::table('roles')->where('id', $owner->id)->update(['permissions' => json_encode($permissions)]);
    }
};
