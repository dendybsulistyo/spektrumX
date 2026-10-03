<?php

namespace App\Http\Controllers;

use App\Models\BahanCetakOutdoor;
use App\Models\Customer;
use App\Models\HargaCetakOutdoor;
use App\Models\HargaCetakOutdoorKhusus;
use App\Models\PrinterOutdoor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HargaCetakOutdoorKhususController extends Controller
{
    /**
     * Mirrors HargaCetakOutdoorController's Printer × Bahan matrix, but
     * scoped to one VIP customer + one printer at a time (matches how the
     * shop's paper price list is actually organized per customer). Only
     * customers with a CustomerLimit row (i.e. is_vip) and at least one
     * special outdoor price are selectable.
     */
    
    public function index(Request $request): View
    {
        $vipCustomers = Customer::whereHas('limit')
            ->whereHas('hargaCetakOutdoorKhusus')
            ->orderBy('NmCust')
            ->get();
        $printers = PrinterOutdoor::orderBy('NoUrut')->get();
        $bahanList = BahanCetakOutdoor::orderBy('NoUrut')->get();

        $selectedKdCust = $request->query('KdCust') ?: null;
        $selectedKdPrn = $request->query('KdPrn') ?: null;

        $selectedCustomer = $selectedKdCust ? $vipCustomers->firstWhere('KdCust', $selectedKdCust) : null;
        $standardPrices = HargaCetakOutdoor::all()->keyBy('KdCtk');
        $khususPrices = $selectedKdCust
            ? HargaCetakOutdoorKhusus::where('KdCust', $selectedKdCust)->get()->keyBy('KdCtk')
            : collect();

        return view('harga-cetak-outdoor-khusus.index', [
            'vipCustomers' => $vipCustomers,
            'printers' => $printers,
            'bahanList' => $bahanList,
            'selectedKdCust' => $selectedKdCust,
            'selectedKdPrn' => $selectedKdPrn,
            'selectedCustomer' => $selectedCustomer,
            'standardPrices' => $standardPrices,
            'khususPrices' => $khususPrices,
        ]);
    }

    public function updateMatrix(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'KdCust' => ['required', 'string', 'exists:customers,KdCust'],
            'KdPrn' => ['required', 'string', 'size:2'],
            'harga' => ['required', 'array'],
            'harga.*' => ['nullable', 'integer', 'min:1'],
        ], [], ['KdCust' => 'customer', 'KdPrn' => 'printer']);

        $customer = Customer::with('limit')->where('KdCust', $data['KdCust'])->first();
        abort_unless($customer?->is_vip, 422, 'Harga khusus hanya bisa diatur untuk customer VIP.');

        $deleteKeys = [];
        $upserts = [];
        foreach ($data['harga'] as $noCetak => $std) {
            $kdCtk = $data['KdPrn'].$noCetak;

            if ($std === null || $std === '') {
                $deleteKeys[] = $kdCtk;

                continue;
            }

            $upserts[] = ['KdCust' => $data['KdCust'], 'KdCtk' => $kdCtk, 'HargaStd' => $std];
        }

        DB::transaction(function () use ($data, $deleteKeys, $upserts): void {
            if ($deleteKeys !== []) {
                HargaCetakOutdoorKhusus::where('KdCust', $data['KdCust'])
                    ->whereIn('KdCtk', $deleteKeys)
                    ->delete();
            }
            if ($upserts !== []) {
                HargaCetakOutdoorKhusus::upsert($upserts, ['KdCust', 'KdCtk'], ['HargaStd']);
            }
        });

        return redirect()->route('harga-cetak-outdoor-khusus.index', ['KdCust' => $data['KdCust'], 'KdPrn' => $data['KdPrn']])
            ->with('status', 'Harga khusus berhasil disimpan.');
    }

    public function copy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source_kd_cust' => ['required', 'string', 'different:target_kd_cust', 'exists:customer_limits,KdCust'],
            'target_kd_cust' => ['required', 'string', 'exists:customer_limits,KdCust'],
            'KdPrn' => ['required', 'string', 'size:2', 'exists:printers_outdoors,KdPrn'],
            'scope' => ['required', Rule::in(['printer', 'all'])],
        ], [], [
            'source_kd_cust' => 'customer sumber',
            'target_kd_cust' => 'customer tujuan',
            'KdPrn' => 'printer',
            'scope' => 'cakupan penyalinan',
        ]);

        $sourceQuery = HargaCetakOutdoorKhusus::query()->where('KdCust', $data['source_kd_cust']);
        if ($data['scope'] === 'printer') {
            $sourceQuery->where('KdCtk', 'like', $data['KdPrn'].'%');
        }

        $sourcePrices = $sourceQuery->get(['KdCtk', 'HargaStd', 'HargaMin']);
        if ($sourcePrices->isEmpty()) {
            return back()->withInput()->with('error', 'Customer sumber belum mempunyai harga khusus pada cakupan yang dipilih.');
        }

        DB::transaction(function () use ($data, $sourcePrices): void {
            $targetQuery = HargaCetakOutdoorKhusus::query()->where('KdCust', $data['target_kd_cust']);
            if ($data['scope'] === 'printer') {
                $targetQuery->where('KdCtk', 'like', $data['KdPrn'].'%');
            }
            $targetQuery->delete();

            $now = now();
            HargaCetakOutdoorKhusus::insert($sourcePrices->map(fn (HargaCetakOutdoorKhusus $price): array => [
                'KdCust' => $data['target_kd_cust'],
                'KdCtk' => $price->KdCtk,
                'HargaStd' => $price->HargaStd,
                'HargaMin' => $price->HargaMin,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });

        $scopeLabel = $data['scope'] === 'all' ? 'semua printer' : 'printer yang dipilih';

        return redirect()->route('harga-cetak-outdoor-khusus.index', [
            'KdCust' => $data['target_kd_cust'],
            'KdPrn' => $data['KdPrn'],
        ])->with('status', sprintf('%d harga khusus untuk %s berhasil disalin.', $sourcePrices->count(), $scopeLabel));
    }
}
