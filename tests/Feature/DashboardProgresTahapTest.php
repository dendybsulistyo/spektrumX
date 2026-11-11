<?php

namespace Tests\Feature;

use App\Models\OrderIndoor;
use App\Models\OrderIndoorDetail;
use App\Services\DashboardStatsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardProgresTahapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('kategori_produk_indoor', function (Blueprint $table) {
            $table->id();
            $table->string('KdDivs');
            $table->boolean('lewati_layout_cetak')->default(false);
        });
        Schema::create('produk_indoor', function (Blueprint $table) {
            $table->id();
            $table->string('KdProd');
            $table->string('KdDivs')->nullable();
        });
        DB::table('kategori_produk_indoor')->insert([['KdDivs' => '01', 'lewati_layout_cetak' => false], ['KdDivs' => '20', 'lewati_layout_cetak' => true]]);
        DB::table('produk_indoor')->insert([['KdProd' => '0101', 'KdDivs' => '01'], ['KdProd' => '2021', 'KdDivs' => '20']]);
    }

    /**
     * Order seperti IND.2.261111031: item cetak 750 di Layout, item Potong 29.500 langsung di Finishing.
     */
    private function order(string $status): OrderIndoor
    {
        $order = new OrderIndoor(['status' => $status]);
        $order->setRelation('items', collect([
            new OrderIndoorDetail(['KdProd' => '0101', 'jenis_produk' => 'indoor', 'Qty' => 750, 'qty_desain' => 750]),
            new OrderIndoorDetail(['KdProd' => '2021', 'jenis_produk' => 'indoor', 'Qty' => 29500, 'qty_finishing' => 29500]),
        ]));

        return $order;
    }

    private function progress(OrderIndoor $order, string $stage): ?string
    {
        return (fn () => $this->stageProgress($order, $stage))->call(app(DashboardStatsService::class));
    }

    public function test_order_baru_tidak_menampilkan_progres_tahap(): void
    {
        $this->assertNull($this->progress($this->order('baru'), 'desain'));
        $this->assertNull($this->progress($this->order('baru'), 'cetak'));
    }

    public function test_item_yang_mulai_di_finishing_tidak_dihitung_di_layout_dan_cetak(): void
    {
        $order = $this->order('desain');

        $this->assertSame('0/750', $this->progress($order, 'desain'));
        $this->assertSame('0/750', $this->progress($order, 'cetak'));
        $this->assertSame('0/30250', $this->progress($order, 'finishing'));
    }
}
