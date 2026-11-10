<?php

namespace Tests\Feature;

use App\Http\Controllers\KeuanganController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LaporanKasirHarianTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->timestamps()]);
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('email'), $t->unsignedBigInteger('role_id')->nullable(), $t->timestamps()]);
        Schema::create('customers', fn (Blueprint $t) => [$t->id(), $t->string('KdCust'), $t->string('NmCust'), $t->timestamps()]);
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('NoOrder');
                $t->string('KdCust')->nullable();
                $t->string('status');
                $t->unsignedBigInteger('cancel_approved_by')->nullable();
                $t->timestamp('cancel_approved_at')->nullable();
                $t->timestamp('invoice_voided_at')->nullable();
                $t->unsignedBigInteger('replacement_order_id')->nullable();
                $t->decimal('replacement_credit', 15, 2)->default(0);
                $t->decimal('jumlah_dibayar', 15, 2)->default(0);
                $t->timestamp('dibayar_at')->nullable();
                $t->unsignedBigInteger('kasir_user_id')->nullable();
                $t->timestamps();
            });
        }
        Schema::create('order_payments', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->unsignedBigInteger('order_id');
            $t->string('jenis');
            $t->string('cara_bayar');
            $t->decimal('jumlah', 15, 2);
            $t->string('no_referensi')->nullable();
            $t->unsignedBigInteger('batch_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
        });
        Schema::create('cash_daily_entries', function (Blueprint $t) {
            $t->id();
            $t->date('tanggal');
            $t->dateTime('occurred_at')->nullable();
            $t->integer('urutan')->default(0);
            $t->string('no_nota')->nullable();
            $t->string('keterangan');
            $t->decimal('debet', 15, 2)->default(0);
            $t->decimal('kredit', 15, 2)->default(0);
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
        });
        Schema::create('final_sales_discounts', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->unsignedBigInteger('order_id');
            $t->decimal('refund_amount', 15, 2)->default(0);
            $t->string('refund_method')->nullable();
            $t->decimal('receivable_offset', 15, 2)->default(0);
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
        });

        DB::table('users')->insert([
            ['name' => 'Yovita', 'email' => 'yovita@example.test'],
            ['name' => 'Brian', 'email' => 'brian@example.test'],
        ]);
        DB::table('customers')->insert(['KdCust' => 'C1', 'NmCust' => 'Kiki']);
    }

    /** @return array{rows: Collection, saldoKas: float} */
    private function report(array $query): array
    {
        $viewer = new class(['name' => 'Yovita']) extends User
        {
            public function hasPermission(string $permission): bool
            {
                return true;
            }
        };
        $viewer->id = 1;
        $request = Request::create('/keuangan/laporan-kasir-harian', 'GET', $query);
        $request->setUserResolver(fn () => $viewer);

        return app(KeuanganController::class)->laporanKasirHarian($request)->getData();
    }

    private function payment(array $attributes): void
    {
        DB::table('order_payments')->insert($attributes + [
            'order_type' => 'indoor', 'order_id' => 1, 'jenis' => 'pelunasan', 'user_id' => 2,
            'created_at' => '2026-11-05 10:00:00', 'updated_at' => '2026-11-05 10:00:00',
        ]);
    }

    public function test_cancellation_refund_via_transfer_is_credit_only(): void
    {
        DB::table('order_indoor')->insert(['NoOrder' => 'IND.2.261105040', 'KdCust' => 'C1', 'status' => 'batal', 'cancel_approved_by' => 2]);
        $this->payment(['cara_bayar' => 'transfer', 'jumlah' => -25700, 'created_at' => '2026-11-05 15:00:00']);

        $report = $this->report(['tanggal' => '2026-11-05', 'kasir' => 2]);

        $refundRows = $report['rows']->filter(fn ($row) => str_contains($row['keterangan'], 'efund'));
        $this->assertCount(1, $refundRows);
        $this->assertSame([0.0, 25700.0], [(float) $refundRows->first()['debet'], (float) $refundRows->first()['kredit']]);
        $this->assertEquals(-25700, $report['saldoKas']);
    }

    public function test_drawer_opening_balance_only_shown_for_all_cashiers(): void
    {
        DB::table('order_indoor')->insert(['NoOrder' => 'IND.2.261104001', 'KdCust' => 'C1', 'status' => 'selesai']);
        $this->payment(['cara_bayar' => 'tunai', 'jumlah' => 100000, 'created_at' => '2026-11-04 10:00:00']);
        $this->payment(['cara_bayar' => 'tunai', 'jumlah' => 50000]);

        $perKasir = $this->report(['tanggal' => '2026-11-05', 'kasir' => 2]);
        $semua = $this->report(['tanggal' => '2026-11-05']);

        $this->assertFalse($perKasir['rows']->contains('keterangan', 'Saldo Awal'));
        $this->assertEquals(50000, $perKasir['saldoKas']);
        $this->assertEquals(100000, (float) $semua['rows']->firstWhere('keterangan', 'Saldo Awal')['debet']);
        $this->assertEquals(150000, $semua['saldoKas']);
    }

    public function test_nota_batal_karena_nota_pengganti_kredit_di_penyetuju_debet_di_kasir(): void
    {
        $old = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.261107048', 'KdCust' => 'C1', 'status' => 'batal', 'jumlah_dibayar' => 1428700, 'cancel_approved_by' => 1, 'cancel_approved_at' => '2026-11-09 13:26:49', 'invoice_voided_at' => '2026-11-09 13:26:49']);
        $new = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.261109032', 'KdCust' => 'C1', 'status' => 'desain', 'replacement_order_id' => $old, 'replacement_credit' => 1428700, 'kasir_user_id' => 2, 'dibayar_at' => '2026-11-09 14:00:00']);
        $this->payment(['order_id' => $new, 'jenis' => 'nota_pengganti', 'cara_bayar' => 'tunai', 'jumlah' => 10500, 'created_at' => '2026-11-09 14:00:00']);

        $kasir = $this->report(['tanggal' => '2026-11-09', 'kasir' => 2]);
        $this->assertSame(['IND.2.261109032'], $kasir['rows']->whereNotNull('no_nota')->pluck('no_nota')->all());
        $this->assertSame([1439200.0, 0.0], [$kasir['rows'][0]['debet'], $kasir['rows'][0]['kredit']]);

        $penyetuju = $this->report(['tanggal' => '2026-11-09', 'kasir' => 1]);
        $batal = $penyetuju['rows']->firstWhere('no_nota', 'IND.2.261107048');
        $this->assertSame('Pembatalan · diganti IND.2.261109032 · disetujui Yovita - KIKI', $batal['keterangan']);
        $this->assertSame([0.0, 1428700.0], [$batal['debet'], $batal['kredit']]);

        // Total harian semua kasir = uang yang benar-benar masuk (tambahan Rp 10.500).
        $this->assertEquals(10500, $this->report(['tanggal' => '2026-11-09'])['saldoKas']);
    }

    public function test_nota_pengganti_tanpa_selisih_tetap_tercatat_di_debet_kasir(): void
    {
        $old = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.001', 'KdCust' => 'C1', 'status' => 'batal', 'jumlah_dibayar' => 50000, 'cancel_approved_by' => 1, 'cancel_approved_at' => '2026-11-09 10:00:00', 'invoice_voided_at' => '2026-11-09 10:00:00']);
        DB::table('order_indoor')->insert(['NoOrder' => 'IND.2.002', 'KdCust' => 'C1', 'status' => 'desain', 'replacement_order_id' => $old, 'replacement_credit' => 50000, 'kasir_user_id' => 2, 'dibayar_at' => '2026-11-09 11:00:00']);

        $rows = $this->report(['tanggal' => '2026-11-09'])['rows']->keyBy('no_nota');

        $this->assertSame([50000.0, 2], [$rows['IND.2.002']['debet'], $rows['IND.2.002']['user_id']]);
        $this->assertSame([50000.0, 1], [$rows['IND.2.001']['kredit'], $rows['IND.2.001']['user_id']]);
    }

    public function test_rekap_per_user_kelompok_kasir_hanya_penerimaan(): void
    {
        $order = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.010', 'KdCust' => 'C1', 'status' => 'desain']);
        $this->payment(['order_id' => $order, 'jenis' => 'lunas', 'cara_bayar' => 'qris', 'jumlah' => 75000, 'created_at' => '2026-11-10 09:00:00']);
        $this->payment(['order_id' => $order, 'jenis' => 'lunas', 'cara_bayar' => 'tunai', 'jumlah' => 25000, 'created_at' => '2026-11-10 09:30:00']);

        $data = app(KeuanganController::class)->kasHarian(Request::create('/', 'GET', ['tanggal' => '2026-11-10']))->getData();
        $groups = collect($data['groups'])->keyBy('label');

        $this->assertSame([100000.0, 0.0], [$groups['Brian']['subtotal_debet'], $groups['Brian']['subtotal_kredit']]);
        $this->assertSame(75000.0, $groups['Non-Tunai & Penyesuaian']['subtotal_kredit']);
        $this->assertStringEndsWith('· Brian', $groups['Non-Tunai & Penyesuaian']['rows'][0]['keterangan']);
        $this->assertEquals(25000, $data['saldoKas'] - (float) ($groups['-']['subtotal_debet'] ?? 0));
    }
}
