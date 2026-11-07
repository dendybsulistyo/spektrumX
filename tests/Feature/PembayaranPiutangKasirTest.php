<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PembayaranPiutangKasirTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->text('permissions')->nullable(), $t->timestamps()]);
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->unsignedBigInteger('role_id')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->timestamps();
        });
        DB::table('roles')->insert([
            ['name' => 'kasir', 'permissions' => json_encode(['kasir.view', 'kasir.manage'])],
            ['name' => 'operator-cetak', 'permissions' => json_encode(['order-cetak.view'])],
        ]);
        DB::table('users')->insert([
            ['name' => 'Kasir', 'email' => 'kasir@example.test', 'role_id' => 1, 'email_verified_at' => now()],
            ['name' => 'Cetak', 'email' => 'cetak@example.test', 'role_id' => 2, 'email_verified_at' => now()],
        ]);
    }

    public function test_kasir_boleh_membuka_pembayaran_piutang(): void
    {
        $this->assertContains('permission.any:keuangan.view,kasir.manage', Route::getRoutes()->getByName('keuangan.customer-receivable-details')->gatherMiddleware());
    }

    public function test_kasir_tidak_boleh_membuka_laporan_piutang_per_customer(): void
    {
        $this->actingAs(User::find(1))
            ->get(route('keuangan.customer-receivable-details', ['mode' => 'report']))
            ->assertForbidden();
    }

    public function test_user_tanpa_hak_kasir_atau_keuangan_ditolak(): void
    {
        $this->actingAs(User::find(2))
            ->get(route('keuangan.customer-receivable-details'))
            ->assertForbidden();
    }
}
