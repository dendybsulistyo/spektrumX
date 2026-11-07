<?php

namespace Tests\Feature;

use App\Models\OrderLateReason;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderLateReasonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->text('permissions')->nullable(), $t->timestamps()]);
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('password')->nullable();
            $t->unsignedBigInteger('role_id')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->timestamps();
        });
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, fn (Blueprint $t) => [$t->id(), $t->string('NoOrder'), $t->timestamps()]);
        }
        Schema::create('order_late_reasons', function (Blueprint $t) {
            $t->id();
            $t->string('order_type', 20);
            $t->unsignedBigInteger('order_id');
            $t->text('alasan');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
        });

        DB::table('roles')->insert([
            ['name' => 'operator cetak', 'permissions' => json_encode(['history-telat.alasan'])],
            ['name' => 'customer service', 'permissions' => json_encode([])],
        ]);
        DB::table('users')->insert([
            ['name' => 'Bastian', 'email' => 'bastian@example.test', 'role_id' => 1, 'email_verified_at' => now()],
            ['name' => 'Rani', 'email' => 'rani@example.test', 'role_id' => 1, 'email_verified_at' => now()],
            ['name' => 'Tamu', 'email' => 'tamu@example.test', 'role_id' => 2, 'email_verified_at' => now()],
        ]);
        DB::table('order_outdoor')->insert(['NoOrder' => 'OUT.1.261107001']);
    }

    public function test_operators_with_permission_can_each_add_reasons(): void
    {
        $this->actingAs(User::find(1))
            ->postJson(route('dashboard.order-late-reasons.store', ['type' => 'outdoor', 'id' => 1]), ['alasan' => 'Bahan habis'])
            ->assertOk();

        $this->actingAs(User::find(2))
            ->postJson(route('dashboard.order-late-reasons.store', ['type' => 'outdoor', 'id' => 1]), ['alasan' => '  Printer error  '])
            ->assertOk()
            ->assertJsonCount(2, 'late_reasons')
            ->assertJsonPath('late_reasons.0.alasan', 'Printer error')
            ->assertJsonPath('late_reasons.0.user', 'Rani')
            ->assertJsonPath('late_reasons.1.user', 'Bastian');

        $this->assertSame(2, OrderLateReason::forOrder('outdoor', 1)->count());
    }

    public function test_user_without_permission_cannot_add_reason(): void
    {
        $this->actingAs(User::find(3))
            ->postJson(route('dashboard.order-late-reasons.store', ['type' => 'outdoor', 'id' => 1]), ['alasan' => 'Coba'])
            ->assertForbidden();

        $this->assertSame(0, OrderLateReason::count());
    }

    public function test_reason_is_required_and_order_must_exist(): void
    {
        $this->actingAs(User::find(1))
            ->postJson(route('dashboard.order-late-reasons.store', ['type' => 'outdoor', 'id' => 1]), ['alasan' => ''])
            ->assertJsonValidationErrors('alasan');

        $this->actingAs(User::find(1))
            ->postJson(route('dashboard.order-late-reasons.store', ['type' => 'outdoor', 'id' => 99]), ['alasan' => 'X'])
            ->assertNotFound();

        $this->actingAs(User::find(1))
            ->postJson(route('dashboard.order-late-reasons.store', ['type' => 'lain', 'id' => 1]), ['alasan' => 'X'])
            ->assertNotFound();
    }
}
