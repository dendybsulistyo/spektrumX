<?php

namespace App\Http\Controllers;

use App\Models\OrderPayment;
use App\Models\Pengeluaran;
use App\Models\PeriodeTutupBuku;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TutupBukuController extends Controller
{
    public function index(): View
    {
        $closed = PeriodeTutupBuku::with('ditutupOleh')->orderByDesc('periode')->get();

        $periodeUsulan = now()->subMonthNoOverflow()->format('Y-m');
        if ($closed->contains('periode', $periodeUsulan)) {
            $periodeUsulan = now()->format('Y-m');
        }

        return view('keuangan.tutup-buku', [
            'closed' => $closed,
            'periodeUsulan' => $periodeUsulan,
            'previewPeriode' => null,
            'previewKasMasuk' => null,
            'previewPengeluaran' => null,
        ]);
    }

    /**
     * Small sanity-check preview (total kas masuk vs pengeluaran for the
     * period) shown before the admin commits to closing it — an AJAX-free
     * GET so it can be requested inline from the close form.
     */
    public function preview(Request $request): View
    {
        $periode = $request->validate(['periode' => ['required', 'date_format:Y-m']])['periode'];

        $closed = PeriodeTutupBuku::with('ditutupOleh')->orderByDesc('periode')->get();

        return view('keuangan.tutup-buku', [
            'closed' => $closed,
            'periodeUsulan' => $periode,
            'previewPeriode' => $periode,
            'previewKasMasuk' => OrderPayment::query()->where('created_at', 'like', "{$periode}%")->sum('jumlah'),
            'previewPengeluaran' => Pengeluaran::query()->where('tanggal', 'like', "{$periode}%")->sum('jumlah'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['periode'] > now()->format('Y-m')) {
            return back()->with('error', 'Tidak bisa menutup periode yang belum terjadi.');
        }

        if (PeriodeTutupBuku::isClosed($data['periode'])) {
            return back()->with('error', 'Periode ini sudah ditutup sebelumnya.');
        }

        PeriodeTutupBuku::create([
            'periode' => $data['periode'],
            'ditutup_oleh' => auth()->id(),
            'ditutup_at' => now(),
            'catatan' => $data['catatan'] ?? null,
        ]);

        return redirect()->route('keuangan.tutup-buku')
            ->with('status', "Periode {$data['periode']} berhasil ditutup. Pengeluaran & payroll periode ini terkunci.");
    }

    public function destroy(PeriodeTutupBuku $periodeTutupBuku): RedirectResponse
    {
        $periode = $periodeTutupBuku->periode;
        $periodeTutupBuku->delete();

        return redirect()->route('keuangan.tutup-buku')
            ->with('status', "Periode {$periode} dibuka kembali.");
    }
}
