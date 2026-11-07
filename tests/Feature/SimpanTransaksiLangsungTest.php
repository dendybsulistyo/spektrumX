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
            $table->string('number')->nullable();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
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

    public function test_simpan_mengubah_tanggal_invoice_sekali_saja(): void
    {
        $order = $this->paidOrder('IND.2.004', printed: true);
        $this->travelTo('2026-11-05 10:00:00');

        app(PengambilanController::class)->archiveTransactions(Request::create('/', 'POST', ['transaksi' => ["indoor:{$order}"]]));
        $this->travelTo('2026-11-06 09:00:00');
        app(PengambilanController::class)->archiveTransactions(Request::create('/', 'POST', ['transaksi' => ["indoor:{$order}"]]));

        $this->assertSame('2026-11-05 10:00:00', (string) DB::table('order_documents')->where('order_id', $order)->value('issued_at'));
    }

    public function test_command_menyamakan_tanggal_invoice_lama_dengan_tanggal_simpan(): void
    {
        $order = $this->paidOrder('IND.2.005', printed: true);
        DB::table('sales_transaction_archives')->insert(['order_type' => 'indoor', 'order_id' => $order, 'archived_at' => '2026-11-04 15:30:00']);

        $this->artisan('spektrum:samakan-tanggal-invoice-simpan')->assertSuccessful();
        $this->assertSame('2026-11-03 08:00:00', (string) DB::table('order_documents')->where('order_id', $order)->value('issued_at'));

        $this->artisan('spektrum:samakan-tanggal-invoice-simpan', ['--jalankan' => true])
            ->expectsConfirmation('Ubah tanggal 1 invoice di atas?', 'yes')->assertSuccessful();
        $this->assertSame('2026-11-04 15:30:00', (string) DB::table('order_documents')->where('order_id', $order)->value('issued_at'));
    }

    public function test_plafon_vip_boleh_disimpan_sebelum_lunas_dan_tanggal_invoice_ikut_simpan(): void
    {
        $order = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.006', 'status_bayar' => 'hutang', 'jumlah_piutang' => 75000, 'jumlah_dibayar' => 0]);
        $document = DB::table('order_documents')->insertGetId(['kind' => 'inv', 'order_type' => 'indoor', 'order_id' => $order, 'issued_at' => '2026-11-03 08:00:00']);
        DB::table('order_document_prints')->insert(['order_document_id' => $document]);
        $this->travelTo('2026-11-07 14:00:00');

        app(PengambilanController::class)->archiveTransactions(Request::create('/', 'POST', ['transaksi' => ["indoor:{$order}"]]));

        $this->assertSame([$order], DB::table('sales_transaction_archives')->pluck('order_id')->all());
        $this->assertSame('2026-11-07 14:00:00', (string) DB::table('order_documents')->where('order_id', $order)->value('issued_at'));
        $this->assertSame(['hutang', 75000.0], [DB::table('order_indoor')->where('id', $order)->value('status_bayar'), (float) DB::table('order_indoor')->where('id', $order)->value('jumlah_piutang')]);
    }

    private function paidOrder(string $number, bool $printed): int
    {
        $id = DB::table('order_indoor')->insertGetId(['NoOrder' => $number, 'status_bayar' => 'lunas', 'jumlah_dibayar' => 10000]);
        DB::table('order_payments')->insert(['order_type' => 'indoor', 'order_id' => $id, 'jumlah' => 10000]);
        $document = DB::table('order_documents')->insertGetId(['kind' => 'inv', 'order_type' => 'indoor', 'order_id' => $id, 'issued_at' => '2026-11-03 08:00:00']);
        if ($printed) {
            DB::table('order_document_prints')->insert(['order_document_id' => $document]);
        }

        return $id;
    }
}
