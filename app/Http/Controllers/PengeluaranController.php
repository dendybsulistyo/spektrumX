<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use App\Models\PeriodeTutupBuku;
use App\Services\AccountingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengeluaranController extends Controller
{
    public function __construct(private readonly AccountingService $accounting) {}

    public function index(Request $request): View
    {
        $from = $request->filled('from') ? $request->string('from')->toString() : now()->startOfMonth()->format('Y-m-d');
        $to = $request->filled('to') ? $request->string('to')->toString() : now()->format('Y-m-d');
        $kategori = $request->filled('kategori') ? $request->string('kategori')->toString() : null;

        $pengeluaran = Pengeluaran::query()
            ->with('user')
            ->whereBetween('tanggal', [$from, $to])
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();

        $totalPerKategori = $pengeluaran->groupBy('kategori')
            ->map(fn ($rows) => $rows->sum('jumlah'));

        return view('pengeluaran.index', [
            'pengeluaran' => $pengeluaran,
            'from' => $from,
            'to' => $to,
            'kategori' => $kategori,
            'total' => $pengeluaran->sum('jumlah'),
            'totalPerKategori' => $totalPerKategori,
            'kategoriOptions' => Pengeluaran::KATEGORI_LABELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if (PeriodeTutupBuku::isClosed($data['tanggal'])) {
            return back()->withInput()->with('error', 'Periode tanggal ini sudah ditutup (closing) — tidak bisa mencatat pengeluaran baru.');
        }

        $pengeluaran = Pengeluaran::create($data + ['user_id' => auth()->id()]);
        $pengeluaran->update(['no_trans_jurnal' => $this->postJurnal($pengeluaran)]);

        return redirect()->route('pengeluaran.index')->with('status', 'Pengeluaran berhasil dicatat.');
    }

    public function update(Request $request, Pengeluaran $pengeluaran): RedirectResponse
    {
        $data = $this->validated($request);

        if (PeriodeTutupBuku::isClosed($pengeluaran->tanggal->format('Y-m-d')) || PeriodeTutupBuku::isClosed($data['tanggal'])) {
            return back()->withInput()->with('error', 'Periode pengeluaran ini sudah ditutup (closing) — tidak bisa diubah.');
        }

        // Journal entries are append-only — editing means reversing the old
        // posting and posting a fresh one for the updated values, rather
        // than mutating history in place.
        $this->accounting->reverse($pengeluaran->no_trans_jurnal, 'Koreksi pengeluaran #'.$pengeluaran->id);

        $pengeluaran->update($data);
        $pengeluaran->update(['no_trans_jurnal' => $this->postJurnal($pengeluaran)]);

        return redirect()->route('pengeluaran.index')->with('status', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Pengeluaran $pengeluaran): RedirectResponse
    {
        if (PeriodeTutupBuku::isClosed($pengeluaran->tanggal->format('Y-m-d'))) {
            return back()->with('error', 'Periode pengeluaran ini sudah ditutup (closing) — tidak bisa dihapus.');
        }

        $this->accounting->reverse($pengeluaran->no_trans_jurnal, 'Hapus pengeluaran #'.$pengeluaran->id);

        $pengeluaran->delete();

        return redirect()->route('pengeluaran.index')->with('status', 'Pengeluaran dihapus.');
    }

    private function postJurnal(Pengeluaran $pengeluaran): string
    {
        $akunBiaya = AccountingService::AKUN_PENGELUARAN_KATEGORI[$pengeluaran->kategori] ?? '60099';

        return $this->accounting->post(
            $pengeluaran->tanggal->format('Y-m-d'),
            'PGL-'.$pengeluaran->id,
            $pengeluaran->keterangan,
            [
                ['akun' => $akunBiaya, 'debet' => (float) $pengeluaran->jumlah],
                ['akun' => AccountingService::akunKasFor($pengeluaran->cara_bayar), 'kredit' => (float) $pengeluaran->jumlah],
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'tanggal' => ['required', 'date'],
            'kategori' => ['required', 'in:'.implode(',', array_keys(Pengeluaran::KATEGORI_LABELS))],
            'keterangan' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'cara_bayar' => ['required', 'in:tunai,qris,transfer'],
            'no_referensi' => ['required_if:cara_bayar,qris,transfer', 'nullable', 'string', 'max:50'],
        ]);
    }
}
