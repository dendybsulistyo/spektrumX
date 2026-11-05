<?php

namespace Tests\Feature;

use App\Http\Controllers\ApprovalHistoryController;
use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class ApprovalHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name')]);
        Schema::create('customers', fn (Blueprint $t) => [$t->id(), $t->string('KdCust'), $t->string('NmCust')]);
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('NoOrder');
                $t->string('KdCust')->nullable();
                foreach (['diskon', 'hutang'] as $kind) {
                    $t->timestamp($kind.'_requested_at')->nullable();
                    foreach (['requested', 'approved', 'rejected'] as $step) {
                        $t->unsignedBigInteger("{$kind}_{$step}_by")->nullable();
                    }
                    $t->timestamp($kind.'_approved_at')->nullable();
                    $t->timestamp($kind.'_rejected_at')->nullable();
                }
                $t->string('diskon_tipe')->nullable();
                $t->decimal('diskon_requested_persen', 5, 2)->nullable();
                $t->decimal('diskon_requested_nominal', 15, 2)->nullable();
                $t->string('diskon_alasan')->nullable();
                $t->string('hutang_catatan')->nullable();
            });
        }
        Schema::create('order_status_notes', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->unsignedBigInteger('order_id');
            $t->string('stage');
            $t->string('action');
            $t->text('catatan')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('reprint_requests', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 3);
            $t->string('order_type', 10);
            $t->unsignedBigInteger('order_id');
            $t->string('reason');
            $t->string('status', 10)->default('pending');
            $t->unsignedBigInteger('requested_by');
            $t->unsignedBigInteger('decided_by')->nullable();
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
        });
        Schema::create('approval_requests', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 10);
            $t->string('order_type', 10);
            $t->unsignedBigInteger('order_id');
            $t->string('detail')->nullable();
            $t->string('reason')->nullable();
            $t->string('status', 10)->default('pending');
            $t->unsignedBigInteger('requested_by')->nullable();
            $t->timestamp('requested_at');
            $t->unsignedBigInteger('decided_by')->nullable();
            $t->timestamp('decided_at')->nullable();
        });
    }

    public function test_menampilkan_peminta_dan_pemutus_semua_jenis_approval(): void
    {
        $this->travelTo('2026-11-05 10:00:00');
        $brian = DB::table('users')->insertGetId(['name' => 'Brian']);
        $yovita = DB::table('users')->insertGetId(['name' => 'Yovita']);
        DB::table('customers')->insert(['KdCust' => 'C1', 'NmCust' => 'Kids Fun']);
        $order = DB::table('order_indoor')->insertGetId(['NoOrder' => 'IND.2.261103072', 'KdCust' => 'C1']);

        DB::table('order_status_notes')->insert([
            ['order_type' => 'indoor', 'order_id' => $order, 'stage' => 'pembatalan', 'action' => 'diajukan', 'catatan' => 'ganti bahan', 'user_id' => $brian, 'created_at' => '2026-11-03 20:33:00'],
            ['order_type' => 'indoor', 'order_id' => $order, 'stage' => 'pembatalan', 'action' => 'disetujui', 'catatan' => null, 'user_id' => $yovita, 'created_at' => '2026-11-04 09:40:00'],
        ]);
        DB::table('reprint_requests')->insert(['kind' => 'inv', 'order_type' => 'indoor', 'order_id' => $order, 'reason' => 'kertas macet',
            'status' => 'used', 'requested_by' => $brian, 'decided_by' => $yovita, 'decided_at' => '2026-11-04 11:00:00', 'created_at' => '2026-11-04 10:55:00']);
        $this->actingAs((new User(['name' => 'Brian']))->forceFill(['id' => $brian]));
        ApprovalRequest::open('diskon', 'indoor', $order, '10%', 'langganan');
        $this->actingAs((new User(['name' => 'Yovita']))->forceFill(['id' => $yovita]));
        ApprovalRequest::decide('diskon', 'indoor', $order, false);

        $view = app(ApprovalHistoryController::class)->index(Request::create('/', 'GET', ['dari' => '2026-11-01', 'sampai' => '2026-11-05']));
        $rows = $view->getData()['rows']->keyBy('kind');

        $this->assertCount(3, $rows);
        $this->assertSame(['Brian', 'Yovita', 'approved', 'IND.2.261103072', 'Kids Fun'], [
            $rows['pembatalan']->requested_name, $rows['pembatalan']->decided_name, $rows['pembatalan']->status,
            $rows['pembatalan']->no_order, $rows['pembatalan']->customer,
        ]);
        $this->assertSame(['rejected', 'Yovita'], [$rows['diskon']->status, $rows['diskon']->decided_name]);
        $this->assertSame('approved', $rows['cetak_ulang']->status);
        view()->share('errors', new ViewErrorBag);
        $this->assertStringContainsString('IND.2.261103072', $view->render());
    }
}
