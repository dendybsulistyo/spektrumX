<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekapPenerimaanTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_open_rekap_penerimaan(): void
    {
        $role = Role::create([
            'name' => 'analitik',
            'label' => 'Analitik',
            'permissions' => ['data-warehouse.view'],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get(route('rekap-penerimaan.index', ['dari' => '2026-09-30', 'sampai' => '2026-09-01']))
            ->assertOk()
            ->assertSee('Rekap Penerimaan SPEKTRUM')
            ->assertSee('01 September 2026')
            ->assertSee('30 September 2026')
            ->assertSee('PRINT DOKUMEN')
            ->assertSee('JUMLAH');
    }

    public function test_user_without_permission_cannot_open_rekap_penerimaan(): void
    {
        $role = Role::create([
            'name' => 'tanpa_analitik',
            'label' => 'Tanpa Analitik',
            'permissions' => [],
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('rekap-penerimaan.index'))->assertForbidden();
    }
}
