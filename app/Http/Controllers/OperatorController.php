<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOperatorRequest;
use App\Models\Operator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperatorController extends Controller
{
    public function index(Request $request): View
    {
        $operators = Operator::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmOpr', 'like', "%{$search}%")
                    ->orWhere('KdOpr', 'like', "%{$search}%");
            })
            ->orderBy('NmOpr')
            ->paginate(15)
            ->withQueryString();

        return view('operators.index', compact('operators'));
    }

    public function create(): View
    {
        return view('operators.create');
    }

    public function store(StoreOperatorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['Status'] = $request->boolean('Status');

        Operator::create($data);

        return redirect()->route('operators.index')->with('status', 'Operator berhasil ditambahkan.');
    }

    public function edit(Operator $operator): View
    {
        return view('operators.edit', compact('operator'));
    }

    public function update(StoreOperatorRequest $request, Operator $operator): RedirectResponse
    {
        $data = $request->validated();
        $data['Status'] = $request->boolean('Status');

        $operator->update($data);

        return redirect()->route('operators.index')->with('status', 'Operator berhasil diperbarui.');
    }

    public function destroy(Operator $operator): RedirectResponse
    {
        $operator->delete();

        return redirect()->route('operators.index')->with('status', 'Operator berhasil dihapus.');
    }
}
