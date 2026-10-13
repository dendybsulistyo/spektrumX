<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Izin "keuangan.pengaturan" dulu mencakup banyak fitur (Penyesuaian Kas,
 * Potongan Penjualan, Kode Akun, Pembelian, Laporan PPN, dll). Dipecah jadi
 * izin terpisah. Role yang sudah punya izin lama otomatis mendapat semua izin
 * baru agar tidak ada yang kehilangan akses; selanjutnya diatur lewat menu Role.
 */
return new class extends Migration
{
    private const NEW_PERMISSIONS = [
        'keuangan.penyesuaian-kas',
        'keuangan.penyesuaian-penjualan',
        'akuntansi.master',
        'akuntansi.pembelian',
        'keuangan.pajak',
    ];

    public function up(): void
    {
        foreach (DB::table('roles')->get(['id', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            if (! in_array('keuangan.pengaturan', $permissions, true)) {
                continue;
            }
            $merged = array_values(array_unique([...$permissions, ...self::NEW_PERMISSIONS]));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($merged)]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('roles')->get(['id', 'permissions']) as $role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            $kept = array_values(array_diff($permissions, self::NEW_PERMISSIONS));
            if ($kept !== $permissions) {
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($kept)]);
            }
        }
    }
};
