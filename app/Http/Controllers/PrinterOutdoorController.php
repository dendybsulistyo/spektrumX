<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrinterOutdoorRequest;
use App\Models\PrinterOutdoor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrinterOutdoorController extends Controller
{
    public function index(Request $request): View
    {
        $printerOutdoors = PrinterOutdoor::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmPrn', 'like', "%{$search}%")
                    ->orWhere('KdPrn', 'like', "%{$search}%");
            })
            ->orderBy('NoUrut')
            ->paginate(15)
            ->withQueryString();

        return view('printer-outdoor.index', compact('printerOutdoors'));
    }

    public function create(): View
    {
        return view('printer-outdoor.create');
    }

    public function store(StorePrinterOutdoorRequest $request): RedirectResponse
    {
        PrinterOutdoor::create($request->validated());

        return redirect()->route('printer-outdoor.index')->with('status', 'Printer outdoor berhasil ditambahkan.');
    }

    public function edit(PrinterOutdoor $printerOutdoor): View
    {
        return view('printer-outdoor.edit', compact('printerOutdoor'));
    }

    public function update(StorePrinterOutdoorRequest $request, PrinterOutdoor $printerOutdoor): RedirectResponse
    {
        $printerOutdoor->update($request->validated());

        return redirect()->route('printer-outdoor.index')->with('status', 'Printer outdoor berhasil diperbarui.');
    }

    public function destroy(PrinterOutdoor $printerOutdoor): RedirectResponse
    {
        $printerOutdoor->delete();

        return redirect()->route('printer-outdoor.index')->with('status', 'Printer outdoor berhasil dihapus.');
    }
}
