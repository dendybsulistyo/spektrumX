<?php

namespace Tests\Feature;

use App\Models\OrderIndoor;
use App\Models\SalesOrderPrint;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EditOrderSetelahCetakSoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('order_indoor', function (Blueprint $table) {
            $table->id();
            $table->string('NoOrder');
            $table->string('status');
            $table->string('status_bayar');
        });
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
        });
        Schema::create('order_documents', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
        });
        (require database_path('migrations/2026_10_03_000000_create_sales_order_prints_table.php'))->up();
    }

    private function order(string $statusBayar = 'belum_bayar'): OrderIndoor
    {
        $id = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.261111031', 'status' => 'baru', 'status_bayar' => $statusBayar]);
        SalesOrderPrint::create(['order_type' => 'indoor', 'order_id' => $id, 'print_count' => 1, 'first_printed_at' => now(), 'last_printed_at' => now()]);

        return OrderIndoor::query()->findOrFail($id);
    }

    public function test_order_belum_bayar_yang_so_nya_sudah_dicetak_boleh_diubah_dan_cetakannya_tidak_berlaku(): void
    {
        $order = $this->order();

        $this->assertNull($order->editBlockReason());
        $this->assertNotNull($order->deleteBlockReason());

        $order->forgetSalesOrderPrint();
        $this->assertSame(0, SalesOrderPrint::query()->count());
    }

    public function test_order_dengan_dokumen_resmi_atau_pembayaran_tetap_terkunci(): void
    {
        $order = $this->order();
        DB::table('order_documents')->insert(['order_type' => 'indoor', 'order_id' => $order->id]);
        $this->assertNotNull($order->editBlockReason());

        DB::table('order_documents')->delete();
        DB::table('order_payments')->insert(['order_type' => 'indoor', 'order_id' => $order->id]);
        $this->assertNotNull($order->editBlockReason());

        $this->assertNotNull($this->order('lunas')->editBlockReason());
    }
}
