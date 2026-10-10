<?php

namespace App\Http\Controllers;

use App\Models\CashDailyEntry;
use App\Models\User;
use App\Services\AccountingService;
use App\Support\CashAdjustmentJournal;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashAdjustmentController extends Controller
{
    private const CASHIER_EMAIL = 'yovita@spektrum.com';

    public function __construct(private readonly AccountingService $accounting) {}

    public function index(): View
    {
        // Riwayat penyesuaian tidak lagi ditampilkan di halaman ini (permintaan akuntansi);
        // hasilnya terlihat di Rekap Kas Harian, Rekap Kasir per User, & Laporan Kasir Harian.
        return view('keuangan.cash-adjustments', [
            'cashier' => $this->cashier(),
            'expenseAccounts' => CashAdjustmentJournal::expenseAccounts(),
            'defaultExpense' => CashAdjustmentJournal::DEFAULT_EXPENSE_ACCOUNT,
            'cashSources' => CashAdjustmentJournal::CASH_SOURCES,
        ]);
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
            'reason' => ['nullable', 'required_unless:adjustment_type,setor_bank', 'string', 'max:255'],
            'expense_account' => ['required_if:adjustment_type,pengeluaran', 'nullable', Rule::in(CashAdjustmentJournal::expenseAccounts()->pluck('NoAkun')->all())],
            'cash_source' => ['required_if:adjustment_type,setor_tunai', 'nullable', Rule::in(array_keys(CashAdjustmentJournal::CASH_SOURCES))],
        ], [
            'expense_account.required_if' => 'Pilih akun beban untuk pengeluaran.',
            'cash_source.required_if' => 'Pilih sumber dana Setor Tunai.',
            'expense_account.in' => 'Akun beban tidak valid (pilih akun rinci, bukan akun kepala).',
            'cash_source.in' => 'Sumber dana tidak valid.',
        ]);

        // Posisi otomatis: Setor Tunai = kas masuk (Debet); Setor ke Bank & Pengeluaran = kas keluar (Kredit).
        $entrySide = $validated['adjustment_type'] === 'setor_tunai' ? 'debet' : 'kredit';
        $counterAccount = match ($validated['adjustment_type']) {
            'setor_tunai' => CashAdjustmentJournal::CASH_SOURCES[$validated['cash_source']]['account'],
            'setor_bank' => AccountingService::AKUN_KAS_BANK,
            'pengeluaran' => $validated['expense_account'],
        };

        $cashier = $this->cashier();
        abort_unless($cashier, 422, 'Akun Yovita belum tersedia.');

        $occurredAt = Carbon::parse($validated['occurred_at']);
        $type = $validated['adjustment_type'];
        $typeLabel = CashAdjustmentJournal::TYPE_LABELS[$type];
        if ($type === 'setor_tunai') {
            $typeLabel .= ' ('.CashAdjustmentJournal::CASH_SOURCES[$validated['cash_source']]['label'].')';
        }
        $amount = (float) $validated['amount'];

        try {
            DB::transaction(function () use ($amount, $cashier, $entrySide, $occurredAt, $typeLabel, $validated, $type, $counterAccount): void {
            // Semua penyesuaian memakai user Yovita. Mengunci baris user ini
            // membuat perhitungan MAX(urutan)+1 aman dari request bersamaan.
            User::query()->lockForUpdate()->findOrFail($cashier->id);
            $sequence = (int) CashDailyEntry::query()
                ->whereDate('tanggal', $occurredAt->toDateString())
                ->where('user_id', $cashier->id)
                ->max('urutan') + 1;

            $entry = CashDailyEntry::create([
                'tanggal' => $occurredAt->toDateString(),
                'occurred_at' => $occurredAt,
                'user_id' => $cashier->id,
                'source_key' => 'cash-adjustment:'.Str::uuid(),
                'adjustment_type' => $type,
                'counter_account' => $counterAccount,
                'no_nota' => $validated['reference'] ?: null,
                'keterangan' => $typeLabel.($validated['reason'] !== '' ? ' - '.$validated['reason'] : ''),
                'debet' => $entrySide === 'debet' ? $amount : 0,
                'kredit' => $entrySide === 'kredit' ? $amount : 0,
                'urutan' => $sequence,
            ]);

            // Jurnal dibuat dalam transaksi yang sama: gagal jurnal = penyesuaian batal.
            $entry->update(['journal_number' => CashAdjustmentJournal::post($this->accounting, $entry, $counterAccount, $typeLabel)]);
            }, attempts: 3);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['occurred_at' => $e->getMessage()]);
        }

        return redirect()->route('keuangan.cash-adjustments.index')
            ->with('success', 'Penyesuaian kas berhasil dicatat atas nama Yovita dan sudah dijurnal.');
    }

    private function cashier(): ?User
    {
        return User::query()
            ->select(['id', 'name'])
            ->whereRaw('LOWER(email) = ?', [self::CASHIER_EMAIL])
            ->first();
    }
}
