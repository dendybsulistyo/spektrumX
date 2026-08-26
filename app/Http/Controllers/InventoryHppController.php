<?php

namespace App\Http\Controllers;

use App\Models\AccountingInventoryCount;
use App\Models\AccountingInventoryItem;
use App\Models\JurnalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryHppController extends Controller
{
    public function index(Request $request): View
    {
        $dari = $request->filled('dari') ? $request->string('dari')->toString() : now()->startOfYear()->toDateString();
        $sampai = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->toDateString();
        $items = AccountingInventoryItem::with(['counts' => fn ($query) => $query->whereDate('tanggal', '<=', $sampai)->latest('tanggal')])->orderBy('kelompok')->orderBy('nama')->get();
        $opening = $this->inventoryValueBefore($dari);
        $ending = $this->inventoryValueAt($sampai);
        $hasEndingCount = AccountingInventoryCount::whereDate('tanggal', '<=', $sampai)->exists();
        $opening = $this->withOpeningFallback($opening, $dari);
        $purchases = $this->journalTotals($dari, $sampai, ['53000', '53003']);
        $overhead = $this->journalTotals($dari, $sampai, ['53004', '53005', '63001']);
        $materials = $opening['bahan_baku'] + $opening['bahan_penolong'] + $purchases['53000'] + $purchases['53003'] - $ending['bahan_baku'] - $ending['bahan_penolong'];
        $hppProduksi = $materials + $overhead['53004'] + $overhead['53005'] + $overhead['63001'];
        $hppPenjualan = $opening['barang_jadi'] + $hppProduksi - $ending['barang_jadi'];

        return view('akuntansi.inventory-hpp', compact('dari', 'sampai', 'items', 'opening', 'ending', 'purchases', 'overhead', 'materials', 'hppProduksi', 'hppPenjualan', 'hasEndingCount'));
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate(['kode' => ['required', 'string', 'max:30', 'unique:accounting_inventory_items,kode'], 'nama' => ['required', 'string', 'max:150'], 'kelompok' => ['required', 'in:bahan_baku,bahan_penolong,barang_jadi'], 'satuan' => ['required', 'string', 'max:20']]);
        AccountingInventoryItem::create($data + ['is_active' => true]);
        return back()->with('status', 'Item persediaan ditambahkan.');
    }

    public function report(Request $request): View
    {
        $dari = $request->filled('dari') ? $request->string('dari')->toString() : now()->startOfYear()->toDateString();
        $sampai = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->toDateString();
        $opening = $this->withOpeningFallback($this->inventoryValueBefore($dari), $dari);
        $ending = $this->inventoryValueAt($sampai);
        $purchases = $this->journalTotals($dari, $sampai, ['53000', '53003']);
        $overhead = $this->journalTotals($dari, $sampai, ['53004', '53005', '63001']);
        $materials = $opening['bahan_baku'] + $opening['bahan_penolong'] + $purchases['53000'] + $purchases['53003'] - $ending['bahan_baku'] - $ending['bahan_penolong'];
        $hppProduksi = $materials + $overhead['53004'] + $overhead['53005'] + $overhead['63001'];
        $hppPenjualan = $opening['barang_jadi'] + $hppProduksi - $ending['barang_jadi'];
        $hasEndingCount = AccountingInventoryCount::whereDate('tanggal', '<=', $sampai)->exists();
        $fmt = fn ($number) => number_format(abs((float) $number), 0, ',', '.');
        return view('akuntansi.hpp-report', compact('dari', 'sampai', 'opening', 'ending', 'purchases', 'overhead', 'materials', 'hppProduksi', 'hppPenjualan', 'hasEndingCount', 'fmt'));
    }

    public function storeCount(Request $request, AccountingInventoryItem $item): RedirectResponse
    {
        $data = $request->validate(['tanggal' => ['required', 'date'], 'qty' => ['required', 'numeric', 'min:0'], 'harga_satuan' => ['required', 'numeric', 'min:0'], 'keterangan' => ['nullable', 'string', 'max:255']]);
        $data['nilai'] = round((float) $data['qty'] * (float) $data['harga_satuan']);
        $data['user_id'] = auth()->id();
        AccountingInventoryCount::updateOrCreate(['inventory_item_id' => $item->id, 'tanggal' => $data['tanggal']], $data);
        return back()->with('status', 'Stok opname '.$item->nama.' disimpan.');
    }

    /** @return array<string,float> */
    private function inventoryValueBefore(string $date): array { return $this->latestCountValues(fn ($query) => $query->whereDate('tanggal', '<', $date)); }
    /** @return array<string,float> */
    private function inventoryValueAt(string $date): array { return $this->latestCountValues(fn ($query) => $query->whereDate('tanggal', '<=', $date)); }

    /** @return array<string,float> */
    private function latestCountValues(callable $filter): array
    {
        $base = DB::table('accounting_inventory_counts'); $filter($base);
        $latest = $base->select('inventory_item_id', DB::raw('MAX(tanggal) tanggal'))->groupBy('inventory_item_id');
        $values = DB::table('accounting_inventory_counts as c')->joinSub($latest, 'latest', fn ($join) => $join->on('c.inventory_item_id', '=', 'latest.inventory_item_id')->on('c.tanggal', '=', 'latest.tanggal'))
            ->join('accounting_inventory_items as i', 'i.id', '=', 'c.inventory_item_id')->select('i.kelompok', DB::raw('SUM(c.nilai) total'))->groupBy('i.kelompok')->pluck('total', 'i.kelompok');
        return ['bahan_baku' => (float) ($values['bahan_baku'] ?? 0), 'bahan_penolong' => (float) ($values['bahan_penolong'] ?? 0), 'barang_jadi' => (float) ($values['barang_jadi'] ?? 0)];
    }

    /** @param array<string,float> $values @return array<string,float> */
    private function withOpeningFallback(array $values, string $date): array
    {
        $opening = DB::table('accounting_opening_balances')->where('kode_bantu', '')->where('periode', '<=', substr($date, 0, 7))->whereIn('NoAkun', ['11301', '11400', '11200'])
            ->select('NoAkun', DB::raw('SUM(debet-kredit) total'))->groupBy('NoAkun')->pluck('total', 'NoAkun');
        foreach (['bahan_baku' => '11301', 'bahan_penolong' => '11400', 'barang_jadi' => '11200'] as $group => $account) if ($values[$group] == 0) $values[$group] = (float) ($opening[$account] ?? 0);
        return $values;
    }

    /** @return array<string,float> */
    private function journalTotals(string $dari, string $sampai, array $accounts): array
    {
        $rows = JurnalEntry::whereIn('NoAkun', $accounts)->whereBetween('TgTrans', [$dari, $sampai])->select('NoAkun', DB::raw('SUM(Debet-Kredit) total'))->groupBy('NoAkun')->pluck('total', 'NoAkun');
        return collect($accounts)->mapWithKeys(fn ($account) => [$account => (float) ($rows[$account] ?? 0)])->all();
    }
}
