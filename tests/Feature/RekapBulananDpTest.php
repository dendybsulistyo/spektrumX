<?php

namespace Tests\Feature;

use App\Http\Controllers\MonthlyTransactionController;
use App\Services\OrderPricingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RekapBulananDpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('customers', fn (Blueprint $t) => [$t->id(), $t->string('KdCust'), $t->string('NmCust')]);
        Schema::create('customer_limits', fn (Blueprint $t) => [$t->id(), $t->string('KdCust')]);
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('NoOrder');
                $t->string('KdCust');
                $t->date('TglOrder');
                $t->string('status')->default('selesai');
                $t->string('status_bayar');
                $t->decimal('total', 15, 2);
            });
        }
        Schema::create('order_indoor_detail', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('order_indoor_id')]);
        Schema::create('order_payments', fn (Blueprint $t) => [$t->id(), $t->string('order_type'), $t->unsignedBigInteger('order_id'), $t->decimal('jumlah', 15, 2)]);
        Schema::create('order_documents', fn (Blueprint $t) => [$t->id(), $t->string('order_type'), $t->unsignedBigInteger('order_id'), $t->string('kind'), $t->string('number'), $t->integer('sequence')->default(1)]);
        DB::table('customers')->insert(['KdCust' => 'C1', 'NmCust' => 'Umum']);

        $this->mock(OrderPricingService::class)->shouldReceive('detailedLineItems')->andReturn(collect());
    }

    private function totals(string $month): object
    {
        return app(MonthlyTransactionController::class)->index(Request::create('/', 'GET', ['bulan' => $month]))->getData()['totals'];
    }

    public function test_nota_dp_ikut_dihitung_mulai_november_2026(): void
    {
        foreach (['2026-10-20', '2026-11-06'] as $i => $date) {
            DB::table('order_indoor')->insert(['NoOrder' => "IND.LUNAS.{$i}", 'KdCust' => 'C1', 'TglOrder' => $date, 'status_bayar' => 'lunas', 'total' => 100000]);
            $dp = DB::table('order_indoor')->insertGetId(['NoOrder' => "IND.DP.{$i}", 'KdCust' => 'C1', 'TglOrder' => $date, 'status_bayar' => 'dp', 'total' => 50000]);
            DB::table('order_payments')->insert(['order_type' => 'indoor', 'order_id' => $dp, 'jumlah' => 20000]);
        }

        $november = $this->totals('2026-11');
        $this->assertSame([150000.0, 120000.0, 30000.0], [$november->total, $november->cash, $november->credit]);

        $oktober = $this->totals('2026-10');
        $this->assertSame([100000.0, 100000.0, 0.0], [$oktober->total, $oktober->cash, $oktober->credit]);
    }
}
