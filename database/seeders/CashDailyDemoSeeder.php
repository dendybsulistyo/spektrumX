<?php

namespace Database\Seeders;

use App\Models\CashDailyEntry;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class CashDailyDemoSeeder extends Seeder
{
    private const DATE = '2026-08-24';

    public function run(): void
    {
        DB::transaction(function () {
            $role = Role::firstOrCreate(['name' => 'kasir'], [
                'label' => 'Kasir',
                'permissions' => [
                    'customers.view', 'produk.view', 'kategori.view',
                    'order-indoor.view', 'order-outdoor.view', 'order-artwork.view',
                    'kasir.view', 'kasir.manage', 'pengambilan.view',
                    'pengambilan.manage', 'preview-cetak.view',
                ],
            ]);

            $mini = $this->cashier($role, 'Mini', 'mini@spektrum.com');
            $yovita = $this->cashier($role, 'Yovita', 'yovita@spektrum.com');

            $rows = [
                [null, null, 'Saldo Awal', 2700300, 0],

                [$mini->id, 'UM-26F12107.71', 'DP - TYAS DWI ASTUTI', 100500, 0],
                [$mini->id, null, 'Bayar via Transfer - TYAS DWI ASTUTI', 0, 100500],
                [$mini->id, 'UM-26P07785.71', 'DP - UJANG SPEKTRUM', 124700, 0],
                [$mini->id, null, 'Bayar via Transfer - UJANG SPEKTRUM', 0, 124700],
                [$mini->id, 'UM-26P07786.71', 'DP - AGUS KLITREN', 344300, 0],
                [$mini->id, 'UM-26F12118.83', 'DP - PUTRO BEKEL', 25000, 0],
                [$mini->id, null, 'Bayar via Transfer - PUTRO BEKEL', 0, 25000],
                [$mini->id, 'UM-26F12117.83', 'DP - PUTRO BEKEL', 400500, 0],
                [$mini->id, null, 'Bayar via Transfer - PUTRO BEKEL', 0, 400500],
                [$mini->id, 'UM-26F12119.83', 'DP - IBON', 5400, 0],
                [$mini->id, 'UM-26F12022.17', 'DP - GALERI PLAKAT', 180600, 0],
                [$mini->id, null, 'Bayar via Transfer - GALERI PLAKAT', 0, 180600],
                [$mini->id, '26T19173.12', 'Nota GMP', 40000, 0],
                [$mini->id, null, 'Bayar via Transfer - GMP', 0, 40000],
            ];

            $yovitaTransactions = [
                ['SUKSES MANDIRI CV', 1100000],
                ['DJARUM PT RSO SEMARANG', 10828700],
                ['DJARUM PT RSO SEMARANG', 18251300],
                ['SUKSES MANDIRI CV', 1100000],
                ['MIROTA KSM, PT', 945100],
                ['SUMBER BARU LAND . PT', 5587200],
                ['SUMBER BARU LAND . PT', 12131600],
                ['SUMBER BARU LAND . PT', 7687600],
                ['SUMBER BARU LAND . PT', 2598900],
                ['JO CIPUTRA SUNINDO PRIMA UTAMA', 756500],
                ['CERMIN JIWA ILAHI, PT', 413500],
                ['SUMBER BARU LAND . PT', 508800],
                ['JO CIPUTRA SUNINDO PRIMA UTAMA', 434800],
                ['SUMBER BARU LAND . PT', 55400],
                ['Perada Swara Productions, PT', 22484800],
                ['KARTIKA MUDA JAYA, CV', 80000],
                ['DJARUM PT RSO SEMARANG', 20398900],
                ['DJARUM PT RSO SEMARANG', 42353500],
                ['MIROTA KSM, PT', 754500],
                ['SUMBER BARU LAND . PT', 143500],
                ['KARTIKA MUDA JAYA, CV', 5000000],
                ['KARTIKA MUDA JAYA, CV', 10600000],
            ];

            foreach ($yovitaTransactions as [$customer, $amount]) {
                $rows[] = [$yovita->id, null, 'Piutang '.$customer, $amount, 0];
                $rows[] = [$yovita->id, null, 'Bayar via Transfer BCA - '.$customer, 0, $amount];
            }

            foreach ($rows as $index => [$userId, $invoice, $description, $debit, $credit]) {
                $occurredAt = $index === 0
                    ? Carbon::parse(self::DATE)->startOfDay()
                    : Carbon::parse(self::DATE.' 08:00:00')->addMinutes(($index - 1) * 5);

                CashDailyEntry::updateOrCreate(
                    ['source_key' => 'demo-kas-20260824-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                    [
                        'tanggal' => self::DATE,
                        'occurred_at' => $occurredAt,
                        'user_id' => $userId,
                        'no_nota' => $invoice,
                        'keterangan' => $description,
                        'debet' => $debit,
                        'kredit' => $credit,
                        'urutan' => $index + 1,
                    ]
                );
            }
        });

        $this->command?->info('Demo Kas Harian 24 Agustus 2026 serta akun Mini dan Yovita berhasil disiapkan.');
    }

    private function cashier(Role $role, string $name, string $email): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role_id' => $role->id,
                'email_verified_at' => now(),
            ]
        );
    }
}
