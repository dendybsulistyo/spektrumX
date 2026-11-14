<?php

namespace Tests\Feature;

use App\Models\CsTransferMasuk;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CsTransferMasukTest extends TestCase
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
        Schema::create('customers', fn (Blueprint $t) => [$t->id(), $t->string('KdCust'), $t->string('NmCust'), $t->timestamps()]);
        (require base_path('database/migrations/2026_11_14_142631_create_cs_transfer_masuk_table.php'))->up();

        DB::table('roles')->insert([
            ['name' => 'customer-service', 'permissions' => json_encode(['customer-service.view', 'customer-service.manage'])],
            ['name' => 'kasir', 'permissions' => json_encode(['customer-service.view'])],
        ]);
        User::forceCreate(['name' => 'Ana', 'email' => 'cs@example.test', 'role_id' => 1]);
        User::forceCreate(['name' => 'Fergi', 'email' => 'kasir@example.test', 'role_id' => 2]);
        DB::table('customers')->insert(['KdCust' => 'RJT001', 'NmCust' => 'RAMA JATU']);
    }

    public function test_cs_mencatat_transfer_masuk_lalu_menandai_sudah_dipakai(): void
    {
        $this->actingAs(User::find(1))->post(route('customer-service.transfer-masuk.store'), [
            'customer_code' => 'RJT001',
            'amount' => '1.500.000',
            'transfer_date' => now()->toDateString(),
            'notes' => 'via BCA',
        ])->assertRedirect(route('customer-service.payment-queue', ['tab' => 'transfer']));

        $transfer = CsTransferMasuk::sole();
        $this->assertSame(['RAMA JATU', '1500000.00', 1], [$transfer->customer_name, $transfer->amount, (int) $transfer->created_by]);
        $this->assertSame(1, CsTransferMasuk::belumDipakai()->count());

        $this->actingAs(User::find(1))->post(route('customer-service.transfer-masuk.use', $transfer), ['used_order_number' => 'OUT.1.261114001'])
            ->assertRedirect(route('customer-service.payment-queue', ['tab' => 'transfer']));

        $this->assertSame('OUT.1.261114001', $transfer->fresh()->used_order_number);
        $this->assertSame(0, CsTransferMasuk::belumDipakai()->count());
    }

    public function test_input_ditolak_tanpa_customer_valid_atau_tanpa_hak_kelola(): void
    {
        $this->actingAs(User::find(1))->post(route('customer-service.transfer-masuk.store'), [
            'customer_code' => '', 'amount' => '50.000', 'transfer_date' => now()->toDateString(),
        ])->assertSessionHasErrors('customer_code');

        $this->actingAs(User::find(2))->post(route('customer-service.transfer-masuk.store'), [
            'customer_code' => 'RJT001', 'amount' => '50.000', 'transfer_date' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertSame(0, CsTransferMasuk::count());
    }
}
