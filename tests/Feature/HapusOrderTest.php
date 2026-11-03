<?php

namespace Tests\Feature;

use App\Models\DeletedOrderLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HapusOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->string('NoOrder');
                $table->string('KdCust');
                $table->string('status');
                $table->string('status_bayar');
                $table->decimal('jumlah_piutang', 15, 2)->default(0);
            });
            Schema::create($table.'_detail', function (Blueprint $blueprint) use ($table) {
                $blueprint->id();
                $blueprint->unsignedBigInteger($table.'_id');
            });
        }
        Schema::create('am', function (Blueprint $table) {
            $table->id();
            $table->string('Bukti');
            $table->date('TgTrans');
        });
        Schema::create('periode_tutup_buku', function (Blueprint $table) {
            $table->id();
            $table->string('periode');
        });
        Schema::create('customer_limits', function (Blueprint $table) {
            $table->id();
            $table->string('KdCust');
            $table->decimal('Total', 15, 2);
        });
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->uuid('batch_id')->nullable();
        });
        Schema::create('order_documents', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
        });
    }

    public function test_deletes_only_the_given_order_and_releases_vip_credit(): void
    {
        (require database_path('migrations/2026_11_03_121217_create_deleted_order_logs_table.php'))->up();
        $dummy = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.261103018', 'KdCust' => 'EST', 'status' => 'desain', 'status_bayar' => 'hutang', 'jumlah_piutang' => 150000]);
        $other = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.261103019', 'KdCust' => 'EST', 'status' => 'desain', 'status_bayar' => 'hutang', 'jumlah_piutang' => 50000]);
        DB::table('order_indoor_detail')->insert([['order_indoor_id' => $dummy], ['order_indoor_id' => $other]]);
        DB::table('am')->insert([['Bukti' => 'IND.2.261103018', 'TgTrans' => '2026-11-03'], ['Bukti' => 'IND.2.261103018', 'TgTrans' => '2026-11-03'], ['Bukti' => 'IND.2.261103019', 'TgTrans' => '2026-11-03']]);
        DB::table('order_documents')->insert(['order_type' => 'indoor', 'order_id' => $dummy]);
        DB::table('customer_limits')->insert(['KdCust' => 'EST', 'Total' => 200000]);

        $this->artisan('spektrum:hapus-order', ['nomor' => ['IND.2.261103018'], '--jalankan' => true, '--alasan' => 'Dummy uji stand banner', '--oleh' => 'dendy'])
            ->expectsQuestion('Ketik HAPUS untuk melanjutkan', 'HAPUS')
            ->assertSuccessful();

        $log = DeletedOrderLog::sole();
        $this->assertSame(['IND.2.261103018', 'Dummy uji stand banner', 'dendy', 150000.0], [$log->no_order, $log->alasan, $log->dihapus_oleh, $log->kredit_dikembalikan]);
        $this->assertCount(2, $log->snapshot['journals']);
        $this->assertCount(1, $log->snapshot['items']);

        $this->assertSame([$other], DB::table('order_indoor')->pluck('id')->all());
        $this->assertSame(1, DB::table('order_indoor_detail')->count());
        $this->assertSame(['IND.2.261103019'], DB::table('am')->pluck('Bukti')->all());
        $this->assertSame(0, DB::table('order_documents')->count());
        $this->assertEquals(50000, DB::table('customer_limits')->value('Total'));
    }

    public function test_dry_run_changes_nothing(): void
    {
        DB::table('order_indoor')->insert(['NoOrder' => 'IND.2.261103018', 'KdCust' => 'EST', 'status' => 'desain', 'status_bayar' => 'lunas']);

        $this->artisan('spektrum:hapus-order', ['nomor' => ['IND.2.261103018']])->assertSuccessful();

        $this->assertSame(1, DB::table('order_indoor')->count());
    }

    public function test_refuses_to_delete_without_archive_table(): void
    {
        DB::table('order_indoor')->insert(['NoOrder' => 'IND.2.261103018', 'KdCust' => 'EST', 'status' => 'desain', 'status_bayar' => 'lunas']);

        $this->artisan('spektrum:hapus-order', ['nomor' => ['IND.2.261103018'], '--jalankan' => true, '--alasan' => 'x'])->assertFailed();

        $this->assertSame(1, DB::table('order_indoor')->count());
    }

    public function test_refuses_when_journal_period_is_closed(): void
    {
        DB::table('order_indoor')->insert(['NoOrder' => 'IND.2.261103018', 'KdCust' => 'EST', 'status' => 'desain', 'status_bayar' => 'lunas']);
        DB::table('am')->insert(['Bukti' => 'IND.2.261103018', 'TgTrans' => '2026-11-03']);
        DB::table('periode_tutup_buku')->insert(['periode' => '2026-11']);

        $this->artisan('spektrum:hapus-order', ['nomor' => ['IND.2.261103018'], '--jalankan' => true])->assertFailed();

        $this->assertSame(1, DB::table('order_indoor')->count());
        $this->assertSame(1, DB::table('am')->count());
    }
}
