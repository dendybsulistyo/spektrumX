<?php

namespace Tests\Feature;

use App\Http\Middleware\KembaliKeHalamanIndex;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KembaliKeHalamanIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware([StartSession::class, KembaliKeHalamanIndex::class])->group(function () {
            Route::get('/uji-master', fn () => 'daftar')->name('uji-master.index');
            Route::put('/uji-master/{id}', fn () => redirect()->route('uji-master.index')->with('success', 'Tersimpan'));
            Route::delete('/uji-master/{id}', fn () => redirect()->route('uji-master.index'));
            Route::post('/uji-master/lain', fn () => redirect()->route('uji-master.index', ['tab' => 'x']));
        });

        Route::getRoutes()->refreshNameLookups();
    }

    public function test_update_kembali_ke_halaman_dan_filter_terakhir(): void
    {
        $this->get('/uji-master?page=3&q=flute&kategori=13')->assertOk();

        $this->put('/uji-master/1')
            ->assertRedirect(url('/uji-master?kategori=13&page=3&q=flute'))
            ->assertSessionHas('success', 'Tersimpan');

        $this->delete('/uji-master/1')->assertRedirect(url('/uji-master?kategori=13&page=3&q=flute'));
    }

    public function test_tanpa_riwayat_tetap_ke_halaman_pertama(): void
    {
        $this->put('/uji-master/1')->assertRedirect(url('/uji-master'));
    }

    public function test_membuka_halaman_pertama_menghapus_ingatan(): void
    {
        $this->get('/uji-master?page=3')->assertOk();
        $this->get('/uji-master')->assertOk();

        $this->put('/uji-master/1')->assertRedirect(url('/uji-master'));
    }

    public function test_redirect_yang_sudah_punya_query_tidak_diubah(): void
    {
        $this->get('/uji-master?page=3')->assertOk();

        $this->post('/uji-master/lain')->assertRedirect(url('/uji-master?tab=x'));
    }

    public function test_tombol_batal_memakai_url_yang_diingat(): void
    {
        $this->get('/uji-master?page=2&q=mdf')->assertOk();

        $this->assertSame(url('/uji-master?page=2&q=mdf'), KembaliKeHalamanIndex::url('uji-master.index'));
    }
}
