<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePrinterRequest;
use App\Models\Printer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrinterController extends Controller
{
    public function index(Request $request): View
    {
        $printers = Printer::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmPrn', 'like', "%{$search}%")
                    ->orWhere('KdPrn', 'like', "%{$search}%");
            })
            ->orderBy('NoUrut')
            ->paginate(15)
            ->withQueryString();

        return view('printers.index', compact('printers'));
    }

    public function create(): View
    {
        return view('printers.create');
    }

    public function store(StorePrinterRequest $request): RedirectResponse
    {
        Printer::create($request->validated());

        return redirect()->route('printers.index')->with('status', 'Printer berhasil ditambahkan.');
    }

    public function edit(Printer $printer): View
    {
        return view('printers.edit', compact('printer'));
    }

    public function update(StorePrinterRequest $request, Printer $printer): RedirectResponse
    {
        $printer->update($request->validated());

        return redirect()->route('printers.index')->with('status', 'Printer berhasil diperbarui.');
    }

    public function destroy(Printer $printer): RedirectResponse
    {
        $printer->delete();

        return redirect()->route('printers.index')->with('status', 'Printer berhasil dihapus.');
    }
}
