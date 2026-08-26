<?php

namespace Database\Seeders;

use App\Models\AccountingInventoryCount;
use App\Models\AccountingInventoryItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingInventoryDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = User::first()?->id;

        // Truncate existing data to prevent duplicate unique constraint errors
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('accounting_inventory_counts')->truncate();
        DB::table('accounting_inventory_items')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info("Seeding demo data persediaan...");

        // 1. Seed Inventory Items
        $items = [
            // Bahan Baku
            [
                'kode' => 'BB-001',
                'nama' => 'Kertas Art Paper 150gr',
                'kelompok' => 'bahan_baku',
                'satuan' => 'Rim',
                'is_active' => true,
                'opname' => [
                    'qty' => 41000,
                    'harga' => 250000, // Nilai total = 10.250.000.000 (mendekati saldo awal + pembelian)
                ]
            ],
            [
                'kode' => 'BB-002',
                'nama' => 'Flexi Banner 280gr',
                'kelompok' => 'bahan_baku',
                'satuan' => 'Roll',
                'is_active' => true,
                'opname' => null
            ],
            // Bahan Penolong
            [
                'kode' => 'BP-001',
                'nama' => 'Lem Powder DTF',
                'kelompok' => 'bahan_penolong',
                'satuan' => 'Kg',
                'is_active' => true,
                'opname' => [
                    'qty' => 25,
                    'harga' => 100000, // Nilai total = 2.500.000
                ]
            ],
            [
                'kode' => 'BP-002',
                'nama' => 'Mata Ayam Ring Kuningan',
                'kelompok' => 'bahan_penolong',
                'satuan' => 'Box',
                'is_active' => true,
                'opname' => null
            ],
            // Barang Jadi
            [
                'kode' => 'BJ-001',
                'nama' => 'Buku Spektra Magazine',
                'kelompok' => 'barang_jadi',
                'satuan' => 'Pcs',
                'is_active' => true,
                'opname' => [
                    'qty' => 104000,
                    'harga' => 15000, // Nilai total = 1.560.000.000
                ]
            ],
            [
                'kode' => 'BJ-002',
                'nama' => 'Banner Roll-Up Promosi',
                'kelompok' => 'barang_jadi',
                'satuan' => 'Pcs',
                'is_active' => true,
                'opname' => null
            ],
        ];

        foreach ($items as $itemData) {
            $opname = $itemData['opname'];
            unset($itemData['opname']);

            $item = AccountingInventoryItem::create($itemData);

            // If this item has stock opname, seed it for 31 January 2026
            if ($opname) {
                $nilai = round($opname['qty'] * $opname['harga']);
                AccountingInventoryCount::create([
                    'inventory_item_id' => $item->id,
                    'tanggal' => '2026-01-31',
                    'qty' => $opname['qty'],
                    'harga_satuan' => $opname['harga'],
                    'nilai' => $nilai,
                    'keterangan' => 'Stok Opname Akhir Januari 2026 (Demo)',
                    'user_id' => $userId,
                ]);
            }
        }

        $this->command->info("Seeding demo data persediaan selesai!");
    }
}
