<?php

namespace Tests\Feature;

use App\Models\OrderOutdoorDetail;
use App\Models\User;
use App\Services\OrderCorrectionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderCorrectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', fn (Blueprint $table) => [$table->id(), $table->string('name'), $table->string('email'), $table->string('password')->nullable(), $table->timestamps()]);
        Schema::create('order_outdoor', function (Blueprint $table) {
            $table->id();
            $table->string('NoOrder');
            $table->string('status');
            $table->timestamp('cancel_requested_at')->nullable();
            foreach (['desain', 'cetak', 'finishing', 'qc', 'bungkus'] as $stage) {
                $table->unsignedBigInteger($stage.'_by')->nullable();
                $table->timestamp($stage.'_at')->nullable();
            }
            $table->timestamps();
        });
        Schema::create('order_outdoor_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_outdoor_id');
            $table->string('NmFile');
            $table->integer('Qty');
            foreach (['desain', 'cetak', 'finishing', 'qc', 'bungkus', 'siap_diambil', 'selesai'] as $stage) {
                $table->integer('qty_'.$stage)->default(0);
            }
            $table->timestamp('stage_entered_at')->nullable();
            $table->timestamps();
        });
        Schema::create('order_rework_requests', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->string('status');
        });
        Schema::create('order_status_notes', function (Blueprint $table) {
            $table->id();
            $table->string('order_type');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_detail_id')->nullable();
            $table->integer('qty')->nullable();
            $table->string('stage');
            $table->string('action');
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at');
        });

        $userId = DB::table('users')->insertGetId(['name' => 'Admin', 'email' => 'admin@example.test']);
        Auth::setUser(User::findOrFail($userId));
    }

    public function test_admin_can_move_stuck_item_directly_to_ready_for_pickup(): void
    {
        $orderId = DB::table('order_outdoor')->insertGetId(['NoOrder' => 'OUT.1.TEST', 'status' => 'desain']);
        $readyItemId = $this->insertItem($orderId, 'Banner selesai', ['qty_siap_diambil' => 1]);
        $stuckItemId = $this->insertItem($orderId, 'Sandblast V170 SM', ['qty_desain' => 2]);

        $result = app(OrderCorrectionService::class)->moveTo(
            OrderOutdoorDetail::findOrFail($stuckItemId),
            'siap_diambil',
            'Tidak perlu layout dan cetak',
            Auth::id(),
        );

        $stuck = DB::table('order_outdoor_detail')->find($stuckItemId);
        $this->assertSame([0, 2], [(int) $stuck->qty_desain, (int) $stuck->qty_siap_diambil]);
        $this->assertSame(1, (int) DB::table('order_outdoor_detail')->where('id', $readyItemId)->value('qty_siap_diambil'));
        $this->assertSame('siap_diambil', DB::table('order_outdoor')->where('id', $orderId)->value('status'));
        $this->assertSame(2, $result['moved']);
        $this->assertSame(
            ['desain', 'cetak', 'finishing', 'qc', 'bungkus'],
            DB::table('order_status_notes')->where('order_detail_id', $stuckItemId)->orderBy('id')->pluck('stage')->all(),
        );
        $this->assertTrue(DB::table('order_status_notes')->where('catatan', 'like', '%Tidak perlu layout dan cetak%')->exists());
    }

    public function test_correction_to_bungkus_preserves_quantities_already_ahead(): void
    {
        $orderId = DB::table('order_outdoor')->insertGetId(['NoOrder' => 'OUT.1.MIXED', 'status' => 'cetak']);
        $itemId = $this->insertItem($orderId, 'Stand banner', ['qty_cetak' => 2, 'qty_siap_diambil' => 1]);

        app(OrderCorrectionService::class)->moveTo(OrderOutdoorDetail::findOrFail($itemId), 'bungkus', 'Barang fisik sudah jadi', Auth::id());

        $item = DB::table('order_outdoor_detail')->find($itemId);
        $this->assertSame([0, 2, 1], [(int) $item->qty_cetak, (int) $item->qty_bungkus, (int) $item->qty_siap_diambil]);
        $this->assertSame(['cetak', 'finishing', 'qc'], DB::table('order_status_notes')->orderBy('id')->pluck('stage')->all());
    }

    public function test_item_cannot_be_moved_backwards(): void
    {
        $orderId = DB::table('order_outdoor')->insertGetId(['NoOrder' => 'OUT.1.READY', 'status' => 'siap_diambil']);
        $itemId = $this->insertItem($orderId, 'Sudah siap', ['qty_siap_diambil' => 1]);

        $this->expectException(ValidationException::class);
        app(OrderCorrectionService::class)->moveTo(OrderOutdoorDetail::findOrFail($itemId), 'bungkus', 'Tidak boleh mundur', Auth::id());
    }

    /** @param array<string, int> $quantities */
    private function insertItem(int $orderId, string $name, array $quantities): int
    {
        return DB::table('order_outdoor_detail')->insertGetId([
            'order_outdoor_id' => $orderId,
            'NmFile' => $name,
            'Qty' => array_sum($quantities),
            ...$quantities,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
