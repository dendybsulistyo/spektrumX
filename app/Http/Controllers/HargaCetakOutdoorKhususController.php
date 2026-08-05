<?php

namespace App\Http\Controllers;

use App\Models\BahanCetakOutdoor;
use App\Models\Customer;
use App\Models\HargaCetakOutdoor;
use App\Models\HargaCetakOutdoorKhusus;
use App\Models\PrinterOutdoor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HargaCetakOutdoorKhususController extends Controller
{
    /**
     * Mirrors HargaCetakOutdoorController's Printer × Bahan matrix, but
     * scoped to one VIP customer + one printer at a time (matches how the
     * shop's paper price list is actually organized per customer). Only
     * customers with a CustomerLimit row (i.e. is_vip) are selectable —
     * this feature exists specifically for VIP pricing.
     */
    public function index(Request $request): View
    {
        $vipCustomers = Customer::whereHas('limit')->orderBy('NmCust')->get();
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
            'harga.*' => ['nullable', 'numeric', 'min:0'],
        ], [], ['KdCust' => 'customer', 'KdPrn' => 'printer']);

        $customer = Customer::where('KdCust', $data['KdCust'])->first();
        abort_unless($customer?->is_vip, 422, 'Harga khusus hanya bisa diatur untuk customer VIP.');

        foreach ($data['harga'] as $noCetak => $std) {
            $kdCtk = $data['KdPrn'].$noCetak;

            if ($std === null || $std === '') {
                HargaCetakOutdoorKhusus::where('KdCust', $data['KdCust'])->where('KdCtk', $kdCtk)->delete();

                continue;
            }

            HargaCetakOutdoorKhusus::updateOrCreate(
                ['KdCust' => $data['KdCust'], 'KdCtk' => $kdCtk],
                ['HargaStd' => $std]
            );
        }

        return redirect()->route('harga-cetak-outdoor-khusus.index', ['KdCust' => $data['KdCust'], 'KdPrn' => $data['KdPrn']])
            ->with('status', 'Harga khusus berhasil disimpan.');
    }
}
