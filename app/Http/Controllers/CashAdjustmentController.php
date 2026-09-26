<?php

namespace App\Http\Controllers;

use App\Models\CashDailyEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CashAdjustmentController extends Controller
{
    private const CASHIER_EMAIL = 'yovita@spektrum.com';

    public function index(): View
    {
        $cashier = $this->cashier();
        $adjustments = CashDailyEntry::query()
            ->cashAdjustments()
            ->select(['id', 'tanggal', 'occurred_at', 'user_id', 'no_nota', 'keterangan', 'debet', 'kredit'])
            ->with('user:id,name')
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(25);

        return view('keuangan.cash-adjustments', compact('cashier', 'adjustments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'amount' => preg_replace('/\D/', '', (string) $request->input('amount')),
            'reference' => trim((string) $request->input('reference')),
            'reason' => trim((string) $request->input('reason')),
        ]);

        $validated = $request->validate([
            'occurred_at' => ['required', 'date'],
            'adjustment_type' => ['required', 'in:setor_tunai,setor_bank,pengeluaran'],
            'amount' => ['required', 'integer', 'min:1', 'max:9999999999999'],
            'reference' => ['nullable', 'string', 'max:80'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $cashier = $this->cashier();
        abort_unless($cashier, 422, 'Akun Yovita belum tersedia.');

        $occurredAt = Carbon::parse($validated['occurred_at']);
        $type = $validated['adjustment_type'];
        $typeLabel = match ($type) {
            'setor_tunai' => 'Setor Tunai',
            'setor_bank' => 'Setor ke Bank',
            'pengeluaran' => 'Pengeluaran',
        };
        $amount = (float) $validated['amount'];
        $sequence = (int) CashDailyEntry::query()
            ->whereDate('tanggal', $occurredAt->toDateString())
            ->max('urutan') + 1;

        CashDailyEntry::create([
            'tanggal' => $occurredAt->toDateString(),
            'occurred_at' => $occurredAt,
            'user_id' => $cashier->id,
            'source_key' => 'cash-adjustment:'.Str::uuid(),
            'no_nota' => $validated['reference'] ?: null,
            'keterangan' => $typeLabel.' - '.$validated['reason'],
            'debet' => $type === 'setor_tunai' ? $amount : 0,
            'kredit' => $type !== 'setor_tunai' ? $amount : 0,
            'urutan' => $sequence,
        ]);

        return redirect()->route('keuangan.cash-adjustments.index')
            ->with('success', 'Penyesuaian kas berhasil dicatat atas nama Yovita.');
    }

    private function cashier(): ?User
    {
        return User::query()
            ->select(['id', 'name'])
            ->whereRaw('LOWER(email) = ?', [self::CASHIER_EMAIL])
            ->first();
    }
}
