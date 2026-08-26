<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\AccountingPurchase;
use App\Models\AccountingPurchasePayment;
use App\Models\AccountingPurchaseReturn;
use App\Models\AccountingSupplier;
use App\Models\Customer;
use App\Models\JurnalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanAkuntansiController extends Controller
{
    public function jurnalUmum(Request $request): View
    {
        [$dari, $sampai] = $this->dates($request);
        $entries = JurnalEntry::query()->whereBetween('TgTrans', [$dari, $sampai])->orderBy('TgTrans')->orderBy('NoTrans')->get();
        $names = Akun::whereIn('NoAkun', $entries->pluck('NoAkun')->unique())->pluck('NmAkun', 'NoAkun');

        return view('akuntansi.jurnal-umum', compact('dari', 'sampai', 'entries', 'names'));
    }

    public function bukuBesar(Request $request): View
    {
        [$dari, $sampai] = $this->dates($request);
        $accounts = Akun::query()->whereIn('TipeDK', ['D', 'K'])->orderBy('NoAkun')->get();
        $code = $request->string('akun')->toString() ?: $accounts->first()?->NoAkun;
        $account = $accounts->firstWhere('NoAkun', $code);

        abort_unless($account, 404, 'Kode akun tidak ditemukan.');

        $opening = JurnalEntry::query()->where('NoAkun', $account->NoAkun)->where('TgTrans', '<', $dari)
            ->selectRaw('COALESCE(SUM(Debet), 0) debet, COALESCE(SUM(Kredit), 0) kredit')->first();
        $saldoAwal = DB::table('accounting_opening_balances')
            ->where('NoAkun', $account->NoAkun)->where('kode_bantu', '')
            ->where('periode', '<=', substr($dari, 0, 7))
            ->selectRaw('COALESCE(SUM(debet), 0) debet, COALESCE(SUM(kredit), 0) kredit')->first();
        $saldo = $this->balance((float) $opening->debet + (float) $saldoAwal->debet, (float) $opening->kredit + (float) $saldoAwal->kredit, $account->TipeDK);
        $entries = JurnalEntry::query()->where('NoAkun', $account->NoAkun)->whereBetween('TgTrans', [$dari, $sampai])->orderBy('TgTrans')->orderBy('NoTrans')->get()
            ->map(function (JurnalEntry $entry) use (&$saldo, $account) {
                $saldo += $this->balance((float) $entry->Debet, (float) $entry->Kredit, $account->TipeDK);
                $entry->saldo = $saldo;
                return $entry;
            });

        return view('akuntansi.buku-besar', compact('dari', 'sampai', 'accounts', 'account', 'entries', 'saldo'));
    }

    public function neracaSaldo(Request $request): View
    {
        [$dari, $sampai] = $this->dates($request);
        $accounts = Akun::query()->whereIn('TipeDK', ['D', 'K'])->orderBy('NoAkun')->get();
        $mutations = JurnalEntry::query()->whereBetween('TgTrans', [$dari, $sampai])
            ->select('NoAkun', DB::raw('SUM(Debet) debet'), DB::raw('SUM(Kredit) kredit'))->groupBy('NoAkun')->get()->keyBy('NoAkun');
        $openings = DB::table('accounting_opening_balances')->where('kode_bantu', '')
            ->where('periode', '<=', substr($dari, 0, 7))
            ->select('NoAkun', DB::raw('SUM(debet) debet'), DB::raw('SUM(kredit) kredit'))->groupBy('NoAkun')->get()->keyBy('NoAkun');

        $rows = $accounts->map(function (Akun $account) use ($mutations, $openings) {
            $mutation = $mutations->get($account->NoAkun);
            $opening = $openings->get($account->NoAkun);
            $debet = (float) ($mutation->debet ?? 0); $kredit = (float) ($mutation->kredit ?? 0);
            $saldo = $this->balance($debet + (float) ($opening->debet ?? 0), $kredit + (float) ($opening->kredit ?? 0), $account->TipeDK);
            return (object) ['akun' => $account, 'debet' => $debet, 'kredit' => $kredit, 'saldo_debet' => $account->TipeDK === 'D' ? $saldo : 0, 'saldo_kredit' => $account->TipeDK === 'K' ? $saldo : 0];
        })->filter(fn ($row) => $row->debet || $row->kredit || $row->saldo_debet || $row->saldo_kredit)->values();

        return view('akuntansi.neraca-saldo', compact('dari', 'sampai', 'rows'));
    }

    public function hutangSupplier(Request $request): View
    {
        [$dari, $sampai] = $this->dates($request, now()->startOfYear()->toDateString());
        $openingBalances = DB::table('accounting_opening_balances')
            ->where('NoAkun', '21100')->where('kode_bantu', '!=', '')
            ->where('periode', '<=', substr($dari, 0, 7))
            ->select('kode_bantu', DB::raw('SUM(kredit - debet) as saldo'))
            ->groupBy('kode_bantu')->pluck('saldo', 'kode_bantu');
        $purchaseBefore = AccountingPurchase::where('tanggal', '<', $dari)->selectRaw('supplier_id, SUM(total) total')->groupBy('supplier_id')->pluck('total', 'supplier_id');
        $paymentBefore = AccountingPurchasePayment::query()->join('accounting_purchases', 'accounting_purchases.id', '=', 'accounting_purchase_payments.purchase_id')
            ->where('accounting_purchase_payments.tanggal', '<', $dari)->selectRaw('accounting_purchases.supplier_id, SUM(accounting_purchase_payments.jumlah) total')->groupBy('accounting_purchases.supplier_id')->pluck('total', 'supplier_id');
        $returnBefore = AccountingPurchaseReturn::query()->join('accounting_purchases', 'accounting_purchases.id', '=', 'accounting_purchase_returns.purchase_id')
            ->where('accounting_purchase_returns.tanggal', '<', $dari)->selectRaw('accounting_purchases.supplier_id, SUM(accounting_purchase_returns.total) total')->groupBy('accounting_purchases.supplier_id')->pluck('total', 'supplier_id');
        $purchases = AccountingPurchase::whereBetween('tanggal', [$dari, $sampai])->selectRaw('supplier_id, SUM(total) total')->groupBy('supplier_id')->pluck('total', 'supplier_id');
        $payments = AccountingPurchasePayment::query()->join('accounting_purchases', 'accounting_purchases.id', '=', 'accounting_purchase_payments.purchase_id')
            ->whereBetween('accounting_purchase_payments.tanggal', [$dari, $sampai])->selectRaw('accounting_purchases.supplier_id, SUM(accounting_purchase_payments.jumlah) total')->groupBy('accounting_purchases.supplier_id')->pluck('total', 'supplier_id');
        $returns = AccountingPurchaseReturn::query()->join('accounting_purchases', 'accounting_purchases.id', '=', 'accounting_purchase_returns.purchase_id')
            ->whereBetween('accounting_purchase_returns.tanggal', [$dari, $sampai])->selectRaw('accounting_purchases.supplier_id, SUM(accounting_purchase_returns.total) total')->groupBy('accounting_purchases.supplier_id')->pluck('total', 'supplier_id');
        $rows = AccountingSupplier::orderBy('kode_bantu')->get()->map(function (AccountingSupplier $supplier) use ($openingBalances, $purchaseBefore, $paymentBefore, $returnBefore, $purchases, $payments, $returns) {
            $opening = (float) ($openingBalances[$supplier->kode_bantu] ?? 0) + (float) ($purchaseBefore[$supplier->id] ?? 0) - (float) ($paymentBefore[$supplier->id] ?? 0) - (float) ($returnBefore[$supplier->id] ?? 0);
            $purchase = (float) ($purchases[$supplier->id] ?? 0);
            $payment = (float) ($payments[$supplier->id] ?? 0);
            $return = (float) ($returns[$supplier->id] ?? 0);
            return (object) ['supplier' => $supplier, 'opening' => $opening, 'purchases' => $purchase, 'returns' => $return, 'payments' => $payment, 'ending' => $opening + $purchase - $return - $payment];
        })->filter(fn ($row) => $row->opening || $row->purchases || $row->returns || $row->payments)->values();
        $details = AccountingPurchase::with(['supplier', 'payments'])->whereBetween('tanggal', [$dari, $sampai])->latest('tanggal')->latest('id')->get();

        return view('akuntansi.hutang-supplier', compact('dari', 'sampai', 'rows', 'details'));
    }

    public function piutangCustomer(Request $request): View
    {
        [$dari, $sampai] = $this->dates($request, now()->startOfYear()->toDateString());
        $opening = DB::table('accounting_opening_balances')->where('NoAkun', '11102')->where('kode_bantu', '!=', '')
            ->where('periode', '<=', substr($dari, 0, 7))->select('kode_bantu', DB::raw('SUM(debet - kredit) total'))->groupBy('kode_bantu')->pluck('total', 'kode_bantu');
        $before = JurnalEntry::where('NoAkun', '11102')->where('KdBantu', '!=', '')->where('TgTrans', '<', $dari)
            ->select('KdBantu', DB::raw('SUM(Debet - Kredit) total'))->groupBy('KdBantu')->pluck('total', 'KdBantu');
        $mutations = JurnalEntry::where('NoAkun', '11102')->where('KdBantu', '!=', '')->whereBetween('TgTrans', [$dari, $sampai])
            ->select('KdBantu', DB::raw('SUM(Debet) debet'), DB::raw('SUM(Kredit) kredit'))->groupBy('KdBantu')->get()->keyBy('KdBantu');
        $codes = collect($opening->keys())->merge($before->keys())->merge($mutations->keys())->unique()->values();
        $profiles = DB::table('accounting_customer_profiles')->whereIn('kode_bantu', $codes)->get(['kode_bantu', 'customer_kd', 'nama'])->keyBy('kode_bantu');
        $customerCodes = $codes->reject(fn ($code) => $profiles->has($code));
        $customers = Customer::whereIn('KdCust', $customerCodes)->get(['KdCust', 'NmCust'])->keyBy('KdCust');
        $rows = $codes->map(function (string $code) use ($opening, $before, $mutations, $profiles, $customers) {
            $mutation = $mutations->get($code);
            $debet = (float) ($mutation->debet ?? 0);
            $kredit = (float) ($mutation->kredit ?? 0);
            $saldoAwal = (float) ($opening[$code] ?? 0) + (float) ($before[$code] ?? 0);
            $profile = $profiles->get($code);
            $name = $profile->nama ?? $customers->get($code)?->NmCust ?? $code;
            return (object) ['kode' => $code, 'nama' => $name, 'opening' => $saldoAwal, 'penjualan' => $debet, 'pelunasan' => $kredit, 'ending' => $saldoAwal + $debet - $kredit];
        })->filter(fn ($row) => $row->opening || $row->penjualan || $row->pelunasan)->sortByDesc('ending')->values();
        $details = JurnalEntry::where('NoAkun', '11102')->where('KdBantu', '!=', '')->whereBetween('TgTrans', [$dari, $sampai])->orderBy('TgTrans')->orderBy('NoTrans')->get();
        $names = $rows->pluck('nama', 'kode');

        return view('akuntansi.piutang-customer', compact('dari', 'sampai', 'rows', 'details', 'names'));
    }

    public function kasBank(Request $request): View
    {
        [$dari, $sampai] = $this->dates($request, now()->startOfYear()->toDateString());
        $accounts = Akun::whereIn('NoAkun', ['11100', '11101'])->orderBy('NoAkun')->get();
        $openingBalances = DB::table('accounting_opening_balances')->whereIn('NoAkun', $accounts->pluck('NoAkun'))->where('kode_bantu', '')
            ->where('periode', '<=', substr($dari, 0, 7))->select('NoAkun', DB::raw('SUM(debet - kredit) total'))->groupBy('NoAkun')->pluck('total', 'NoAkun');
        $before = JurnalEntry::whereIn('NoAkun', $accounts->pluck('NoAkun'))->where('TgTrans', '<', $dari)
            ->select('NoAkun', DB::raw('SUM(Debet - Kredit) total'))->groupBy('NoAkun')->pluck('total', 'NoAkun');
        $mutations = JurnalEntry::whereIn('NoAkun', $accounts->pluck('NoAkun'))->whereBetween('TgTrans', [$dari, $sampai])
            ->select('NoAkun', DB::raw('SUM(Debet) debet'), DB::raw('SUM(Kredit) kredit'))->groupBy('NoAkun')->get()->keyBy('NoAkun');
        $rows = $accounts->map(function (Akun $account) use ($openingBalances, $before, $mutations) {
            $mutation = $mutations->get($account->NoAkun);
            $opening = (float) ($openingBalances[$account->NoAkun] ?? 0) + (float) ($before[$account->NoAkun] ?? 0);
            $in = (float) ($mutation->debet ?? 0); $out = (float) ($mutation->kredit ?? 0);
            return (object) ['account' => $account, 'opening' => $opening, 'in' => $in, 'out' => $out, 'ending' => $opening + $in - $out];
        });
        $details = JurnalEntry::whereIn('NoAkun', $accounts->pluck('NoAkun'))->whereBetween('TgTrans', [$dari, $sampai])->orderBy('TgTrans')->orderBy('NoTrans')->get();
        $names = $accounts->pluck('NmAkun', 'NoAkun');

        return view('akuntansi.kas-bank', compact('dari', 'sampai', 'rows', 'details', 'names'));
    }

    public function neraca(Request $request): View
    {
        $tanggal = $request->filled('tanggal') ? $request->string('tanggal')->toString() : now()->toDateString();
        $accounts = Akun::whereIn('TipeDK', ['D', 'K'])->where('TipeNL', 'N')->orderBy('NoAkun')->get();
        $opening = DB::table('accounting_opening_balances')->where('kode_bantu', '')->where('periode', '<=', substr($tanggal, 0, 7))
            ->select('NoAkun', DB::raw('SUM(debet-kredit) total'))->groupBy('NoAkun')->pluck('total', 'NoAkun');
        $journal = JurnalEntry::whereDate('TgTrans', '<=', $tanggal)->select('NoAkun', DB::raw('SUM(Debet-Kredit) total'))->groupBy('NoAkun')->pluck('total', 'NoAkun');
        $rows = $accounts->map(function (Akun $account) use ($opening, $journal) {
            $raw = (float) ($opening[$account->NoAkun] ?? 0) + (float) ($journal[$account->NoAkun] ?? 0);
            return (object) ['akun' => $account, 'saldo' => $account->TipeDK === 'K' ? -$raw : $raw];
        })->filter(fn ($row) => abs($row->saldo) > 0.01)->values();
        $aktivaLancar = $rows->filter(fn ($row) => str_starts_with($row->akun->NoAkun, '11'))->values();
        $aktivaTetap = $rows->filter(fn ($row) => str_starts_with($row->akun->NoAkun, '12'))->values();
        $hutang = $rows->filter(fn ($row) => str_starts_with($row->akun->NoAkun, '2'))->values();
        $modal = $rows->filter(fn ($row) => str_starts_with($row->akun->NoAkun, '3'))->values();
        $labaTahunBerjalan = $this->labaTahunBerjalan($tanggal);
        $totalAktiva = $aktivaLancar->sum('saldo') + $aktivaTetap->sum('saldo');
        $totalPasiva = $hutang->sum('saldo') + $modal->sum('saldo') + $labaTahunBerjalan;

        $fmt = fn ($number) => number_format(abs((float) $number), 0, ',', '.');
        return view('akuntansi.neraca', compact('tanggal', 'aktivaLancar', 'aktivaTetap', 'hutang', 'modal', 'labaTahunBerjalan', 'totalAktiva', 'totalPasiva', 'fmt'));
    }

    public function perubahanModal(Request $request): View
    {
        $dari = $request->filled('dari') ? $request->string('dari')->toString() : now()->startOfYear()->toDateString();
        $sampai = $request->filled('sampai') ? $request->string('sampai')->toString() : now()->toDateString();
        $modalAwal = $this->accountBalanceBefore(['31000', '32000'], $dari, 'K');
        $tambahanModal = $this->accountMovement(['31000', '32000'], $dari, $sampai, 'K');
        $prive = $this->accountMovement(['33000'], $dari, $sampai, 'D');
        $laba = $this->labaUntukPeriode($dari, $sampai);
        $modalAkhir = $modalAwal + $tambahanModal + $laba - $prive;
        $fmt = fn ($number) => number_format(abs((float) $number), 0, ',', '.');
        return view('akuntansi.perubahan-modal', compact('dari', 'sampai', 'modalAwal', 'tambahanModal', 'prive', 'laba', 'modalAkhir', 'fmt'));
    }

    /** @return array{0:string,1:string} */
    private function dates(Request $request, ?string $defaultFrom = null): array
    {
        return [
            $request->filled('dari') ? $request->string('dari')->toString() : ($defaultFrom ?? now()->startOfMonth()->toDateString()),
            $request->filled('sampai') ? $request->string('sampai')->toString() : now()->toDateString(),
        ];
    }

    private function balance(float $debet, float $kredit, string $normal): float
    {
        return $normal === 'K' ? $kredit - $debet : $debet - $kredit;
    }

    private function labaTahunBerjalan(string $tanggal): float
    {
        return $this->labaUntukPeriode(substr($tanggal, 0, 4).'-01-01', $tanggal);
    }

    private function labaUntukPeriode(string $dari, string $sampai): float
    {
        $accounts = Akun::where('TipeNL', 'L')->get();
        $totals = JurnalEntry::whereIn('NoAkun', $accounts->pluck('NoAkun'))->whereBetween('TgTrans', [$dari, $sampai])
            ->select('NoAkun', DB::raw('SUM(Debet) debet'), DB::raw('SUM(Kredit) kredit'))->groupBy('NoAkun')->get()->keyBy('NoAkun');
        return $accounts->sum(function (Akun $account) use ($totals) {
            $row = $totals->get($account->NoAkun);
            $saldoNormal = $account->TipeDK === 'K' ? (float) ($row->kredit ?? 0) - (float) ($row->debet ?? 0) : (float) ($row->debet ?? 0) - (float) ($row->kredit ?? 0);
            // Pendapatan menambah laba; HPP dan beban menguranginya. Prive
            // dilaporkan tersendiri pada Perubahan Modal, bukan laba rugi.
            if (str_starts_with($account->NoAkun, '3')) return 0;
            return $account->TipeDK === 'K' ? $saldoNormal : -$saldoNormal;
        });
    }

    private function accountBalanceBefore(array $accounts, string $date, string $normal): float
    {
        $opening = DB::table('accounting_opening_balances')->where('kode_bantu', '')->whereIn('NoAkun', $accounts)->where('periode', '<=', substr($date, 0, 7))->selectRaw('COALESCE(SUM(debet-kredit),0) total')->value('total');
        $journal = JurnalEntry::whereIn('NoAkun', $accounts)->where('TgTrans', '<', $date)->selectRaw('COALESCE(SUM(Debet-Kredit),0) total')->value('total');
        $raw = (float) $opening + (float) $journal;
        return $normal === 'K' ? -$raw : $raw;
    }

    private function accountMovement(array $accounts, string $dari, string $sampai, string $normal): float
    {
        $row = JurnalEntry::whereIn('NoAkun', $accounts)->whereBetween('TgTrans', [$dari, $sampai])->selectRaw('COALESCE(SUM(Debet),0) debet, COALESCE(SUM(Kredit),0) kredit')->first();
        return $normal === 'K' ? (float) $row->kredit - (float) $row->debet : (float) $row->debet - (float) $row->kredit;
    }
}
