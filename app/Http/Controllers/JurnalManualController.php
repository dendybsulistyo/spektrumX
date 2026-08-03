<?php

namespace App\Http\Controllers;

use App\Models\Akun;
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

        $entries = JurnalManual::with(['user', 'dibatalkanOleh'])
            ->whereBetween('tanggal', [$from, $to])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->map(function (JurnalManual $jm) {
                $lines = $jm->jurnalLines();
                $akunNames = Akun::whereIn('NoAkun', $lines->pluck('NoAkun'))->pluck('NmAkun', 'NoAkun');

                return [
                    'model' => $jm,
                    'total' => $lines->sum('Debet'),
                    'lines' => $lines->map(fn ($l) => [
                        'akun' => $l->NoAkun,
                        'nama' => $akunNames->get($l->NoAkun, $l->NoAkun),
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
            $jurnalManual = JurnalManual::create([
                'tanggal' => $data['tanggal'],
                'keterangan' => $data['keterangan'],
                'status' => 'posted',
                'user_id' => auth()->id(),
            ]);

            $noTrans = $this->accounting->post($data['tanggal'], 'JM-'.$jurnalManual->id, $data['keterangan'], $postLines);

            $jurnalManual->update(['no_trans_jurnal' => $noTrans]);
        });

        return redirect()->route('keuangan.jurnal-manual')->with('status', 'Jurnal penyesuaian berhasil diposting.');
    }

    public function batalkan(JurnalManual $jurnalManual): RedirectResponse
    {
        if ($jurnalManual->status === 'dibatalkan') {
            return back()->with('error', 'Jurnal ini sudah dibatalkan.');
        }

        if (PeriodeTutupBuku::isClosed($jurnalManual->tanggal->format('Y-m-d'))) {
            return back()->with('error', 'Periode jurnal ini sudah ditutup (closing) — tidak bisa dibatalkan.');
        }

        $this->accounting->reverse($jurnalManual->no_trans_jurnal, 'Pembatalan jurnal manual #'.$jurnalManual->id);

        $jurnalManual->update([
            'status' => 'dibatalkan',
            'dibatalkan_oleh' => auth()->id(),
            'dibatalkan_at' => now(),
        ]);

        return redirect()->route('keuangan.jurnal-manual')->with('status', 'Jurnal penyesuaian dibatalkan.');
    }
}
