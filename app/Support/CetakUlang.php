<?php

namespace App\Support;

use App\Models\User;

/**
 * Siapa yang boleh mencetak ULANG dokumen customer (SO, DO, Invoice).
 * Cetak pertama tetap mengikuti hak akses halaman masing-masing.
 */
final class CetakUlang
{
    public const ROLES = ['admin-kasir', 'akuntansi'];

    public const LABEL = 'Admin Kasir atau Akuntansi';

    public static function boleh(?User $user): bool
    {
        return in_array($user?->role?->name, self::ROLES, true);
    }
}
