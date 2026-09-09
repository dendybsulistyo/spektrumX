<?php

namespace Tests\Feature;

use App\Http\Controllers\OrderIndoorController;
use App\Http\Controllers\OrderOutdoorController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderNumberFormatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['order_indoor', 'order_outdoor'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->string('NoOrder')->unique();
            });
            Schema::create($table.'_detail', function (Blueprint $table) {
                $table->id();
                $table->string('BrsOrder')->unique();
            });
        }
        Schema::create('laporan_ppn_final_items', function (Blueprint $table) {
            $table->id();
            $table->string('no_order');
        });
    }

    public function test_conversion_preserves_sequences_details_and_report_references(): void
    {
        foreach (['indoor' => ['IND', 'IND.2.', OrderIndoorController::class], 'outdoor' => ['OUT', 'OUT.1.', OrderOutdoorController::class]] as $kind => [$old, $new, $controller]) {
            DB::table('order_'.$kind)->insert(['NoOrder' => $old.'26082800009']);
            DB::table('order_'.$kind)->insert(['NoOrder' => 'legacy.123']);
            DB::table('order_'.$kind.'_detail')->insert(['BrsOrder' => $old.'2608280000901']);
            DB::table('laporan_ppn_final_items')->insert(['no_order' => $old.'26082800009']);
        }
        $migration = require database_path('migrations/2026_09_08_000001_add_division_to_order_numbers.php');
        $migration->up();
        $migration->up();
        foreach (['indoor' => ['IND', 'IND.2.', OrderIndoorController::class], 'outdoor' => ['OUT', 'OUT.1.', OrderOutdoorController::class]] as $kind => [$old, $new, $controller]) {
            $this->assertDatabaseHas('order_'.$kind, ['NoOrder' => $new.'26082800009']);
            $this->assertDatabaseHas('order_'.$kind, ['NoOrder' => 'legacy.123']);
            $this->assertDatabaseHas('order_'.$kind.'_detail', ['BrsOrder' => $new.'2608280000901']);
            $this->assertDatabaseHas('laporan_ppn_final_items', ['no_order' => $new.'26082800009']);
            $generate = new \ReflectionMethod($controller, 'generateNoOrder');
            $this->assertSame($new.'26082800010', $generate->invoke(app($controller), '2026-08-28'));
            $this->assertSame($new.'26082900001', $generate->invoke(app($controller), '2026-08-29'));
        }
        $migration->down();
        $this->assertDatabaseHas('order_indoor', ['NoOrder' => 'IND26082800009']);
        $this->assertDatabaseHas('order_outdoor_detail', ['BrsOrder' => 'OUT2608280000901']);
        $this->assertDatabaseHas('laporan_ppn_final_items', ['no_order' => 'IND26082800009']);
    }

    public function test_number_collision_rolls_back_all_changes(): void
    {
        DB::table('order_outdoor')->insert(['NoOrder' => 'OUT26082800001']);
        DB::table('order_indoor')->insert([
            ['NoOrder' => 'IND26082800001'],
            ['NoOrder' => 'IND.2.26082800001'],
        ]);
        $migration = require database_path('migrations/2026_09_08_000001_add_division_to_order_numbers.php');
        try {
            $migration->up();
            $this->fail('Expected a duplicate number to abort conversion.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Nomor nota sudah ada', $e->getMessage());
        }
        $this->assertDatabaseHas('order_outdoor', ['NoOrder' => 'OUT26082800001']);
        $this->assertDatabaseMissing('order_outdoor', ['NoOrder' => 'OUT.1.26082800001']);
    }
}
