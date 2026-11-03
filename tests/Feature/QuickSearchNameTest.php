<?php

namespace Tests\Feature;

use App\Http\Controllers\QuickSearchController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuickSearchNameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('KdCust');
            $t->string('NmCust');
            $t->string('Telp')->nullable();
        });
        Schema::create('customer_limits', function (Blueprint $t) {
            $t->id();
            $t->string('KdCust');
            $t->decimal('Batas', 15, 2)->default(0);
            $t->decimal('Total', 15, 2)->default(0);
        });
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('NoOrder');
                $t->string('KdCust');
                $t->date('TglOrder')->nullable();
                $t->string('status');
                $t->string('status_bayar');
                $t->decimal('jumlah_piutang', 15, 2)->default(0);
                $t->timestamp('invoice_voided_at')->nullable();
                $t->timestamp('created_at')->nullable();
                $t->timestamp('diproses_kasir_at')->nullable();
            });
        }
        Schema::create('order_documents', function (Blueprint $t) {
            $t->id();
            $t->string('kind');
            $t->string('order_type');
            $t->unsignedBigInteger('order_id');
            $t->string('number')->nullable();
            $t->unsignedInteger('sequence')->default(1);
        });
        Schema::create('customer_service_job_sheets', function (Blueprint $t) {
            $t->id();
            $t->string('order_type');
            $t->unsignedBigInteger('order_id')->nullable();
            $t->unsignedInteger('progress_hari')->nullable();
        });
    }

    public function test_name_search_returns_customer_and_their_orders(): void
    {
        $this->seedAngga();

        $data = $this->search('Angga');

        $this->assertSame('Angga Kalu', $data['customers'][0]['name']);
        $this->assertSame('IND.2.261103059', $data['orders'][0]['no_order']);
    }

    public function test_order_number_search_returns_the_order(): void
    {
        $this->seedAngga();

        $data = $this->search('IND.2.2611030');

        $this->assertSame('IND.2.261103059', $data['orders'][0]['no_order']);
        $this->assertNull($data['orders'][0]['late']);
    }

    private function seedAngga(): void
    {
        DB::table('customers')->insert(['KdCust' => 'ANG001', 'NmCust' => 'Angga Kalu']);
        DB::table('order_indoor')->insert(['NoOrder' => 'IND.2.261103059', 'KdCust' => 'ANG001', 'TglOrder' => '2026-11-03', 'status' => 'desain', 'status_bayar' => 'lunas', 'created_at' => now(), 'diproses_kasir_at' => now()]);
    }

    /** @return array<string, mixed> */
    private function search(string $q): array
    {
        $user = new class extends User
        {
            public function hasPermission(string $key): bool
            {
                return true;
            }
        };
        $request = Request::create('/cari', 'GET', ['q' => $q]);
        $request->setUserResolver(fn () => $user);

        return app(QuickSearchController::class)($request)->getData(true);
    }
}
