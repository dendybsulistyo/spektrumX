<?php

namespace Tests\Feature;

use App\Models\OrderLateLog;
use App\Services\OrderLateLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderLateLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->string('status');
                $table->string('status_bayar')->default('lunas');
                $table->timestamp('created_at')->nullable();
                foreach (['dibayar_at', 'diproses_kasir_at', 'desain_at', 'cetak_at', 'finishing_at', 'qc_at', 'bungkus_at'] as $column) {
                    $table->timestamp($column)->nullable();
                }
            });
        }
        Schema::create('order_status_notes', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->string('stage');
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('customer_service_job_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('progress_hari')->nullable();
        });
        (require database_path('migrations/2026_11_03_095523_create_order_late_logs_table.php'))->up();
        Carbon::setTestNow('2026-11-03 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_records_only_orders_past_24_hours_that_are_not_ready(): void
    {
        $late = DB::table('order_indoor')->insertGetId(['status' => 'cetak', 'created_at' => '2026-11-02 07:00:00', 'dibayar_at' => '2026-11-02 08:00:00']);
        DB::table('order_indoor')->insert(['status' => 'cetak', 'created_at' => '2026-11-03 08:00:00', 'dibayar_at' => '2026-11-03 08:00:00']);
        DB::table('order_outdoor')->insert(['status' => 'siap_diambil', 'created_at' => '2026-11-01 08:00:00', 'dibayar_at' => '2026-11-01 08:00:00']);

        $result = app(OrderLateLogService::class)->record();
        app(OrderLateLogService::class)->record();

        $this->assertSame(1, $result['opened']);
        $this->assertDatabaseCount('order_late_logs', 1);
        $log = OrderLateLog::first();
        $this->assertSame(['indoor', $late, 'cetak', false], [$log->order_type, $log->order_id, $log->stage, $log->is_estimated]);
        $this->assertSame('2026-11-03 08:00:00', $log->late_at->format('Y-m-d H:i:s'));
        $this->assertNull($log->resolved_at);
    }

    public function test_unpaid_orders_are_never_late_and_old_wrong_logs_are_removed(): void
    {
        $unpaid = DB::table('order_outdoor')->insertGetId(['status' => 'baru', 'status_bayar' => 'belum_bayar', 'created_at' => '2026-11-01 08:00:00']);
        DB::table('order_late_logs')->insert(['order_type' => 'outdoor', 'order_id' => $unpaid, 'late_at' => '2026-11-02 08:00:00', 'is_estimated' => false]);

        app(OrderLateLogService::class)->record();

        $this->assertDatabaseCount('order_late_logs', 0);
    }

    public function test_clock_starts_at_first_cashier_note_not_at_later_settlement(): void
    {
        $id = DB::table('order_indoor')->insertGetId(['status' => 'finishing', 'status_bayar' => 'lunas', 'created_at' => '2026-11-01 08:00:00', 'dibayar_at' => '2026-11-03 11:00:00']);
        DB::table('order_status_notes')->insert(['order_type' => 'indoor', 'order_id' => $id, 'stage' => 'kasir', 'created_at' => '2026-11-02 09:00:00']);

        app(OrderLateLogService::class)->record();

        $this->assertSame('2026-11-03 09:00:00', OrderLateLog::sole()->late_at->format('Y-m-d H:i:s'));
    }

    public function test_deadline_follows_cashier_time_plus_cs_progress_days(): void
    {
        $twoDays = DB::table('order_outdoor')->insertGetId(['status' => 'cetak', 'created_at' => '2026-11-01 08:00:00', 'diproses_kasir_at' => '2026-11-01 09:00:00']);
        DB::table('order_outdoor')->insert(['status' => 'cetak', 'created_at' => '2026-11-02 08:00:00', 'diproses_kasir_at' => '2026-11-02 09:00:00']);
        DB::table('customer_service_job_sheets')->insert(['order_type' => 'outdoor', 'order_id' => $twoDays, 'progress_hari' => 2]);

        app(OrderLateLogService::class)->record();

        // 2 hari dari 1 Nov 09:00 = 3 Nov 09:00 (telat); 1 hari dari 2 Nov 09:00 = 3 Nov 09:00 (telat).
        $this->assertSame(['2026-11-03 09:00:00', '2026-11-03 09:00:00'], OrderLateLog::orderBy('order_id')->get()->map(fn ($log) => $log->late_at->format('Y-m-d H:i:s'))->all());
    }

    public function test_cs_progress_days_keep_an_order_on_time(): void
    {
        $id = DB::table('order_outdoor')->insertGetId(['status' => 'cetak', 'created_at' => '2026-11-02 08:00:00', 'diproses_kasir_at' => '2026-11-02 09:00:00']);
        DB::table('customer_service_job_sheets')->insert(['order_type' => 'outdoor', 'order_id' => $id, 'progress_hari' => 3]);

        app(OrderLateLogService::class)->record();

        $this->assertDatabaseCount('order_late_logs', 0);
    }

    public function test_resolves_log_once_order_is_ready_using_bungkus_time(): void
    {
        $id = DB::table('order_indoor')->insertGetId(['status' => 'cetak', 'created_at' => '2026-11-02 08:00:00', 'dibayar_at' => '2026-11-02 08:00:00']);
        app(OrderLateLogService::class)->record();

        DB::table('order_indoor')->where('id', $id)->update(['status' => 'siap_diambil', 'bungkus_at' => '2026-11-03 10:30:00']);
        $result = app(OrderLateLogService::class)->record();

        $log = OrderLateLog::first();
        $this->assertSame(1, $result['resolved']);
        $this->assertSame('2026-11-03 10:30:00', $log->resolved_at->format('Y-m-d H:i:s'));
        $this->assertSame('siap_diambil', $log->resolved_status);
        $this->assertSame('2 jam', $log->lateDurationLabel());
    }

    public function test_backfill_marks_old_orders_whose_packing_finished_after_24_hours(): void
    {
        $late = DB::table('order_artwork')->insertGetId([
            'status' => 'selesai', 'created_at' => '2026-10-01 08:00:00', 'dibayar_at' => '2026-10-01 09:00:00',
            'desain_at' => '2026-10-01 15:00:00', 'cetak_at' => '2026-10-02 14:00:00', 'bungkus_at' => '2026-10-03 09:00:00',
        ]);
        DB::table('order_artwork')->insert(['status' => 'selesai', 'created_at' => '2026-10-01 08:00:00', 'dibayar_at' => '2026-10-01 08:00:00', 'bungkus_at' => '2026-10-01 20:00:00']);
        DB::table('order_artwork')->insert(['status' => 'selesai', 'created_at' => '2026-10-01 08:00:00', 'dibayar_at' => '2026-10-01 08:00:00', 'bungkus_at' => null]);

        $this->assertSame(1, app(OrderLateLogService::class)->backfill());
        $this->assertSame(0, app(OrderLateLogService::class)->backfill());

        $log = OrderLateLog::first();
        $this->assertSame(['artwork', $late, 'cetak', true], [$log->order_type, $log->order_id, $log->stage, $log->is_estimated]);
        $this->assertSame('2026-10-03 09:00:00', $log->resolved_at->format('Y-m-d H:i:s'));
    }

    public function test_backfill_skips_stages_an_item_never_went_through(): void
    {
        DB::table('order_indoor')->insert([
            'status' => 'selesai', 'created_at' => '2026-10-01 08:00:00', 'dibayar_at' => '2026-10-01 08:00:00',
            'finishing_at' => '2026-10-02 10:00:00', 'qc_at' => '2026-10-02 11:00:00', 'bungkus_at' => '2026-10-02 12:00:00',
        ]);

        app(OrderLateLogService::class)->backfill();

        $this->assertSame('finishing', OrderLateLog::first()->stage);
    }

    public function test_commands_run_without_errors(): void
    {
        DB::table('order_outdoor')->insert(['status' => 'desain', 'created_at' => '2026-11-01 08:00:00', 'dibayar_at' => '2026-11-01 08:00:00']);

        $this->artisan('telat:catat')->assertSuccessful();
        $this->artisan('telat:backfill')->assertSuccessful();
        $this->assertDatabaseCount('order_late_logs', 1);
    }

    public function test_catat_command_is_safe_before_migration(): void
    {
        Schema::drop('order_late_logs');

        $this->artisan('telat:catat')->assertSuccessful();
    }
}
