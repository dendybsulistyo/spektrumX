<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\JurnalEntry;
use App\Models\JurnalManual;
use App\Models\PeriodeTutupBuku;
use App\Services\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JurnalManualController extends Controller
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function index(Request $request): View
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : now()->startOfMonth()->format('Y-m-d');
        $to = $request->filled('to') ? $request->string('to')->toString() : now()->format('Y-m-d');

        $jurnalManuals = JurnalManual::with(['user', 'dibatalkanOleh'])
            ->whereBetween('tanggal', [$from, $to])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $linesByTransaction = JurnalEntry::query()
            ->whereIn('NoTrans', $jurnalManuals->pluck('no_trans_jurnal')->filter()->unique())
            ->orderBy('NoAkun')
            ->get(['NoTrans', 'NoAkun', 'Debet', 'Kredit'])
            ->groupBy('NoTrans');

        $accountNames = Akun::query()
            ->whereIn('NoAkun', $linesByTransaction->flatten(1)->pluck('NoAkun')->unique())
            ->pluck('NmAkun', 'NoAkun');

        $entries = $jurnalManuals
            ->map(function (JurnalManual $jm) use ($linesByTransaction, $accountNames) {
                $lines = $linesByTransaction->get($jm->no_trans_jurnal, collect());

                return [
                    'model' => $jm,
                    'total' => $lines->sum('Debet'),
                    'lines' => $lines->map(fn ($l) => [
                        'akun' => $l->NoAkun,
                        'nama' => $accountNames->get($l->NoAkun, $l->NoAkun),
                        'debet' => (float) $l->Debet,
                        'kredit' => (float) $l->Kredit,
                    ]),
                ];
            });

        return view('keuangan.jurnal-manual', [
            'entries' => $entries,
            'from' => $from,
            'to' => $to,
            'akunOptions' => Akun::where('TipeDK', '!=', '-')->orderBy('NoAkun')->get(['NoAkun', 'NmAkun']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'keterangan' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.akun' => ['required', Rule::exists('am__', 'NoAkun')],
            'lines.*.posisi' => ['required', 'in:debet,kredit'],
            'lines.*.jumlah' => ['required', 'numeric', 'min:1'],
        ]);

        if (PeriodeTutupBuku::isClosed($data['tanggal'])) {
            return back()->withInput()->with('error', 'Periode tanggal ini sudah ditutup (closing) — tidak bisa posting jurnal baru.');
        }

        $postLines = collect($data['lines'])->map(fn (array $l) => [
            'akun' => $l['akun'],
            'debet' => $l['posisi'] === 'debet' ? (float) $l['jumlah'] : 0,
            'kredit' => $l['posisi'] === 'kredit' ? (float) $l['jumlah'] : 0,
        ])->all();

        $totalDebet = array_sum(array_column($postLines, 'debet'));
        $totalKredit = array_sum(array_column($postLines, 'kredit'));

        if (abs($totalDebet - $totalKredit) > 0.01) {
            return back()->withInput()->with('error', "Jurnal tidak balance: debet Rp {$totalDebet} != kredit Rp {$totalKredit}.");
        }

        DB::transaction(function () use ($data, $postLines) {
            abort_if(
                PeriodeTutupBuku::isClosed($data['tanggal']),
                422,
                'Periode tanggal ini sudah ditutup (closing) — tidak bisa posting jurnal baru.'
            );

            $jurnalManual = JurnalManual::create([
                'tanggal' => $data['tanggal'],
                'keterangan' => $data['keterangan'],
                'status' => 'posted',
                'user_id' => auth()->id(),
            ]);

            $noTrans = $this->accounting->post($data['tanggal'], 'JM-'.$jurnalManual->id, $data['keterangan'], $postLines);

            $jurnalManual->update(['no_trans_jurnal' => $noTrans]);
        }, attempts: 3);

        return redirect()->route('keuangan.jurnal-manual')->with('status', 'Jurnal penyesuaian berhasil diposting.');
    }

    public function batalkan(JurnalManual $jurnalManual): RedirectResponse
    {
        DB::transaction(function () use ($jurnalManual) {
            $jurnalManual = JurnalManual::query()->lockForUpdate()->findOrFail($jurnalManual->id);

            abort_if($jurnalManual->status === 'dibatalkan', 422, 'Jurnal ini sudah dibatalkan.');
            abort_if(
                PeriodeTutupBuku::isClosed($jurnalManual->tanggal->format('Y-m-d')),
                422,
                'Periode jurnal ini sudah ditutup (closing) — tidak bisa dibatalkan.'
            );

            $this->accounting->reverse($jurnalManual->no_trans_jurnal, 'Pembatalan jurnal manual #'.$jurnalManual->id);

            $jurnalManual->update([
                'status' => 'dibatalkan',
                'dibatalkan_oleh' => auth()->id(),
                'dibatalkan_at' => now(),
            ]);
        }, attempts: 3);

        return redirect()->route('keuangan.jurnal-manual')->with('status', 'Jurnal penyesuaian dibatalkan.');
    }
}
