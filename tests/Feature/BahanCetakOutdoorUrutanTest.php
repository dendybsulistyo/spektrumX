<?php

namespace Tests\Feature;

use App\Http\Controllers\BahanCetakOutdoorController;
use App\Http\Requests\StoreBahanCetakOutdoorRequest;
use App\Models\BahanCetakOutdoor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BahanCetakOutdoorUrutanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('bahan_cetak_outdoor', function (Blueprint $t) {
            $t->integer('NoUrut')->primary();
            $t->string('NmBhn');
            $t->string('NoCetak')->nullable();
            $t->boolean('lewati_layout_cetak')->default(false);
        });
        DB::table('bahan_cetak_outdoor')->insert([
            ['NoUrut' => 1, 'NmBhn' => 'FT China', 'NoCetak' => '11'],
            ['NoUrut' => 2, 'NmBhn' => 'Sandblast V170 SM', 'NoCetak' => '29'],
        ]);
    }

    private function update(BahanCetakOutdoor $bahan, array $input): void
    {
        $request = StoreBahanCetakOutdoorRequest::create('/', 'PUT', $input);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->setRouteResolver(fn () => tap(new Route('PUT', '/{bahan_cetak_outdoor}', []), fn ($route) => $route->bind($request)->setParameter('bahan_cetak_outdoor', $bahan)));
        $request->validateResolved();
        app(BahanCetakOutdoorController::class)->update($request, $bahan);
    }

    public function test_nomor_urut_bisa_diubah_tanpa_mengubah_nomor_cetak(): void
    {
        $this->update(BahanCetakOutdoor::find(2), ['NoUrut' => 10, 'NmBhn' => 'Sandblast V170 SM', 'NoCetak' => '29', 'lewati_layout_cetak' => 1]);

        $this->assertSame(['FT China', 'Sandblast V170 SM'], BahanCetakOutdoor::orderBy('NoUrut')->pluck('NmBhn')->all());
        $this->assertSame('29', BahanCetakOutdoor::find(10)->NoCetak);
        $this->assertTrue(BahanCetakOutdoor::find(10)->lewati_layout_cetak);
    }

    public function test_nomor_urut_tidak_boleh_kembar(): void
    {
        $this->expectException(ValidationException::class);
        $this->update(BahanCetakOutdoor::find(2), ['NoUrut' => 1, 'NmBhn' => 'Sandblast V170 SM', 'NoCetak' => '29']);
    }
}
