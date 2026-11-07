<?php

namespace Tests\Feature;

use App\Http\Controllers\HistoryTelatController;
use App\Services\OrderLateLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HistoryTelatOperatorTest extends TestCase
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
                $t->string('status');
                foreach (['desain', 'cetak', 'finishing', 'qc', 'bungkus'] as $stage) {
                    $t->unsignedBigInteger($stage.'_by')->nullable();
                }
            });
        }
        Schema::create('order_late_logs', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->unsignedBigInteger('order_id');
            $t->timestamp('order_created_at')->nullable();
            $t->timestamp('late_at');
            $t->string('stage')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->string('resolved_status')->nullable();
            $t->boolean('is_estimated')->default(false);
            $t->timestamps();
        });
        Schema::create('order_status_notes', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->unsignedBigInteger('order_id');
            $t->integer('qty')->nullable();
            $t->string('stage');
            $t->string('action');
            $t->text('catatan')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('order_late_reasons', fn (Blueprint $t) => [$t->id(), $t->string('order_type'), $t->unsignedBigInteger('order_id'), $t->text('alasan'), $t->unsignedBigInteger('user_id')->nullable(), $t->timestamps()]);

        DB::table('users')->insert([['name' => 'Bastian'], ['name' => 'Budi Dion']]);
        DB::table('order_outdoor')->insert([
            ['NoOrder' => 'OUT.1.001', 'status' => 'cetak', 'cetak_by' => null],
            ['NoOrder' => 'OUT.1.002', 'status' => 'cetak', 'cetak_by' => null],
            ['NoOrder' => 'OUT.1.003', 'status' => 'finishing', 'cetak_by' => 2],
        ]);
        foreach ([1, 2, 3] as $id) {
            DB::table('order_late_logs')->insert(['order_type' => 'outdoor', 'order_id' => $id, 'late_at' => now(), 'stage' => 'cetak']);
        }
        DB::table('order_status_notes')->insert(['order_type' => 'outdoor', 'order_id' => 1, 'qty' => 2, 'stage' => 'cetak', 'action' => 'progress', 'user_id' => 1, 'created_at' => now()]);
    }

    public function test_tahap_belum_selesai_menampilkan_operator_yang_sedang_mengerjakan(): void
    {
        $data = app(HistoryTelatController::class)->index(Request::create('/history-telat'), app(OrderLateLogService::class))->getData();

        // Order 1: cetak belum selesai, Bastian sudah kirim sebagian → tampil "(sedang)".
        $this->assertSame('Bastian', $data['workingOperators']->get('outdoor-1-cetak'));
        // Order 2: belum ada yang mengerjakan → "Belum dikerjakan".
        $this->assertNull($data['workingOperators']->get('outdoor-2-cetak'));
        // Order 3: tahap cetak sudah selesai → operator penyelesai seperti sebelumnya.
        $this->assertSame('Budi Dion', $data['operators'][2]);
        $this->assertFalse($data['workingOperators']->has('outdoor-3-cetak'));
    }
}
