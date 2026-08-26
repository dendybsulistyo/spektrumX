<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'P-002' => 'CIP009', 'P-003' => 'CIT050', 'P-004' => 'DJA013', 'P-005' => 'KIN001',
            'P-006' => 'KAR041', 'P-007' => 'MIR021', 'P-008' => 'NOX002', 'P-009' => 'ROT001',
            'P-010' => 'SUK072', 'P-011' => 'WOO001', 'P-014' => 'SUM028',
        ] as $kodeBantu => $customerKode) {
            DB::table('accounting_customer_profiles')->where('kode_bantu', $kodeBantu)->update(['customer_kd' => $customerKode, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('accounting_customer_profiles')->whereIn('kode_bantu', ['P-002', 'P-003', 'P-004', 'P-005', 'P-006', 'P-007', 'P-008', 'P-009', 'P-010', 'P-011', 'P-014'])->update(['customer_kd' => null]);
    }
};
