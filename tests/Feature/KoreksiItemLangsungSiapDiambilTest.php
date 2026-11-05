<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KoreksiItemLangsungSiapDiambilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('email'), $t->timestamps()]);
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('NoOrder');
                $t->string('status');
                foreach (['desain', 'cetak', 'finishing', 'qc', 'bungkus'] as $stage) {
                    $t->unsignedBigInteger($stage.'_by')->nullable();
                    $t->timestamp($stage.'_at')->nullable();
                }
                $t->timestamps();
            });
        }
        Schema::create('order_outdoor_detail', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_outdoor_id');
            $t->string('NmFile');
            $t->string('KdCtk')->nullable();
            $t->integer('Qty');
            foreach (['desain', 'cetak', 'finishing', 'qc', 'bungkus', 'siap_diambil', 'selesai'] as $stage) {
                $t->integer('qty_'.$stage)->default(0);
            }
            $t->timestamps();
        });
        Schema::create('bahan_cetak_outdoor', function (Blueprint $t) {
            $t->integer('NoUrut')->primary();
            $t->string('NmBhn');
            $t->string('NoCetak')->nullable();
            $t->boolean('lewati_layout_cetak')->default(false);
        });
        Schema::create('order_status_notes', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->unsignedBigInteger('order_id');
            $t->unsignedBigInteger('order_detail_id')->nullable();
            $t->integer('qty')->nullable();
            $t->string('stage');
            $t->string('action');
            $t->text('catatan')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamp('created_at')->nullable();
        });
        DB::table('users')->insert(['name' => 'Dendy', 'email' => 'dendy@example.test']);
        DB::table('bahan_cetak_outdoor')->insert([
            ['NoUrut' => 1, 'NmBhn' => 'RI/AV White Gloss', 'NoCetak' => '11', 'lewati_layout_cetak' => false],
            ['NoUrut' => 2, 'NmBhn' => 'Sandblast V170 SM', 'NoCetak' => '29', 'lewati_layout_cetak' => true],
        ]);
    }

    public function test_item_laminasi_yang_tertahan_dipindah_ke_siap_diambil(): void
    {
        $order = DB::table('order_outdoor')->insertGetId(['NoOrder' => 'OUT.1.261103017', 'status' => 'cetak', 'bungkus_at' => now()]);
        $printed = DB::table('order_outdoor_detail')->insertGetId(['order_outdoor_id' => $order, 'NmFile' => 'Stkr', 'KdCtk' => '0111', 'Qty' => 2, 'qty_siap_diambil' => 2]);
        $laminasi = DB::table('order_outdoor_detail')->insertGetId(['order_outdoor_id' => $order, 'NmFile' => 'Stkr', 'KdCtk' => '0729', 'Qty' => 2, 'qty_cetak' => 2]);

        $this->artisan('spektrum:koreksi-item-siap', ['nomor' => 'OUT.1.261103017', '--user' => 'dendy@example.test'])->assertSuccessful();
        $this->assertSame(2, (int) DB::table('order_outdoor_detail')->where('id', $laminasi)->value('qty_cetak'));

        $this->artisan('spektrum:koreksi-item-siap', ['nomor' => 'OUT.1.261103017', '--user' => 'dendy@example.test', '--jalankan' => true])
            ->expectsConfirmation('Pindahkan item di atas ke Siap Diambil?', 'yes')->assertSuccessful();

        $row = DB::table('order_outdoor_detail')->where('id', $laminasi)->first();
        $this->assertSame([0, 2], [(int) $row->qty_cetak, (int) $row->qty_siap_diambil]);
        $this->assertSame(2, (int) DB::table('order_outdoor_detail')->where('id', $printed)->value('qty_siap_diambil'));
        $this->assertSame('siap_diambil', DB::table('order_outdoor')->where('id', $order)->value('status'));
        $this->assertSame(1, DB::table('order_status_notes')->where('order_detail_id', $laminasi)->where('stage', 'cetak')->count());
    }
}
