<?php

namespace Tests\Feature;

use App\Http\Controllers\OrderReworkController;
use App\Models\OrderDocument;
use App\Models\OrderIndoor;
use App\Models\OrderIndoorDetail;
use App\Services\AccountingService;
use App\Services\CustomerCreditService;
use App\Services\DeliveryOrderService;
use App\Services\OrderDocumentService;
use App\Services\OrderPaymentWorkflow;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OrderDocumentsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['indoor', 'outdoor', 'artwork'] as $type) {
            Schema::create('order_'.$type, function (Blueprint $t) {
                $t->id();
                $t->string('NoOrder');
                $t->string('KdCust')->nullable();
                $t->string('status')->default('siap_diambil');
                $t->string('status_bayar')->default('lunas');
                $t->decimal('total')->default(1000);
                $t->decimal('jumlah_dibayar')->default(1000);
                $t->decimal('jumlah_piutang')->default(0);
                $t->timestamp('dibayar_at')->nullable();
                $t->timestamp('invoice_voided_at')->nullable();
                $t->timestamp('cancel_requested_at')->nullable();
                $t->timestamp('diambil_at')->nullable();
                $t->integer('pengambilan_by')->nullable();
                $t->timestamp('siap_diambil_at')->nullable();
                $t->integer('siap_diambil_by')->nullable();
            });
        }
        Schema::create('order_indoor_detail', function (Blueprint $t) {
            $t->id();
            $t->integer('order_indoor_id');
            $t->string('Judul')->default('Banner');
            $t->integer('Qty')->default(10);
            foreach (['desain', 'cetak', 'finishing', 'qc', 'bungkus', 'siap_diambil', 'selesai'] as $stage) {
                $t->integer('qty_'.$stage)->default(0);
            }
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('KdCust');
            $t->string('NmCust');
            $t->string('Alamat')->nullable();
        });
        Schema::create('customer_limits', function (Blueprint $t) {
            $t->id();
            $t->string('KdCust');
            $t->decimal('Batas');
            $t->decimal('Total');
        });
        Schema::create('order_rework_requests', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->integer('order_id');
            $t->string('status');
        });
        Schema::create('order_status_notes', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->integer('order_id');
            $t->integer('order_detail_id');
            $t->integer('qty');
            $t->string('stage');
            $t->string('action');
            $t->text('catatan');
            $t->integer('user_id');
            $t->timestamp('created_at');
        });
        Schema::create('order_pickup_signatures', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->integer('order_id');
            $t->integer('order_detail_id');
            $t->integer('qty');
            foreach (['nama_penerima', 'kontak_penerima', 'signature_path', 'signature_hash'] as $field) {
                $t->string($field);
            }
            $t->integer('received_by');
            $t->timestamp('received_at');
            $t->timestamps();
        });
        (require database_path('migrations/2026_09_08_000003_create_order_documents.php'))->up();
    }

    private function order(array $attributes = []): OrderIndoor
    {
        $order = OrderIndoor::create($attributes + ['NoOrder' => 'IND.2.26082800001', 'dibayar_at' => '2026-09-08 10:00:00']);
        OrderIndoorDetail::create(['order_indoor_id' => $order->id, 'Qty' => 10, 'qty_siap_diambil' => 10]);

        return $order->fresh();
    }

    public function test_two_pickups_create_two_dos_and_retry_does_not_move_stock_twice(): void
    {
        $order = $this->order();
        $item = $order->items->first();
        $service = new DeliveryOrderService;
        $data = ['request_key' => (string) Str::uuid(), 'qty' => 5, 'nama_penerima' => 'Budi', 'kontak_penerima' => '08123'];
        $first = $service->receive($item, 'indoor', $data, 'sig.svg', 'hash', 1);
        $retry = $service->receive($item, 'indoor', $data, 'sig2.svg', 'hash', 1);
        $this->assertSame($first->id, $retry->id);
        $this->assertSame(5, $item->fresh()->qty_selesai);
        $data['request_key'] = (string) Str::uuid();
        $second = $service->receive($item, 'indoor', $data, 'sig3.svg', 'hash', 1);
        $this->assertSame('DO.2.26082800001-1', $first->number);
        $this->assertSame('DO.2.26082800001-2', $second->number);
        $this->assertSame(10, $item->fresh()->qty_selesai);
        $this->assertSame('selesai', $order->fresh()->status);
        $this->assertDatabaseCount('order_pickup_signatures', 2);
        $this->assertDatabaseCount('order_status_notes', 2);
        $this->assertSame(0, OrderDocument::where('kind', 'inv')->count());
    }

    public function test_invoice_is_issued_once_at_settlement_before_pickup_and_snapshot_is_stable(): void
    {
        $order = $this->order(['status_bayar' => 'dp', 'jumlah_piutang' => 500, 'jumlah_dibayar' => 500]);
        $service = new OrderDocumentService;
        $this->assertNull($service->issueInvoice($order));
        (new OrderPaymentWorkflow)->run($order, 'dp', fn ($locked) => $locked->update(['status_bayar' => 'lunas', 'jumlah_piutang' => 0, 'jumlah_dibayar' => 1000, 'dibayar_at' => now()]));
        $invoice = $service->issueInvoice($order->fresh());
        $this->assertSame('INV.2.26082800001', $invoice->number);
        $this->assertDatabaseCount('order_documents', 1);
        $this->assertSame(0, $order->items->first()->qty_selesai);
        $order->update(['jumlah_dibayar' => 2000]);
        $this->assertSame('1000.00', $service->issueInvoice($order)->total);
        $this->assertSame(1, $service->invoiceQuery()->count());
        $order->update(['status' => 'batal']);
        $this->assertSame(0, $service->invoiceQuery()->count());
    }

    public function test_vip_credit_can_pick_up_without_invoice_or_ppn(): void
    {
        DB::table('customers')->insert(['KdCust' => 'VIP', 'NmCust' => 'VIP']);
        DB::table('customer_limits')->insert(['KdCust' => 'VIP', 'Batas' => 10000, 'Total' => 1000]);
        $order = $this->order(['KdCust' => 'VIP', 'status_bayar' => 'hutang', 'jumlah_piutang' => 1000, 'jumlah_dibayar' => 0]);
        (new DeliveryOrderService)->receive($order->items->first(), 'indoor', ['request_key' => (string) Str::uuid(), 'qty' => 5, 'nama_penerima' => 'VIP', 'kontak_penerima' => '081'], 'sig.svg', 'hash', 1);
        $this->assertNull((new OrderDocumentService)->issueInvoice($order));
        $this->assertSame(0, (new OrderDocumentService)->invoiceQuery()->count());
        $this->assertDatabaseCount('order_documents', 1);
    }

    public function test_regular_dp_cannot_pick_up(): void
    {
        $order = $this->order(['status_bayar' => 'dp', 'jumlah_piutang' => 500]);
        try {
            (new DeliveryOrderService)->receive($order->items->first(), 'indoor', ['request_key' => (string) Str::uuid(), 'qty' => 5, 'nama_penerima' => 'A', 'kontak_penerima' => '081'], 'sig.svg', 'hash', 1);
            $this->fail('DP pickup should be rejected.');
        } catch (HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertDatabaseCount('order_documents', 0);
        $this->assertSame(0, $order->items->first()->fresh()->qty_selesai);
    }

    public function test_document_failure_rolls_back_pickup_and_signature(): void
    {
        $order = $this->order();
        OrderDocument::create(['kind' => 'do', 'order_type' => 'outdoor', 'order_id' => 99, 'sequence' => 1, 'number' => 'DO.2.26082800001-1', 'issued_at' => now(), 'snapshot' => []]);
        try {
            (new DeliveryOrderService)->receive($order->items->first(), 'indoor', ['request_key' => (string) Str::uuid(), 'qty' => 5, 'nama_penerima' => 'A', 'kontak_penerima' => '081'], 'sig.svg', 'hash', 1);
            $this->fail('Duplicate number must abort.');
        } catch (QueryException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
        $this->assertSame(0, $order->items->first()->fresh()->qty_selesai);
        $this->assertDatabaseCount('order_pickup_signatures', 0);
        $this->assertDatabaseCount('order_status_notes', 0);
    }

    public function test_multiple_items_in_one_trip_share_one_delivery_order(): void
    {
        $order = $this->order();
        $second = OrderIndoorDetail::create(['order_indoor_id' => $order->id, 'Qty' => 3, 'qty_siap_diambil' => 3]);
        $data = ['request_key' => (string) Str::uuid(), 'qty' => 5, 'nama_penerima' => 'A', 'kontak_penerima' => '081',
            'items' => [['id' => $order->items->first()->id, 'qty' => 5], ['id' => $second->id, 'qty' => 3]]];
        $document = (new DeliveryOrderService)->receive($order->items->first(), 'indoor', $data, 'sig.svg', 'hash', 1);
        $this->assertCount(2, $document->snapshot['items']);
        $this->assertDatabaseCount('order_documents', 1);
        $this->assertDatabaseCount('order_pickup_signatures', 2);
        $this->assertSame(3, $second->fresh()->qty_selesai);
    }

    public function test_invoice_failure_rolls_back_settlement(): void
    {
        $order = $this->order(['status_bayar' => 'dp', 'jumlah_piutang' => 500, 'jumlah_dibayar' => 500]);
        OrderDocument::create(['kind' => 'inv', 'order_type' => 'outdoor', 'order_id' => 99, 'sequence' => 1, 'number' => 'INV.2.26082800001', 'issued_at' => now(), 'snapshot' => []]);
        try {
            (new OrderPaymentWorkflow)->run($order, 'dp', fn ($locked) => $locked->update(['status_bayar' => 'lunas', 'jumlah_piutang' => 0, 'jumlah_dibayar' => 1000]));
            $this->fail('Invoice collision must abort settlement.');
        } catch (QueryException $e) {
            $this->assertNotEmpty($e->getMessage());
        }
        $this->assertSame('dp', $order->fresh()->status_bayar);
        $this->assertSame(500.0, (float) $order->fresh()->jumlah_piutang);
    }

    public function test_dp_refund_reverses_deposit_liability_instead_of_sales(): void
    {
        Schema::table('order_indoor', function (Blueprint $t) {
            $t->string('metode_bayar')->nullable();
            $t->string('cara_bayar')->nullable();
        });
        Schema::create('order_payments', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->integer('order_id');
            $t->string('jenis');
            $t->decimal('jumlah');
            $t->string('cara_bayar');
            $t->string('no_referensi')->nullable();
            $t->integer('user_id')->nullable();
            $t->timestamp('created_at');
        });
        $order = $this->order(['status_bayar' => 'dp', 'metode_bayar' => 'dp', 'cara_bayar' => 'tunai', 'jumlah_dibayar' => 500, 'jumlah_piutang' => 500]);
        $accounting = \Mockery::mock(AccountingService::class);
        $accounting->shouldReceive('post')->once()->withArgs(function ($date, $number, $description, $lines) {
            $this->assertSame('27100', $lines[0]['akun']);
            $this->assertSame(500.0, $lines[0]['debet']);
            $this->assertSame(500.0, $lines[1]['kredit']);

            return true;
        })->andReturn('TEST');
        $controller = new OrderReworkController($accounting, new CustomerCreditService);
        (new \ReflectionMethod($controller, 'refundCancelledOrder'))->invoke($controller, $order, 'indoor');
        $this->assertDatabaseHas('order_payments', ['jenis' => 'refund', 'jumlah' => -500]);
        $this->assertSame(0.0, (float) $order->fresh()->jumlah_dibayar);
    }

    public function test_invalid_second_item_rolls_back_entire_delivery(): void
    {
        $order = $this->order();
        $first = $order->items->first();
        $second = OrderIndoorDetail::create(['order_indoor_id' => $order->id, 'Qty' => 3, 'qty_siap_diambil' => 3]);
        $data = ['request_key' => (string) Str::uuid(), 'qty' => 5, 'nama_penerima' => 'A', 'kontak_penerima' => '081',
            'items' => [['id' => $first->id, 'qty' => 5], ['id' => $second->id, 'qty' => 4]]];
        try {
            (new DeliveryOrderService)->receive($first, 'indoor', $data, 'sig.svg', 'hash', 1);
            $this->fail('Quantity exceeding stock must abort the entire delivery.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('qty', $e->errors());
        }
        $this->assertSame(0, $first->fresh()->qty_selesai);
        $this->assertSame(0, $second->fresh()->qty_selesai);
        $this->assertDatabaseCount('order_documents', 0);
        $this->assertDatabaseCount('order_pickup_signatures', 0);
    }

    public function test_demo_origin_is_preserved_and_demo_invoices_remain_in_recap(): void
    {
        $order = $this->order();
        $documents = new OrderDocumentService;
        $invoice = $documents->issueInvoice($order, '2026-01-05 12:00:00', 'historical_demo');
        $this->assertSame('historical_demo', $invoice->snapshot['origin']);
        $this->assertSame('historical_demo', $documents->issueInvoice($order)->snapshot['origin']);
        $this->assertSame(1, $documents->invoiceQuery()->count());
        $this->assertSame(0, $documents->invoiceQuery()->where('issued_at', '>=', '2026-09-01')->count());
    }

    public function test_vip_partial_pickups_then_settlement_create_one_invoice_without_extra_journals(): void
    {
        Schema::create('am__', function (Blueprint $t) {
            $t->string('NoAkun');
            $t->string('TipeDK');
        });
        Schema::create('am', function (Blueprint $t) {
            foreach (['Perio', 'NoTrans', 'TgTrans', 'Bukti', 'KetMT', 'NoAkun', 'KdBantu'] as $field) {
                $t->string($field);
            }
            $t->decimal('Debet', 18, 2);
            $t->decimal('Kredit', 18, 2);
        });
        DB::table('am__')->insert([
            ['NoAkun' => '11100', 'TipeDK' => 'D'], ['NoAkun' => '11102', 'TipeDK' => 'D'], ['NoAkun' => '41000', 'TipeDK' => 'K'],
        ]);
        DB::table('customers')->insert(['KdCust' => 'VIP', 'NmCust' => 'VIP']);
        DB::table('customer_limits')->insert(['KdCust' => 'VIP', 'Batas' => 10000, 'Total' => 1000]);
        $order = $this->order(['KdCust' => 'VIP', 'status_bayar' => 'hutang', 'jumlah_piutang' => 1000, 'jumlah_dibayar' => 0]);
        $accounting = new AccountingService;
        $accounting->post('2026-09-01', $order->NoOrder, 'Penjualan kredit uji', [
            ['akun' => '11102', 'debet' => 1000], ['akun' => '41000', 'kredit' => 1000],
        ]);
        $documents = new OrderDocumentService;
        $item = $order->items->first();
        foreach (['2026-09-07 10:00:00', '2026-09-09 10:00:00'] as $date) {
            $this->travelTo(Carbon::parse($date));
            (new DeliveryOrderService)->receive($item, 'indoor', ['request_key' => (string) Str::uuid(), 'qty' => 5, 'nama_penerima' => 'VIP', 'kontak_penerima' => '081'], 'sig.svg', 'hash', 1);
            $this->assertSame(0, $documents->invoiceQuery()->count());
            $this->assertDatabaseCount('am', 2);
        }
        $this->travelTo(Carbon::parse('2026-09-10 10:00:00'));
        $pay = function ($locked) use ($accounting) {
            $locked->update(['status_bayar' => 'lunas', 'jumlah_piutang' => 0, 'jumlah_dibayar' => 1000, 'dibayar_at' => now()]);
            $locked->customer->limit->decrement('Total', 1000);
            $accounting->post('2026-09-10', $locked->NoOrder, 'Pelunasan kredit uji', [
                ['akun' => '11100', 'debet' => 1000], ['akun' => '11102', 'kredit' => 1000],
            ]);
        };
        (new OrderPaymentWorkflow)->run($order, 'hutang', $pay);
        $invoice = OrderDocument::where('kind', 'inv')->sole();
        $this->assertSame('operational', $invoice->snapshot['origin']);
        $this->assertSame('2026-09-10', $invoice->issued_at->toDateString());
        $this->assertSame(1, $documents->invoiceQuery()->where('issued_at', '>=', '2026-09-10')->count());
        $this->assertDatabaseCount('am', 4);
        $this->assertEquals(DB::table('am')->sum('Debet'), DB::table('am')->sum('Kredit'));
        $this->assertEquals(0, DB::table('customer_limits')->where('KdCust', 'VIP')->value('Total'));
        try {
            (new OrderPaymentWorkflow)->run($order, 'hutang', $pay);
            $this->fail('Repeated settlement must be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('pembayaran', $e->errors());
        }
        $this->assertDatabaseCount('am', 4);
        $this->assertSame(1, OrderDocument::where('kind', 'inv')->count());
        $this->travelBack();
    }
}
