<?php

namespace Tests\Feature;

use App\Services\OrderCancellationRefund;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class KoreksiHangusJadiBatalTotalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('email'), $t->timestamps()]);
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('NoOrder');
                $t->string('KdCust')->nullable();
                $t->string('status');
                $t->string('status_bayar');
                $t->decimal('jumlah_dibayar', 15, 2)->default(0);
                $t->decimal('jumlah_piutang', 15, 2)->default(0);
                $t->timestamp('invoice_voided_at')->nullable();
                $t->unsignedBigInteger('replacement_order_id')->nullable();
                $t->timestamp('cancel_approved_at')->nullable();
                $t->unsignedBigInteger('cancel_approved_by')->nullable();
                $t->timestamps();
            });
        }
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
    }

    private function voidedOrder(array $attributes = []): int
    {
        return DB::table('order_indoor')->insertGetId($attributes + [
            'NoOrder' => 'IND.2.261103072', 'status' => 'batal', 'status_bayar' => 'lunas',
            'jumlah_dibayar' => 47800, 'invoice_voided_at' => now(),
        ]);
    }

    private function commandOptions(array $extra = []): array
    {
        return array_merge(['nomor' => 'IND.2.261103072', '--refund' => '47800', '--cara' => 'tunai', '--user' => 'dendy@example.test'], $extra);
    }

    public function test_moves_voided_invoice_to_full_cancellation_with_refund(): void
    {
        $id = $this->voidedOrder();
        $service = Mockery::mock(OrderCancellationRefund::class);
        $service->shouldReceive('process')->once()
            ->withArgs(fn ($order, $type, $refund) => $order->id === $id && $order->invoice_voided_at === null && $type === 'indoor'
                && $refund === ['refund' => 47800.0, 'cara_bayar' => 'tunai', 'no_referensi' => null])
            ->andReturn('Refund Rp 47.800 (Tunai).');
        $this->app->instance(OrderCancellationRefund::class, $service);

        $this->artisan('spektrum:koreksi-hangus-batal', $this->commandOptions(['--jalankan' => true]))
            ->expectsConfirmation('Lanjutkan koreksi?', 'yes')
            ->assertSuccessful();

        $order = DB::table('order_indoor')->find($id);
        $this->assertSame(['batal', null], [$order->status, $order->invoice_voided_at]);
        $note = DB::table('order_status_notes')->sole();
        $this->assertStringContainsString('Refund Rp 47.800', $note->catatan);
        $this->assertSame(1, (int) $note->user_id);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $id = $this->voidedOrder();
        $service = Mockery::mock(OrderCancellationRefund::class);
        $service->shouldNotReceive('process');
        $this->app->instance(OrderCancellationRefund::class, $service);

        $this->artisan('spektrum:koreksi-hangus-batal', $this->commandOptions())->assertSuccessful();

        $this->assertNotNull(DB::table('order_indoor')->find($id)->invoice_voided_at);
        $this->assertSame(0, DB::table('order_status_notes')->count());
    }

    public function test_refuses_order_that_already_has_official_replacement(): void
    {
        $this->voidedOrder(['replacement_order_id' => 99]);

        $this->artisan('spektrum:koreksi-hangus-batal', $this->commandOptions(['--jalankan' => true]))->assertFailed();

        $this->assertNotNull(DB::table('order_indoor')->value('invoice_voided_at'));
    }

    public function test_refuses_refund_above_paid_amount(): void
    {
        $this->voidedOrder();

        $this->artisan('spektrum:koreksi-hangus-batal', $this->commandOptions(['--refund' => '50000', '--jalankan' => true]))->assertFailed();
    }

    public function test_cancels_picked_up_order_with_flag_and_reason(): void
    {
        $id = $this->voidedOrder(['NoOrder' => 'IND.2.261103022', 'status' => 'siap_diambil', 'jumlah_dibayar' => 39800, 'invoice_voided_at' => null]);
        $service = Mockery::mock(OrderCancellationRefund::class);
        $service->shouldReceive('process')->once()
            ->withArgs(fn ($order, $type, $refund) => $order->status === 'batal' && $refund['refund'] === 39800.0)
            ->andReturn('Refund Rp 39.800 (Tunai).');
        $this->app->instance(OrderCancellationRefund::class, $service);

        $this->artisan('spektrum:koreksi-hangus-batal', $this->commandOptions([
            'nomor' => 'IND.2.261103022', '--refund' => '39800', '--bukan-hangus' => true,
            '--keterangan' => 'Complaint, diganti nota 040.', '--jalankan' => true,
        ]))->expectsConfirmation('Lanjutkan koreksi?', 'yes')->assertSuccessful();

        $order = DB::table('order_indoor')->find($id);
        $this->assertSame(['batal', 1], [$order->status, (int) $order->cancel_approved_by]);
        $this->assertStringContainsString('Complaint, diganti nota 040.', DB::table('order_status_notes')->value('catatan'));
    }

    public function test_active_order_requires_flag_and_reason(): void
    {
        $this->voidedOrder(['status' => 'siap_diambil', 'invoice_voided_at' => null]);

        $this->artisan('spektrum:koreksi-hangus-batal', $this->commandOptions(['--jalankan' => true]))->assertFailed();
        $this->artisan('spektrum:koreksi-hangus-batal', $this->commandOptions(['--bukan-hangus' => true, '--jalankan' => true]))->assertFailed();

        $this->assertSame('siap_diambil', DB::table('order_indoor')->value('status'));
    }
}
