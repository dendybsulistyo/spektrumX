<?php

namespace Tests\Feature;

use App\Services\OrderPaymentWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderPaymentWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('workflow_orders', function (Blueprint $table) {
            $table->id();
            $table->string('status_bayar');
            $table->string('status')->default('kasir');
            $table->timestamp('invoice_voided_at')->nullable();
        });
        Schema::create('workflow_postings', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
        });
    }

    public function test_repeated_payment_does_not_post_twice_even_with_a_stale_model(): void
    {
        foreach (['belum_bayar', 'dp', 'hutang'] as $status) {
            $order = WorkflowOrder::create(['status_bayar' => $status]);
            $workflow = new OrderPaymentWorkflow;
            $callback = function (Model $locked) {
                $locked->update(['status_bayar' => 'lunas']);
                DB::table('workflow_postings')->insert([['kind' => 'payment'], ['kind' => 'journal']]);
            };
            $workflow->run($order, $status, $callback);
            $before = DB::table('workflow_postings')->count();
            try {
                $workflow->run($order, $status, $callback);
                $this->fail('A repeated payment must be rejected.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('pembayaran', $exception->errors());
            }
            $this->assertSame($before, DB::table('workflow_postings')->count());
            $this->assertSame('lunas', $order->fresh()->status_bayar);
        }
    }

    public function test_failed_journal_rolls_back_payment_and_order_status(): void
    {
        $order = WorkflowOrder::create(['status_bayar' => 'dp']);
        try {
            (new OrderPaymentWorkflow)->run($order, 'dp', function (Model $locked) {
                $locked->update(['status_bayar' => 'lunas']);
                DB::table('workflow_postings')->insert(['kind' => 'payment']);
                throw new \RuntimeException('Journal failed');
            });
            $this->fail('Expected journal failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Journal failed', $exception->getMessage());
        }
        $this->assertSame('dp', $order->fresh()->status_bayar);
        $this->assertDatabaseCount('workflow_postings', 0);
    }

    public function test_cancelled_or_voided_order_cannot_be_paid(): void
    {
        foreach ([['status' => 'batal'], ['invoice_voided_at' => now()]] as $attributes) {
            $order = WorkflowOrder::create(['status_bayar' => 'belum_bayar'] + $attributes);
            try {
                (new OrderPaymentWorkflow)->run($order, 'belum_bayar', function () {
                    $this->fail('Cancelled order must not reach payment processing.');
                });
                $this->fail('Expected validation error.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('pembayaran', $exception->errors());
            }
        }
    }

    public function test_credit_booking_cannot_be_repeated(): void
    {
        $order = WorkflowOrder::create(['status_bayar' => 'belum_bayar']);
        $workflow = new OrderPaymentWorkflow;
        $workflow->run($order, 'belum_bayar', fn (Model $locked) => $locked->update(['status_bayar' => 'hutang']));
        $this->expectException(ValidationException::class);
        $workflow->run($order, 'belum_bayar', fn () => $this->fail('Credit booked twice.'));
    }
}

class WorkflowOrder extends Model
{
    protected $table = 'workflow_orders';

    protected $guarded = [];

    public $timestamps = false;
}
