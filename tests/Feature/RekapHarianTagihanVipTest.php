<?php

namespace Tests\Feature;

use App\Http\Controllers\ReportController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RekapHarianTagihanVipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->string('NoOrder');
                $table->string('KdCust')->nullable();
                $table->string('status')->nullable();
                $table->string('status_bayar');
            });
        }
        Schema::create('order_documents', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->timestamp('issued_at')->nullable();
        });
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('KdCust');
        });
        Schema::create('customer_limits', function (Blueprint $table) {
            $table->id();
            $table->string('KdCust');
        });
        Schema::create('sales_transaction_archives', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
        });
    }

    public function test_query_tagihan_vip_berjalan_untuk_semua_jenis_rekap(): void
    {
        foreach ([null, 'lunas', 'tagihan'] as $kind) {
            $view = app(ReportController::class)->dailyTransactions(Request::create('/', 'GET', ['tanggal' => '2026-11-10', 'jenis' => $kind]));
            $this->assertSame([], $view->getData()['rows']->all());
        }
    }
}
