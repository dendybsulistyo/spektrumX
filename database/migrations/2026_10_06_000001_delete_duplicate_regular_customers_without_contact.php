<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus 318 customer reguler dobel tanpa kontak (kandidat 6 Okt 2026).
 * Setiap KdCust dicek ulang saat dijalankan: masih reguler (tanpa limit),
 * Telp < 6 digit, dan namanya masih punya kembaran. Baris yang dihapus
 * disalin ke customers_hapus_dobel_20261006 agar bisa dikembalikan (down).
 */
return new class extends Migration
{
    private const BACKUP = 'customers_hapus_dobel_20261006';

    private const KD_CUST = [
        'ART001', '235001', 'AAN016', 'AAN078', 'ACH019', 'ACH073', 'ADI162', 'ADI626',
        'AFI018', 'AFI063', 'AGU202', 'AGU894', 'ALD033', 'ALD071', 'AMA034', 'AMA095',
        'AMA031', 'AMA092', 'AMY005', 'AMY010', 'ANA048', 'ANA196', 'ANA044', 'AND221',
        'AND880', 'AND197', 'AND855', 'AND225', 'AND884', 'AND191', 'AND849', 'ANI062',
        'ANI211', 'ANI064', 'ANI213', 'ANT093', 'ANT302', 'ARD077', 'ARD261', 'ARR025',
        'AWA011', 'AWA043', 'BAR007', 'BAR063', 'BAY037', 'BAY185', 'BAY071', 'BAY222',
        'BER028', 'BER097', 'BHA006', 'BIA008', 'BIA014', 'BIR002', 'BIR013', 'CHR150',
        'CVA004', 'CV_009', 'CV_017', 'CVK001', 'CV_011', 'DAK004', 'DAV029', 'DAV111',
        'DEW171', 'DIA082', 'DIA322', 'DIM041', 'DIM171', 'DIS006', 'DIS028', 'DWI029',
        'DWI271', 'DWI076', 'DWI320', 'EKA109', 'EKO304', 'ELS005', 'ELS017', 'END042',
        'END147', 'ENI010', 'EVA022', 'EVA059', 'FAD001', 'FAD006', 'FAD093', 'FAD027',
        'FAD087', 'FAD026', 'FAD086', 'FAI031', 'FAI113', 'FAJ068', 'FAJ232', 'FAN022',
        'FAN073', 'FAR066', 'FAR198', 'FEL019', 'FEL051', 'FER053', 'FER210', 'FER048',
        'FER205', 'FER054', 'FER211', 'FEY002', 'FOO001', 'FRA029', 'FRA097', 'GAP002',
        'GAP007', 'GER018', 'GER042', 'GHE002', 'GHE004', 'GIA009', 'GIA022', 'GIT004',
        'GIT030', 'GON009', 'GON016', 'GRA051', 'GRA109', 'HAN105', 'HAN310', 'HER550',
        'IGN001', 'IGN021', 'IMR008', 'IMR016', 'INC001', 'IND084', 'IND344', 'IND082',
        'IND342', 'INT005', 'INT038', 'IRF018', 'IRF064', 'JAL015', 'JAL032', 'JAT007',
        'JAT034', 'JOA002', 'JOH025', 'JOH091', 'JON020', 'JON072', 'JUW005', 'KAD011',
        'K_A001', 'KAN019', 'KAN050', 'KAR042', 'KAR151', 'KEM021', 'KEM022', 'KHO001',
        'KHO003', 'KIK017', 'KIK055', 'KOP004', 'KOP010', 'KRI053', 'KRI189', 'LAN020',
        'LAN057', 'LAN021', 'LAN058', 'LAS004', 'LAS015', 'LIO003', 'LUS010', 'LUS026',
        'LUS008', 'LUS024', 'MAL018', 'MAR129', 'MAR445', 'MAR141', 'MAR458', 'MAT021',
        'MAT083', 'MOH021', 'MOH089', 'NAF012', 'NAF030', 'NAN181', 'NAT032', 'NAT074',
        'NAY006', 'NAY013', 'NEX001', 'NE_001', 'NIS039', 'NOV059', 'NOV219', 'NUR075',
        'NUR457', 'OKA005', 'OKA015', 'OLI007', 'OLI013', 'OPT005', 'OPT012', 'PAN042',
        'PAN172', 'PEN012', 'PEN042', 'PER020', 'PER076', 'PET011', 'PET054', 'PIN014',
        'PIN035', 'PRA056', 'PRA285', 'PRI062', 'PRI228', 'PTF001', 'PT_034', 'RAI013',
        'RAI036', 'RAN033', 'RAN113', 'RAN041', 'RAN123', 'RAT035', 'RAT131', 'RAY027',
        'RES032', 'RES098', 'RIA041', 'RIA136', 'RIF039', 'RIF099', 'RIS052', 'RIS180',
        'RIS191', 'RIS190', 'RIS194', 'RIS055', 'RIS184', 'RIY012', 'RIY058', 'RIZ065',
        'RIZ321', 'RIZ356', 'SAH001', 'SAH005', 'SAN154', 'SAP013', 'SAP047', 'SAV004',
        'SAV010', 'SEP028', 'SEP114', 'SEV006', 'SEV012', 'SHE018', 'SHE053', 'SIM011',
        'SIM030', 'SLA007', 'SLA041', 'SRI016', 'SRI088', 'SUD015', 'SUD054', 'SUM021',
        'SUM082', 'SUN020', 'SUN078', 'SUR038', 'SUR177', 'TAD001', 'TAU032', 'TAU121',
        'TEG035', 'TEG119', 'TEM007', 'TEM016', 'THE019', 'THE076', 'TIK005', 'TIK024',
        'TIM008', 'TIM034', 'TKA002', 'TK_003', 'TOM026', 'TOM070', 'TRI099', 'TRI406',
        'TRI090', 'TRI396', 'TUL004', 'TUL009', 'UNI005', 'UNI018', 'VIN003', 'VIO005',
        'VIO009', 'WAR019', 'WAR088', 'WIK006', 'WIK018', 'WIN043', 'WIN123', 'YER007',
        'YER016', 'YUD050', 'YUD053', 'YUD240', 'ZON004', 'ZON008',
    ];

    public function up(): void
    {
        if (! Schema::hasTable(self::BACKUP)) {
            Schema::create(self::BACKUP, function (Blueprint $table) {
                $table->unsignedBigInteger('id')->primary();
                $table->string('KdCust')->nullable();
                $table->string('NmCust')->nullable();
                $table->text('Alamat')->nullable();
                $table->string('Kota')->nullable();
                $table->string('Telp')->nullable();
                $table->string('NPWP')->nullable();
                $table->timestamp('deleted_at')->useCurrent();
            });
        }

        $norm = fn ($name) => trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N} ]/u', ' ', mb_strtolower((string) $name))));
        $hasPhone = fn ($telp) => strlen(preg_replace('/\D/', '', (string) $telp)) >= 6;

        $vip = DB::table('customer_limits')->pluck('KdCust')->flip();
        $nameCounts = DB::table('customers')->pluck('NmCust')->countBy($norm);

        $targets = DB::table('customers')->whereIn('KdCust', self::KD_CUST)->get()
            ->reject(fn ($c) => isset($vip[$c->KdCust]) || $hasPhone($c->Telp) || ($nameCounts[$norm($c->NmCust)] ?? 0) < 2);

        DB::transaction(function () use ($targets) {
            foreach ($targets->chunk(100) as $chunk) {
                DB::table(self::BACKUP)->insertOrIgnore($chunk->map(fn ($c) => [
                    'id' => $c->id, 'KdCust' => $c->KdCust, 'NmCust' => $c->NmCust, 'Alamat' => $c->Alamat,
                    'Kota' => $c->Kota, 'Telp' => $c->Telp, 'NPWP' => $c->NPWP,
                ])->values()->all());
                DB::table('customers')->whereIn('id', $chunk->pluck('id'))->delete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP)) {
            return;
        }

        DB::table(self::BACKUP)->orderBy('id')->chunk(100, function ($rows) {
            DB::table('customers')->insertOrIgnore($rows->map(fn ($c) => [
                'id' => $c->id, 'KdCust' => $c->KdCust, 'NmCust' => $c->NmCust, 'Alamat' => $c->Alamat,
                'Kota' => $c->Kota, 'Telp' => $c->Telp, 'NPWP' => $c->NPWP,
            ])->all());
        });

        Schema::drop(self::BACKUP);
    }
};
