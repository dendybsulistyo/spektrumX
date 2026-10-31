{{-- Alur cetak bersama SO / DO / Invoice (lihat App\Services\ReprintGate):
     cetak pertama langsung; cetak ulang minta alasan; lewat batas waktu → tawarkan
     permintaan approval. Tanda "tgl jam · petugas-X" diisi ke .reprint-stamp. --}}
<script>
    window.reprintPrint = async function ({ registerUrl, isReprint, request }) {
        let reason = null;
        if (isReprint) {
            reason = window.askReprintReason();
            if (reason === null) return false;
        }
        const response = await fetch(registerUrl, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
            body: JSON.stringify({ reason }),
        });
        const payload = await response.json().catch(() => ({}));
        if (response.ok) {
            document.querySelectorAll('.reprint-stamp').forEach((el) => { el.textContent = payload.stamp || ''; });
            return true;
        }
        if (payload.code === 'reason_required' && ! isReprint) {
            // Halaman lama (belum dimuat ulang) mengira cetak pertama: ulangi dengan alasan.
            return window.reprintPrint({ registerUrl, isReprint: true, request });
        }
        if (payload.code === 'needs_approval' && request) {
            if (confirm(payload.message + '\n\nKirim permintaan cetak ulang sekarang?')) {
                const ask = await fetch(@json(route('reprint-requests.store')), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                    body: JSON.stringify({ ...request, reason }),
                });
                const result = await ask.json().catch(() => ({}));
                alert(result.message || 'Permintaan cetak ulang gagal dikirim.');
            }
            return false;
        }
        alert(payload.message || 'Dokumen tidak dapat dicetak.');
        return false;
    };

    window.askReprintReason = function () {
        const reasons = @json(\App\Services\ReprintGate::REASONS);
        const answer = prompt('Alasan cetak ulang:\n' + reasons.map((r, i) => `${i + 1}. ${r}`).join('\n')
            + '\n\nKetik nomor (1-' + reasons.length + ') atau tulis alasan lain:');
        if (answer === null) return null;
        const text = answer.trim();
        if (text === '') { alert('Alasan cetak ulang wajib diisi.'); return null; }
        return reasons[Number(text) - 1] ?? text;
    };
</script>
