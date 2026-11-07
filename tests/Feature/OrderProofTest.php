<?php

namespace Tests\Feature;

use App\Models\OrderOutdoor;
use App\Models\OrderProof;
use App\Models\User;
use App\Services\OrderProofService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OrderProofTest extends TestCase
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
        Schema::create('order_outdoor', fn (Blueprint $t) => [$t->id(), $t->string('NoOrder'), $t->timestamps()]);
        Schema::create('order_number_sequences', function (Blueprint $t) {
            $t->id();
            $t->string('order_type', 10);
            $t->date('order_date');
            $t->unsignedInteger('last_sequence')->default(0);
            $t->timestamps();
            $t->unique(['order_type', 'order_date']);
        });
        foreach (['printers_outdoors' => ['KdPrn', 'NmPrn'], 'bahan_cetak_outdoor' => ['NoCetak', 'NmBhn'], 'produk_indoor' => ['KdProd', 'NmProd'], 'harga_artwork' => ['KdProd', 'NmProd']] as $table => [$code, $name]) {
            Schema::create($table, fn (Blueprint $t) => [$t->id(), $t->string($code), $t->string($name), $t->integer('NoUrut')->default(0)]);
        }
        Schema::create('harga_cetak_outdoor', fn (Blueprint $t) => [$t->id(), $t->string('KdCtk')]);
        (require base_path('database/migrations/2026_11_07_144903_create_order_proofs_table.php'))->up();

        DB::table('roles')->insert([
            ['name' => 'customer-service', 'permissions' => json_encode(['order-proof.manage'])],
            ['name' => 'operator-desain', 'permissions' => json_encode(['order-desain.view', 'order-desain.manage'])],
        ]);
        DB::table('users')->insert([
            ['name' => 'Cs', 'email' => 'cs@example.test', 'role_id' => 1, 'email_verified_at' => now()],
            ['name' => 'Layouter', 'email' => 'layout@example.test', 'role_id' => 2, 'email_verified_at' => now()],
        ]);
        Schema::create('customer_limits', fn (Blueprint $t) => [$t->id(), $t->string('KdCust'), $t->decimal('Batas', 15, 2)->default(0), $t->decimal('Total', 15, 2)->default(0), $t->timestamps()]);
        DB::table('customers')->insert([['KdCust' => 'C1', 'NmCust' => 'Kiki'], ['KdCust' => 'C2', 'NmCust' => 'Umum']]);
        DB::table('customer_limits')->insert(['KdCust' => 'C1', 'Batas' => 1000000]);
    }

    private function storeProof(string $type = 'outdoor'): OrderProof
    {
        $this->actingAs(User::find(1))->post(route('order-proof.store'), [
            'order_type' => $type,
            'KdCust' => 'C1',
            'items' => [['NmFile' => 'Banner Kiki', 'kode' => '0101', 'Panjang' => '', 'Lebar' => '', 'Qty' => 2, 'catatan' => 'proof A4']],
        ])->assertRedirect();

        return OrderProof::latest('id')->firstOrFail();
    }

    public function test_proof_gets_own_number_and_goes_to_layout(): void
    {
        $outdoor = $this->storeProof('outdoor');
        $indoor = $this->storeProof('indoor');

        $this->assertSame('PRF.1.'.now()->format('ymd').'001', $outdoor->NoOrder);
        $this->assertSame('PRF.2.'.now()->format('ymd').'001', $indoor->NoOrder);
        $this->assertSame('layout', $outdoor->status);
        $this->assertNull($outdoor->items->first()->Panjang);
        $this->assertSame('indoor', $indoor->items->first()->jenis_produk);
        $this->assertSame(0, DB::table('order_outdoor')->count());
    }

    public function test_only_layout_operator_marks_done_then_proof_is_ready(): void
    {
        $proof = $this->storeProof();

        $this->actingAs(User::find(1))->post(route('order-proof.layout-done', $proof))->assertForbidden();
        $this->actingAs(User::find(2))->post(route('order-proof.layout-done', $proof))->assertRedirect(route('order-desain.index', ['tab' => 'proof']));

        $this->assertSame(['selesai', 2], [$proof->fresh()->status, $proof->fresh()->layout_done_by]);
        $this->actingAs(User::find(1))->getJson(route('order-proof.ready'))
            ->assertJsonCount(1, 'proofs')
            ->assertJsonPath('proofs.0.no_order', $proof->NoOrder)
            ->assertJsonPath('proofs.0.items.0.ukuran', 'Belum pasti');
    }

    public function test_proof_converts_to_one_order_only(): void
    {
        $proof = $this->storeProof();
        $proof->update(['status' => 'selesai']);
        $orderId = DB::table('order_outdoor')->insertGetId(['NoOrder' => 'OUT.1.261107001']);
        $order = OrderOutdoor::find($orderId);

        $this->actingAs(User::find(1));
        app(OrderProofService::class)->markConverted($proof->id, 'outdoor', $order);

        $this->assertSame(['jadi_order', 'outdoor', $orderId], [$proof->fresh()->status, $proof->fresh()->converted_order_type, $proof->fresh()->converted_order_id]);
        $this->assertSame($proof->id, OrderProof::fromOrder('outdoor', $orderId)?->id);
        $this->getJson(route('order-proof.ready'))->assertJsonCount(0, 'proofs');

        $this->expectException(HttpException::class);
        app(OrderProofService::class)->markConverted($proof->id, 'outdoor', $order);
    }

    public function test_closed_proof_leaves_the_ready_list(): void
    {
        $proof = $this->storeProof();
        $proof->update(['status' => 'selesai']);

        $this->actingAs(User::find(1))
            ->postJson(route('order-proof.close', $proof), ['alasan' => 'Customer tidak jadi'])
            ->assertOk();

        $this->assertSame(['ditutup', 'Customer tidak jadi'], [$proof->fresh()->status, $proof->fresh()->close_reason]);
        $this->getJson(route('order-proof.ready'))->assertJsonCount(0, 'proofs');
    }

    public function test_proof_is_only_for_vip_customers(): void
    {
        $this->actingAs(User::find(1))->post(route('order-proof.store'), [
            'order_type' => 'outdoor',
            'KdCust' => 'C2',
            'items' => [['NmFile' => 'Banner', 'Qty' => 1]],
        ])->assertSessionHasErrors('KdCust');

        $this->assertSame(0, OrderProof::count());
        // Pemegang hak proof (tanpa hak order/customer) tetap bisa mencari customer.
        $this->getJson('/customers-search?vip=1&q=')->assertOk()->assertJsonCount(1)->assertJsonPath('0.KdCust', 'C1');
        $this->getJson('/customers-search?q=')->assertOk()->assertJsonCount(2);
    }
}
