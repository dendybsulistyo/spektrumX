<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LengkapiPembatalanProduksiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->string('NoOrder');
                $table->string('status');
                $table->timestamp('cancel_requested_at')->nullable();
                $table->unsignedBigInteger('cancel_requested_by')->nullable();
                $table->string('cancel_reason')->nullable();
                $table->timestamp('cancel_approved_at')->nullable();
                $table->unsignedBigInteger('cancel_approved_by')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
        Schema::create('order_rework_requests', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->string('action');
            $table->string('status');
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
        });
    }

    public function test_melengkapi_data_pembatalan_dari_pengajuan_batal_produksi(): void
    {
        $batal = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.261105028', 'status' => 'batal']);
        $aktif = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.261105029', 'status' => 'desain']);
        foreach ([$batal, $aktif] as $id) {
            DB::table('order_rework_requests')->insert([
                'order_type' => 'indoor', 'order_id' => $id, 'action' => 'batal', 'status' => 'approved', 'reason' => 'ganti ukuran',
                'requested_by' => 7, 'requested_at' => '2026-11-06 09:00:00', 'resolved_by' => 9, 'resolved_at' => '2026-11-06 10:40:13',
            ]);
        }

        $this->artisan('spektrum:lengkapi-batal-produksi')->assertSuccessful();
        $this->assertNull(DB::table('order_indoor')->find($batal)->cancel_approved_at);

        $this->artisan('spektrum:lengkapi-batal-produksi --jalankan')->assertSuccessful();
        $order = DB::table('order_indoor')->find($batal);
        $this->assertSame([7, 'ganti ukuran', 9, '2026-11-06 10:40:13'], [(int) $order->cancel_requested_by, $order->cancel_reason, (int) $order->cancel_approved_by, $order->cancel_approved_at]);
        $this->assertNull(DB::table('order_indoor')->find($aktif)->cancel_approved_at);

        $this->artisan('spektrum:lengkapi-batal-produksi')->expectsOutput('Tidak ada order batal produksi yang perlu dilengkapi.');
    }
}
