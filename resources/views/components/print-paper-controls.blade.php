<div class="field" style="width:92px;">
    <label>Kertas</label>
    <select class="input" data-print-paper style="height:36px; padding-left:10px; padding-right:28px;">
        <option value="A4">A4</option>
        <option value="A5">A5</option>
    </select>
</div>
<button type="button" class="btn btn-secondary" style="height:36px;" onclick="window.printFinancialReport(this)">Cetak</button>

@once
    <script>
        window.printFinancialReport = function (button) {
            const selector = button.closest('form').querySelector('[data-print-paper]');
            const paper = selector.value === 'A5' ? 'A5' : 'A4';
            const margin = paper === 'A5' ? '10mm 7mm' : '12mm 9mm';
            let pageStyle = document.getElementById('financial-print-page-size');

            if (!pageStyle) {
                pageStyle = document.createElement('style');
                pageStyle.id = 'financial-print-page-size';
                document.head.appendChild(pageStyle);
            }

            pageStyle.textContent = `
                @page {
                    size: ${paper} portrait;
                    margin: ${margin};

                    @bottom-right {
                        content: "Halaman " counter(page) " dari " counter(pages);
                        color: #64748b;
                        font-family: Arial, sans-serif;
                        font-size: 8pt;
                    }
                }
            `;
            document.body.classList.toggle('print-paper-a5', paper === 'A5');
            try {
                localStorage.setItem('spektrum-print-paper', paper);
            } catch (error) {
                // Cetak tetap berjalan ketika penyimpanan browser dinonaktifkan.
            }
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    window.print();
                });
            });
        };

        document.querySelectorAll('[data-print-paper]').forEach(function (selector) {
            let savedPaper = 'A4';
            try {
                savedPaper = localStorage.getItem('spektrum-print-paper') || 'A4';
            } catch (error) {
                // Gunakan A4 sebagai pilihan awal.
            }
            selector.value = savedPaper === 'A5' ? 'A5' : 'A4';
        });
    </script>
@endonce
