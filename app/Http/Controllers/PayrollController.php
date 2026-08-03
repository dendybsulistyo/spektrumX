<?php

namespace App\Http\Controllers;

use App\Models\GajiPegawai;
use App\Models\Pengeluaran;
use App\Models\PeriodeTutupBuku;
use App\Models\SlipGaji;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function index(Request $request): View
    {
        $periode = $request->filled('periode') ? $request->string('periode')->toString() : now()->format('Y-m');

        $users = User::query()->orderBy('name')->get();
        $gajiConfig = GajiPegawai::query()->get()->keyBy('user_id');
        $slips = SlipGaji::query()->where('periode', $periode)->get()->keyBy('user_id');

        $rows = $users->map(function (User $user) use ($gajiConfig, $slips) {
            $config = $gajiConfig->get($user->id);
            $slip = $slips->get($user->id);

            return [
                'user_id' => $user->id,
                'nama' => $user->name,
                'gaji_pokok' => $config?->gaji_pokok ?? 0,
                'tunjangan' => $config?->tunjangan ?? 0,
                'ada_config' => $config !== null,
                'slip' => $slip,
            ];
        });

        return view('payroll.index', [
            'periode' => $periode,
            'rows' => $rows,
            'totalGajiPokok' => $rows->sum('gaji_pokok') + $rows->sum('tunjangan'),
            'totalDibayar' => $slips->where('status', 'dibayar')->sum('total'),
            'totalDraft' => $slips->where('status', 'draft')->sum('total'),
            'jumlahBelumDiproses' => $rows->filter(fn ($r) => $r['ada_config'] && ! $r['slip'])->count(),
        ]);
    }

    public function updateGaji(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'gaji_pokok' => ['required', 'numeric', 'min:0'],
            'tunjangan' => ['nullable', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        GajiPegawai::updateOrCreate(['user_id' => $user->id], [
            'gaji_pokok' => $data['gaji_pokok'],
            'tunjangan' => $data['tunjangan'] ?? 0,
            'catatan' => $data['catatan'] ?? null,
        ]);

        return back()->with('status', 'Konfigurasi gaji tersimpan.');
    }

    /**
     * Generates draft payslips for the period from each employee's standing
     * gaji_pegawai config — idempotent, skips anyone who already has a slip
     * for that period (user_id+periode is unique) or has no salary configured.
     */
    public function proses(Request $request): RedirectResponse
    {
        $periode = $request->validate(['periode' => ['required', 'date_format:Y-m']])['periode'];

        if (PeriodeTutupBuku::isClosed($periode)) {
            return back()->with('error', "Periode {$periode} sudah ditutup (closing) — tidak bisa memproses gajian baru.");
        }

        $existing = SlipGaji::where('periode', $periode)->pluck('user_id');

        $dibuat = 0;
        foreach (GajiPegawai::whereNotIn('user_id', $existing)->get() as $config) {
            SlipGaji::create([
                'user_id' => $config->user_id,
                'periode' => $periode,
                'gaji_pokok' => $config->gaji_pokok,
                'tunjangan' => $config->tunjangan,
                'potongan' => 0,
                'total' => $config->totalGaji(),
                'status' => 'draft',
            ]);
            $dibuat++;
        }

        return redirect()->route('payroll.index', ['periode' => $periode])
            ->with('status', $dibuat > 0 ? "{$dibuat} slip gaji draft dibuat untuk periode {$periode}." : 'Semua pegawai sudah punya slip untuk periode ini.');
    }

    /**
     * Pays out a draft payslip — records it as a Pengeluaran (so it shows
     * up in Kas Harian / Rekap Pengeluaran like any other expense) and posts
     * the journal entry (Debit Gaji / Credit Kas).
     */
    public function bayar(Request $request, SlipGaji $slipGaji): RedirectResponse
    {
        if ($slipGaji->status === 'dibayar') {
            return back()->with('error', 'Slip gaji ini sudah dibayar.');
        }

        if (PeriodeTutupBuku::isClosed($slipGaji->periode)) {
            return back()->with('error', "Periode {$slipGaji->periode} sudah ditutup (closing) — tidak bisa membayar slip gaji ini.");
        }

        $data = $request->validate([
            'potongan' => ['nullable', 'numeric', 'min:0'],
            'cara_bayar' => ['required', 'in:tunai,qris,transfer'],
            'no_referensi' => ['required_if:cara_bayar,qris,transfer', 'nullable', 'string', 'max:50'],
        ]);

        $potongan = (float) ($data['potongan'] ?? 0);
        $total = $slipGaji->gaji_pokok + $slipGaji->tunjangan - $potongan;

        if ($total <= 0) {
            return back()->with('error', 'Total gaji setelah potongan harus lebih dari nol.');
        }

        DB::transaction(function () use ($slipGaji, $data, $potongan, $total) {
            $namaPegawai = $slipGaji->user?->name ?? "User #{$slipGaji->user_id}";

            $pengeluaran = Pengeluaran::create([
                'tanggal' => now()->format('Y-m-d'),
                'kategori' => 'gaji',
                'keterangan' => "Gaji {$namaPegawai} — periode {$slipGaji->periode}",
                'jumlah' => $total,
                'cara_bayar' => $data['cara_bayar'],
                'no_referensi' => $data['no_referensi'] ?? null,
                'user_id' => auth()->id(),
            ]);

            $noTrans = $this->accounting->post(
                now()->format('Y-m-d'),
                'GJ-'.$slipGaji->id,
                "Gaji {$namaPegawai} {$slipGaji->periode}",
                [
                    ['akun' => AccountingService::AKUN_GAJI, 'debet' => $total],
                    ['akun' => AccountingService::akunKasFor($data['cara_bayar']), 'kredit' => $total],
                ]
            );

            $pengeluaran->update(['no_trans_jurnal' => $noTrans]);

            $slipGaji->update([
                'potongan' => $potongan,
                'total' => $total,
                'status' => 'dibayar',
                'cara_bayar' => $data['cara_bayar'],
                'no_referensi' => $data['no_referensi'] ?? null,
                'dibayar_at' => now(),
                'dibayar_oleh' => auth()->id(),
                'pengeluaran_id' => $pengeluaran->id,
                'no_trans_jurnal' => $noTrans,
            ]);
        });

        return back()->with('status', 'Gaji berhasil dibayar dan dicatat.');
    }
}
