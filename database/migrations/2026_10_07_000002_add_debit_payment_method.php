<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambah metode bayar "debit" (Debit/Card) — dipakai sebagai metode
 * pengganti di Penyesuaian Nota Order / DP. Masuk ke akun Bank.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE order_payments MODIFY cara_bayar ENUM('tunai','qris','transfer','debit') NOT NULL");
        DB::statement("ALTER TABLE payment_method_corrections MODIFY old_method ENUM('tunai','qris','transfer','debit') NOT NULL");
        DB::statement("ALTER TABLE payment_method_corrections MODIFY new_method ENUM('tunai','qris','transfer','debit') NOT NULL");
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY cara_bayar ENUM('tunai','qris','transfer','debit','campuran') NULL");
        }
    }

    public function down(): void
    {
        DB::table('order_payments')->where('cara_bayar', 'debit')->update(['cara_bayar' => 'transfer']);
        DB::table('payment_method_corrections')->where('old_method', 'debit')->update(['old_method' => 'transfer']);
        DB::table('payment_method_corrections')->where('new_method', 'debit')->update(['new_method' => 'transfer']);
        DB::statement("ALTER TABLE order_payments MODIFY cara_bayar ENUM('tunai','qris','transfer') NOT NULL");
        DB::statement("ALTER TABLE payment_method_corrections MODIFY old_method ENUM('tunai','qris','transfer') NOT NULL");
        DB::statement("ALTER TABLE payment_method_corrections MODIFY new_method ENUM('tunai','qris','transfer') NOT NULL");
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            DB::table($table)->where('cara_bayar', 'debit')->update(['cara_bayar' => 'transfer']);
            DB::statement("ALTER TABLE {$table} MODIFY cara_bayar ENUM('tunai','qris','transfer','campuran') NULL");
        }
    }
};
