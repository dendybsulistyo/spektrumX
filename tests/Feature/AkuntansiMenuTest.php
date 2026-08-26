<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkuntansiMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_accounting_menus_and_reports_load_successfully(): void
    {
        // 1. Create a Role with all Akuntansi-related permissions
        $role = Role::create([
            'name' => 'staf_akuntansi',
            'label' => 'Staf Akuntansi',
            'permissions' => [
                'keuangan.view',
                'pengeluaran.view',
                'pengeluaran.manage',
                'payroll.view',
                'payroll.manage',
                'keuangan.tutup-buku',
                'keuangan.jurnal-manual',
                'keuangan.pengaturan',
            ],
        ]);

        // 2. Create and authenticate the user
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $this->actingAs($user);

        // 3. List of all accounting routes to verify
        $routes = [
            // Penjualan & Pajak / Kas & Bank
            'akuntansi.gunggungan' => route('akuntansi.gunggungan'),
            'akuntansi.rekap-omset' => route('akuntansi.rekap-omset'),
            'keuangan.laporan-ppn' => route('keuangan.laporan-ppn'),
            'keuangan.kas-harian' => route('keuangan.kas-harian'),
            'akuntansi.kas-bank' => route('akuntansi.kas-bank'),
            
            // Pembelian & Hutang
            'akuntansi.purchases.index' => route('akuntansi.purchases.index'),
            'akuntansi.purchases.report' => route('akuntansi.purchases.report'),
            'akuntansi.hutang-supplier' => route('akuntansi.hutang-supplier'),
            
            // Piutang
            'keuangan.piutang' => route('keuangan.piutang'),
            'akuntansi.piutang-customer' => route('akuntansi.piutang-customer'),
            
            // Jurnal & Laporan
            'akuntansi.jurnal-umum' => route('akuntansi.jurnal-umum'),
            'akuntansi.buku-besar' => route('akuntansi.buku-besar'),
            'akuntansi.neraca-saldo' => route('akuntansi.neraca-saldo'),
            'akuntansi.inventory-hpp' => route('akuntansi.inventory-hpp'),
            'akuntansi.hpp-report' => route('akuntansi.hpp-report'),
            'akuntansi.fixed-assets.index' => route('akuntansi.fixed-assets.index'),
            'keuangan.laba-rugi' => route('keuangan.laba-rugi'),
            'akuntansi.neraca' => route('akuntansi.neraca'),
            'akuntansi.perubahan-modal' => route('akuntansi.perubahan-modal'),
            'keuangan.jurnal-manual' => route('keuangan.jurnal-manual'),
            'akuntansi.import-gunggungan' => route('akuntansi.import-gunggungan'),
            'keuangan.tutup-buku' => route('keuangan.tutup-buku'),
            
            // Pengeluaran & Payroll
            'pengeluaran.index' => route('pengeluaran.index'),
            'payroll.index' => route('payroll.index'),
            
            // Master
            'akuntansi.akun.index' => route('akuntansi.akun.index'),
            'akuntansi.suppliers.index' => route('akuntansi.suppliers.index'),
            'keuangan.pengaturan.edit' => route('keuangan.pengaturan.edit'),
        ];

        // 4. Hit each route and assert it is successful (200 OK)
        foreach ($routes as $name => $url) {
            $response = $this->get($url);
            $response->assertStatus(200, "Route '{$name}' at URL '{$url}' failed to load.");
        }
    }
}
