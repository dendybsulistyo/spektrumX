<?php

namespace App\Support;

use App\Models\User;

/**
 * Halaman ber-izin keuangan.view dibagi dua: Perpajakan & Akuntansi.
 * User dengan keuangan.view hanya boleh membuka halaman dari menu yang
 * diberikan ke role-nya (menu.perpajakan / menu.akuntansi).
 */
class FinanceMenuAccess
{
    /** Halaman menu Perpajakan (nama route persis atau awalan "nama."). */
    private const PERPAJAKAN = [
        'akuntansi.gunggungan',
        'report.credit-orders-by-customer',
        'keuangan.laporan-ppn',
        'keuangan.global-customer-receivables',
        'akuntansi.purchases',
        'akuntansi.hutang-supplier',
        'keuangan.piutang',
        'akuntansi.piutang-customer',
    ];

    /** Halaman yang ada di kedua menu. */
    private const KEDUANYA = [
        'order-documents',
        'keuangan.customer-receivable-details',
        'order-indoor.request-cancel',
        'order-outdoor.request-cancel',
    ];

    /** Semua halaman keuangan.view lainnya termasuk menu Akuntansi. */
    public static function menuFor(?string $routeName): string
    {
        if (self::matches($routeName, self::KEDUANYA)) {
            return 'keduanya';
        }

        return self::matches($routeName, self::PERPAJAKAN) ? 'perpajakan' : 'akuntansi';
    }

    public static function allows(User $user, ?string $routeName): bool
    {
        if (! $user->hasPermission('keuangan.view')) {
            return false;
        }

        return match (self::menuFor($routeName)) {
            'perpajakan' => $user->hasPermission('menu.perpajakan'),
            'akuntansi' => $user->hasPermission('menu.akuntansi'),
            default => $user->hasPermission('menu.perpajakan') || $user->hasPermission('menu.akuntansi'),
        };
    }

    private static function matches(?string $routeName, array $names): bool
    {
        if (! $routeName) {
            return false;
        }

        foreach ($names as $name) {
            if ($routeName === $name || str_starts_with($routeName, $name.'.')) {
                return true;
            }
        }

        return false;
    }
}
