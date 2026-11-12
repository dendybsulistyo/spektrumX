<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SalesOrderPrint;
use App\Models\User;
use App\Services\ReprintGate;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CetakUlangSatuPintuTest extends TestCase
{
    private function user(string $role): User
    {
        $user = new User(['name' => 'Fergi']);
        $user->setRelation('role', new Role(['name' => $role]));

        return $user;
    }

    private function printed(): SalesOrderPrint
    {
        return new SalesOrderPrint(['print_count' => 1, 'first_printed_at' => Carbon::now()]);
    }

    public function test_kasir_tidak_bisa_cetak_ulang_walau_baru_saja_dicetak(): void
    {
        $gate = app(ReprintGate::class);
        $kasir = $this->user('kasir');

        $this->assertSame('first', $gate->state(null, 'so', 'indoor', 1, null, $kasir));
        $this->assertSame('locked', $gate->state($this->printed(), 'so', 'indoor', 1, null, $kasir));

        $this->actingAs($kasir);
        $result = $gate->authorizeReprint($this->printed(), 'so', 'indoor', 1, null, 'IND.2.261112001', 'Printer macet');
        $this->assertFalse($result['allowed']);
        $this->assertSame('locked', $result['code']);
    }

    public function test_admin_kasir_dan_akuntansi_tetap_bisa_cetak_ulang(): void
    {
        $gate = app(ReprintGate::class);

        $this->assertSame('privileged', $gate->state($this->printed(), 'inv', 'indoor', 1, 5, $this->user('admin-kasir')));
        $this->assertSame('privileged', $gate->state($this->printed(), 'inv', 'indoor', 1, 5, $this->user('akuntansi')));
    }
}
