<?php

namespace Tests\Feature;

use App\Http\Controllers\PengambilanController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SimpanTransaksiLangsungTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['order_indoor', 'order_outdoor'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->string('NoOrder');
                $table->string('status_bayar');
                $table->decimal('jumlah_piutang', 15, 2)->default(0);
                $table->decimal('jumlah_dibayar', 15, 2)->default(0);
            });
        }
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->decimal('jumlah', 15, 2);
        });
        Schema::create('order_documents', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
        });
        Schema::create('order_document_prints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_document_id');
        });
        Schema::create('sales_transaction_archives', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_saves_only_paid_transactions_whose_invoice_was_printed(): void
    {
        $printed = $this->paidOrder('IND.2.001', printed: true);
        $notPrinted = $this->paidOrder('IND.2.002', printed: false);
        $unpaid = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.003', 'status_bayar' => 'dp', 'jumlah_piutang' => 5000, 'jumlah_dibayar' => 5000]);

        $response = app(PengambilanController::class)->archiveTransactions(Request::create('/', 'POST', [
            'transaksi' => ["indoor:{$printed}", "indoor:{$notPrinted}", "indoor:{$unpaid}"],
        ]));

        $this->assertSame([$printed], DB::table('sales_transaction_archives')->pluck('order_id')->all());
        $this->assertStringContainsString('1 transaksi', session('status'));
        $this->assertStringContainsString('IND.2.002: Invoice belum dicetak', session('error'));
        $this->assertStringContainsString('IND.2.003: Transaksi belum lunas', session('error'));
        $this->assertTrue($response->isRedirect());
    }

    private function paidOrder(string $number, bool $printed): int
    {
        $id = DB::table('order_indoor')->insertGetId(['NoOrder' => $number, 'status_bayar' => 'lunas', 'jumlah_dibayar' => 10000]);
        DB::table('order_payments')->insert(['order_type' => 'indoor', 'order_id' => $id, 'jumlah' => 10000]);
        $document = DB::table('order_documents')->insertGetId(['kind' => 'inv', 'order_type' => 'indoor', 'order_id' => $id]);
        if ($printed) {
            DB::table('order_document_prints')->insert(['order_document_id' => $document]);
        }

        return $id;
    }
}
