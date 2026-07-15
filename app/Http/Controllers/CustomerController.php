<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $customers = Customer::query()
            ->with('limit')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NmCust', 'like', "%{$search}%")
                    ->orWhere('KdCust', 'like', "%{$search}%");
            })
            ->orderBy('NmCust')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $customer = Customer::create($this->onlyCustomerFields($data));

        $this->syncLimit($customer, $data);

        return redirect()->route('customers.index')->with('status', 'Customer berhasil ditambahkan.');
    }

    public function edit(Customer $customer): View
    {
        $customer->load('limit');

        return view('customers.edit', compact('customer'));
    }

    public function update(StoreCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $customer->update($this->onlyCustomerFields($data));

        $this->syncLimit($customer, $data);

        return redirect()->route('customers.index')->with('status', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        CustomerLimit::where('KdCust', $customer->KdCust)->delete();
        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function onlyCustomerFields(array $data): array
    {
        return collect($data)->only(['KdCust', 'NmCust', 'Alamat', 'Kota', 'Telp'])->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncLimit(Customer $customer, array $data): void
    {
        $isVip = (bool) ($data['is_vip'] ?? false);

        if ($isVip) {
            CustomerLimit::updateOrCreate(
                ['KdCust' => $customer->KdCust],
                ['Batas' => $data['Batas'], 'Total' => $customer->limit?->Total ?? 0]
            );
        } else {
            CustomerLimit::where('KdCust', $customer->KdCust)->delete();
        }
    }
}
