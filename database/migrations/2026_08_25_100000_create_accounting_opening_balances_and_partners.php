<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7)->index();
            $table->string('NoAkun', 6)->index();
            $table->string('kode_bantu', 10)->default('');
            $table->decimal('debet', 18, 2)->default(0);
            $table->decimal('kredit', 18, 2)->default(0);
            $table->string('keterangan', 120)->nullable();
            $table->timestamps();
            $table->unique(['periode', 'NoAkun', 'kode_bantu'], 'accounting_opening_balance_unique');
        });

        Schema::create('accounting_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('kode_bantu', 10)->unique();
            $table->string('nama', 120);
            $table->string('npwp', 30)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->decimal('saldo_awal', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('accounting_customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('customer_kd', 20)->nullable()->index();
            $table->string('kode_bantu', 10)->unique();
            $table->string('nama', 120);
            $table->string('npwp', 30)->nullable();
            $table->string('alamat', 255)->nullable();
            $table->decimal('saldo_awal', 18, 2)->default(0);
            $table->timestamps();
        });

        $opening = [
            ['11100', 650389307, 0, 'Saldo awal Kas'], ['11102', 1281740038, 0, 'Saldo awal Piutang Dagang'],
            ['11200', 1553000000, 0, 'Saldo awal Persediaan Barang Jadi'], ['11301', 10187858027, 0, 'Saldo awal Persediaan Bahan Baku'],
            ['12100', 48589042, 0, 'Saldo awal Gedung dan Listrik'], ['12200', 6252122108, 0, 'Saldo awal Peralatan Pabrik'],
            ['12300', 208909613, 0, 'Saldo awal Peralatan Kantor'], ['21100', 0, 7162874124, 'Saldo awal Hutang Dagang'],
            ['22101', 0, 31589467, 'Saldo awal Hutang PPh Final PP 46'], ['31000', 0, 11488144544, 'Saldo awal Modal'],
            ['32000', 0, 1500000000, 'Saldo awal Laba Ditahan Amnesty'],
        ];

        foreach ($opening as [$account, $debet, $kredit, $note]) {
            DB::table('accounting_opening_balances')->insert(['periode' => '2026-01', 'NoAkun' => $account, 'debet' => $debet, 'kredit' => $kredit, 'keterangan' => $note, 'created_at' => now(), 'updated_at' => now()]);
        }

        $suppliers = [
            ['H-001', 'Aneka Warna Indah, PT', '0013041108073000', 89792000.09], ['H-002', 'Artha Inti Lestari, PT', '0023419682036000', 5172600], ['H-003', 'Jaya Sejahtera, CV', '0814248126543000', 3685010], ['H-004', 'Kurnia Jaya, CV', '0026564468504000', 1295903], ['H-005', 'Mulia Mandiri Supply, PT', '0027922004044000', 77920499], ['H-006', 'Multi Prima Karya Sejati, CV', '0022311278085000', 2234427], ['H-007', 'Multiviscomindo, PT', '0027962547062000', 231328247], ['H-008', 'Nusign Supply Indonesia, PT', '0311882641418000', 522471950], ['H-009', 'Putra Mutiara Jaya, PT', '0029132289424000', 32490018], ['H-010', 'Samafitro, PT', '0013109087073000', 89698276], ['H-011', 'Tri Mitra Sejati, CV', '0755762911503000', 1680025], ['H-012', 'Omega Garda Rupla, PT', '0026450528543000', 0], ['H-013', 'Mitra Abadi Jaya Mandiri, CV', '0314894494541000', 0], ['H-014', 'Attaya Global Visindo, PT', '0835815531009000', 0],
        ];
        foreach ($suppliers as [$code, $name, $npwp, $balance]) {
            DB::table('accounting_suppliers')->insert(['kode_bantu' => $code, 'nama' => $name, 'npwp' => $npwp, 'saldo_awal' => $balance, 'created_at' => now(), 'updated_at' => now()]);
            if ($balance > 0) DB::table('accounting_opening_balances')->insert(['periode' => '2026-01', 'NoAkun' => '21100', 'kode_bantu' => $code, 'kredit' => $balance, 'keterangan' => 'Saldo awal hutang '.$name, 'created_at' => now(), 'updated_at' => now()]);
        }

        $customers = [
            ['P-001', 'Artindo Grafika Printing, PT', '0025062092004000', 1231037841], ['P-002', 'CIPUTRA NUSA LESTARI', '0736718511542000', 488400], ['P-003', 'CITRA JOGJA KREASI', '0032926479542000', 7229541], ['P-004', 'DJARUM, PT', '0012023974511000', 27152215], ['P-005', 'K-24 INDONESIA', '0023694474541000', 12654], ['P-006', 'KARTIKA MUDA JAYA', '0210008959542000', 5050056], ['P-007', 'MIROTA KSM', '0011367331542000', 975579], ['P-008', 'NOX INDONESIA GAUNG GEMERLAP', '0824228274541000', 7967358], ['P-009', 'ROTI KEHIDUPAN INDONESIA', '0904115565541000', 688755], ['P-010', 'SUKSES MANDIRI', '0758550131541000', 616383], ['P-011', 'WOOK GLOBAL TECHNOLOGY', '0855776142041000', 521256], ['P-012', 'Pustaka Insan Madani, PT', '0023984578542000', 0], ['P-013', 'Cermin Jiwa Ilahi, PT', '0755766318542000', 0], ['P-014', 'Sumber Baru Land, PT', '0022653869542000', 0],
        ];
        $databaseCustomers = DB::table('customers')->get(['KdCust', 'NmCust']);
        foreach ($customers as [$code, $name, $npwp, $balance]) {
            $match = $databaseCustomers->first(fn ($customer) => Str::upper(trim($customer->NmCust)) === Str::upper(trim($name)));
            DB::table('accounting_customer_profiles')->insert(['customer_kd' => $match?->KdCust, 'kode_bantu' => $code, 'nama' => $name, 'npwp' => $npwp, 'saldo_awal' => $balance, 'created_at' => now(), 'updated_at' => now()]);
            if ($balance > 0) DB::table('accounting_opening_balances')->insert(['periode' => '2026-01', 'NoAkun' => '11102', 'kode_bantu' => $code, 'debet' => $balance, 'keterangan' => 'Saldo awal piutang '.$name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_customer_profiles');
        Schema::dropIfExists('accounting_suppliers');
        Schema::dropIfExists('accounting_opening_balances');
    }
};
