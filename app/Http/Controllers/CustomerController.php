<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerLimit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->when($request->boolean('vip'), fn ($q) => $q->whereHas('limit'))
            ->orderBy('NmCust')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    /**
     * Semua customer diurutkan berdasar tanggal transaksi terbaru
     * (order paling baru dari gabungan order Indoor/Outdoor/Artwork).
     * Customer yang belum pernah order muncul paling bawah.
     */
    public function aktif(Request $request): View
    {
        $transaksi = DB::table('order_indoor')->select('KdCust', 'TglOrder')
            ->unionAll(DB::table('order_outdoor')->select('KdCust', 'TglOrder'))
            ->unionAll(DB::table('order_artwork')->select('KdCust', 'TglOrder'));

        $lastTransaksi = DB::query()->fromSub($transaksi, 'trx')
            ->select('KdCust', DB::raw('MAX(TglOrder) as tanggal_transaksi'))
            ->groupBy('KdCust');

        $customers = Customer::query()
            ->leftJoinSub($lastTransaksi, 'lt', 'lt.KdCust', '=', 'customers.KdCust')
            ->select('customers.*', 'lt.tanggal_transaksi')
            ->with('limit')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('customers.NmCust', 'like', "%{$search}%")
                    ->orWhere('customers.KdCust', 'like', "%{$search}%");
            })
            ->orderByRaw('lt.tanggal_transaksi IS NULL, lt.tanggal_transaksi DESC')
            ->paginate(15)
            ->withQueryString();

        return view('customers.aktif', compact('customers'));
    }

    /**
     * Read-only queue of a customer's orders that are not yet fully paid.
     * This lets kasir check both unpaid and DP orders without granting
     * access to edit customer data or showing completed transactions.
     */
    public function show(Customer $customer): View
    {
        $ordersQuery = DB::query()->fromSub(
            DB::table('order_indoor')
                ->where('KdCust', $customer->KdCust)
                ->whereIn('status_bayar', ['belum_bayar', 'dp'])
                ->select('id', 'NoOrder', 'TglOrder', 'total', 'jumlah_piutang', 'status_bayar', 'status', DB::raw("'indoor' as order_type"))
                ->unionAll(
                    DB::table('order_outdoor')
                        ->where('KdCust', $customer->KdCust)
                        ->whereIn('status_bayar', ['belum_bayar', 'dp'])
                        ->select('id', 'NoOrder', 'TglOrder', 'total', 'jumlah_piutang', 'status_bayar', 'status', DB::raw("'outdoor' as order_type"))
                )
                ->unionAll(
                    DB::table('order_artwork')
                        ->where('KdCust', $customer->KdCust)
                        ->whereIn('status_bayar', ['belum_bayar', 'dp'])
                        ->select('id', 'NoOrder', 'TglOrder', 'total', 'jumlah_piutang', 'status_bayar', 'status', DB::raw("'artwork' as order_type"))
                ),
            'orders'
        );

        $totalBelumLunas = (clone $ordersQuery)
            ->sum(DB::raw('CASE WHEN status_bayar = \'dp\' THEN jumlah_piutang ELSE total END'));

        $orders = $ordersQuery
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->paginate(20)
            ->withQueryString();

        return view('customers.show', compact('customer', 'orders', 'totalBelumLunas'));
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

    /**
     * Sarankan kode customer berdasarkan nama, mengikuti pola kode lama
     * (3 huruf awal nama + nomor urut, misal "Rio Spektrum" -> RIO001),
     * dan pastikan tidak bentrok dengan kode yang sudah ada.
     */
    public function suggestCode(Request $request): JsonResponse
    {
        return response()->json(['code' => $this->generateCode((string) $request->query('name', ''))]);
    }

    /**
     * Minimal customer creation used from the order form's "+ Tambah
     * customer baru" flow — no permission gate beyond auth (front-desk
     * staff taking orders usually don't have customers.manage), only
     * Nama/Telp required, and KdCust is auto-generated rather than typed,
     * so it doesn't interrupt the keyboard-driven order flow.
     */
    public function quickCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'NmCust' => ['required', 'string', 'max:50'],
            'Telp' => ['required', 'string', 'max:40'],
            'Alamat' => ['nullable', 'string', 'max:60'],
            'Kota' => ['nullable', 'string', 'max:25'],
        ], [], ['NmCust' => 'nama customer', 'Telp' => 'telepon']);

        $customer = Customer::create([
            'KdCust' => $this->generateCode($data['NmCust']),
            'NmCust' => $data['NmCust'],
            'Telp' => $data['Telp'],
            'Alamat' => $data['Alamat'] ?? '',
            'Kota' => $data['Kota'] ?? '',
        ]);

        return response()->json($customer->only(['KdCust', 'NmCust', 'Telp']));
    }

    /**
     * Sarankan kode customer berdasarkan nama, mengikuti pola kode lama
     * (3 huruf awal nama + nomor urut, misal "Rio Spektrum" -> RIO001),
     * dan pastikan tidak bentrok dengan kode yang sudah ada.
     */
    private function generateCode(string $name): string
    {
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $name) ?? '');
        $prefix = substr(str_pad($letters !== '' ? $letters : 'CST', 3, 'X'), 0, 3);

        $digitLength = max(1, 6 - strlen($prefix));

        $lastNumber = Customer::where('KdCust', 'like', $prefix.'%')
            ->pluck('KdCust')
            ->map(fn ($code) => (int) substr($code, strlen($prefix)))
            ->max();

        $next = ($lastNumber ?? 0) + 1;
        $code = $prefix.str_pad((string) $next, $digitLength, '0', STR_PAD_LEFT);

        while (Customer::where('KdCust', $code)->exists()) {
            $next++;
            $code = $prefix.str_pad((string) $next, $digitLength, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    /**
     * Lightweight autocomplete search for order forms — avoids loading all
     * ~18k customers into a <select> (was causing noticeable page lag).
     */
    public function search(Request $request): JsonResponse
    {
        $q = (string) $request->query('q', '');

        $customers = Customer::query()
            ->whereRaw("TRIM(KdCust) != ''")
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('NmCust', 'like', "%{$q}%")
                        ->orWhere('KdCust', 'like', "%{$q}%");
                });
            })
            ->orderBy('NmCust')
            ->limit(20)
            ->get(['KdCust', 'NmCust', 'Telp']);

        return response()->json($customers);
    }

    /**
     * VIP price overrides for Outdoor (harga_cetak_outdoor_khusus), keyed
     * by KdCtk — fetched client-side by order-outdoor/_form.blade.php when
     * a customer is picked via the live search widget (no page reload), so
     * the on-screen estimate matches what OrderPricingService will
     * actually charge. Empty object for non-VIP customers / no overrides.
     */
    public function hargaCetakOutdoorKhusus(Customer $customer): JsonResponse
    {
        $khusus = $customer->hargaCetakOutdoorKhusus()->get()
            ->mapWithKeys(fn ($h) => [$h->KdCtk => ['std' => (float) $h->HargaStd, 'min' => (float) $h->HargaMin]]);

        return response()->json($khusus);
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
        return collect($data)->only(['KdCust', 'NmCust', 'Alamat', 'Kota', 'Telp', 'NPWP'])->all();
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
