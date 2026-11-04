<?php

namespace Tests\Feature;

use App\Services\EmployeeOfMonthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeOfMonthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', fn (Blueprint $t) => [$t->id(), $t->string('name')]);
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                foreach (['desain_by', 'cetak_by', 'finishing_by', 'qc_by', 'bungkus_by'] as $column) {
                    $t->unsignedBigInteger($column)->nullable();
                }
            });
        }
        foreach (['order_indoor_detail', 'order_artwork_detail'] as $table) {
            Schema::create($table, fn (Blueprint $t) => [$t->id(), $t->string('KdProd'), $t->boolean('tanpa_proses')->default(false)]);
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
        Schema::create('order_payments', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->unsignedBigInteger('order_id');
            $t->string('jenis');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('customer_service_job_sheets', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('created_by')->nullable(), $t->timestamp('created_at')->nullable()]);
        Schema::create('order_rework_requests', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->unsignedBigInteger('order_id');
            $t->string('action');
            $t->string('status');
            $t->string('target_stage')->nullable();
            $t->timestamp('requested_at')->nullable();
        });
        (require database_path('migrations/2026_11_03_095523_create_order_late_logs_table.php'))->up();
        Carbon::setTestNow('2026-11-15 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cetak_winner_combines_productivity_timeliness_and_quality(): void
    {
        [$ani, $budi] = $this->users('Ani', 'Budi');
        // Ani: 10 order × 10 qty, 2 order telat di tahap cetak. Budi: 5 order × 10 qty, tepat waktu.
        for ($i = 1; $i <= 10; $i++) {
            $order = DB::table('order_outdoor')->insertGetId(['cetak_by' => $ani]);
            $this->note('outdoor', $order, 'cetak', 10, $ani, $i);
            if ($i <= 2) {
                DB::table('order_late_logs')->insert(['order_type' => 'outdoor', 'order_id' => $order, 'late_at' => "2026-10-0{$i} 12:00:00", 'stage' => 'cetak', 'is_estimated' => false]);
            }
        }
        for ($i = 1; $i <= 5; $i++) {
            $order = DB::table('order_outdoor')->insertGetId(['cetak_by' => $budi]);
            $this->note('outdoor', $order, 'cetak', 10, $budi, $i);
        }
        $this->activeOn($ani, 10);
        $this->activeOn($budi, 10);

        $cetak = $this->division('Cetak');

        $this->assertSame('Ani', $cetak['winner']['name']);
        $this->assertSame([94.0, 75.0], $cetak['rows']->pluck('score')->all());
        $this->assertSame(80.0, $cetak['rows'][0]['on_time']);
    }

    public function test_ctcp_auto_notes_and_non_achievement_products_are_ignored(): void
    {
        [$cici] = $this->users('Cici');
        $order = DB::table('order_indoor')->insertGetId([]);
        $normal = DB::table('order_indoor_detail')->insertGetId(['KdProd' => '1801']);
        $kresek = DB::table('order_indoor_detail')->insertGetId(['KdProd' => '2804']);
        $this->note('indoor', $order, 'finishing', 4, $cici, 1, $normal);
        $this->note('indoor', $order, 'finishing', 50, $cici, 1, $kresek);
        $this->note('indoor', $order, 'finishing', 70, $cici, 1, $normal, 'Otomatis selesai bersama proses Cetak CTcP.');
        $this->activeOn($cici, 10);

        $this->assertSame(4.0, $this->division('Finishing')['rows'][0]['output']);
    }

    public function test_person_below_minimum_active_days_cannot_win(): void
    {
        [$dodi, $eka] = $this->users('Dodi', 'Eka');
        $this->note('outdoor', DB::table('order_outdoor')->insertGetId(['bungkus_by' => $dodi]), 'bungkus', 100, $dodi, 1);
        $this->note('outdoor', DB::table('order_outdoor')->insertGetId(['bungkus_by' => $eka]), 'bungkus', 10, $eka, 1);
        $this->activeOn($dodi, 3);
        $this->activeOn($eka, 12);

        $bungkus = $this->division('Bungkus');

        $this->assertSame('Eka', $bungkus['winner']['name']);
        $this->assertFalse($bungkus['rows']->firstWhere('name', 'Dodi')['eligible']);
    }

    public function test_cashier_and_cs_count_paid_orders_and_job_sheets(): void
    {
        [$fani, $gita] = $this->users('Fani', 'Gita');
        foreach ([1, 1, 2, 3] as $orderId) {
            DB::table('order_payments')->insert(['order_type' => 'indoor', 'order_id' => $orderId, 'jenis' => 'lunas', 'user_id' => $fani, 'created_at' => '2026-10-05 10:00:00']);
        }
        DB::table('order_payments')->insert(['order_type' => 'indoor', 'order_id' => 9, 'jenis' => 'refund', 'user_id' => $fani, 'created_at' => '2026-10-05 10:00:00']);
        DB::table('customer_service_job_sheets')->insert(array_fill(0, 7, ['created_by' => $gita, 'created_at' => '2026-10-06 10:00:00']));
        $this->activeOn($fani, 10);
        $this->activeOn($gita, 10);

        $this->assertSame(3.0, $this->division('Kasir')['winner']['output']);
        $this->assertSame(7.0, $this->division('Customer Service')['winner']['output']);
    }

    /** @return list<int> */
    private function users(string ...$names): array
    {
        return array_map(fn (string $name) => DB::table('users')->insertGetId(['name' => $name]), $names);
    }

    private function note(string $type, int $orderId, string $stage, int $qty, int $userId, int $day, ?int $detailId = null, ?string $catatan = null): void
    {
        DB::table('order_status_notes')->insert([
            'order_type' => $type, 'order_id' => $orderId, 'order_detail_id' => $detailId, 'qty' => $qty, 'stage' => $stage,
            'action' => 'selesai', 'catatan' => $catatan, 'user_id' => $userId, 'created_at' => sprintf('2026-10-%02d 09:00:00', $day),
        ]);
    }

    /** Hari aktif lewat catatan non-produksi (tidak memengaruhi qty). */
    private function activeOn(int $userId, int $days): void
    {
        for ($d = 1; $d <= $days; $d++) {
            DB::table('order_status_notes')->insert(['order_type' => 'indoor', 'order_id' => 0, 'stage' => 'kasir', 'action' => 'catatan', 'user_id' => $userId, 'created_at' => sprintf('2026-10-%02d 08:00:00', $d)]);
        }
    }

    /** @return array<string, mixed> */
    private function division(string $label): array
    {
        $result = app(EmployeeOfMonthService::class)->compute(Carbon::parse('2026-10-01'));
        $this->assertFalse($result['provisional']);

        return collect($result['divisions'])->firstWhere('label', $label);
    }
}
