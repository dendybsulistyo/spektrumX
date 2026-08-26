import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const formatRupiah = (input) => {
    const nominal = input.value.replace(/\D/g, '');
    input.value = nominal ? new Intl.NumberFormat('id-ID').format(nominal) : '';
};

const initializePurchasePaymentModals = () => {
    document.querySelectorAll('details').forEach((details) => {
        const form = details.querySelector('form[action*="/pelunasan"]');
        const summary = details.querySelector(':scope > summary');
        if (!form || !summary || form.dataset.paymentModalReady) return;

        form.dataset.paymentModalReady = 'true';
        const paymentInput = form.querySelector('input[name="jumlah"]');
        if (paymentInput) {
            paymentInput.type = 'text';
            paymentInput.inputMode = 'numeric';
            paymentInput.pattern = '[0-9]*';
            paymentInput.addEventListener('input', () => formatRupiah(paymentInput));
            form.addEventListener('submit', () => {
                paymentInput.value = paymentInput.value.replace(/\D/g, '');
            });
        }

        const modal = document.createElement('dialog');
        modal.className = 'pelunasan-modal';
        form.className = 'grid gap-2 p-5';
        modal.appendChild(form);
        document.body.appendChild(modal);

        summary.addEventListener('click', (event) => {
            event.preventDefault();
            modal.showModal();
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) modal.close();
        });
    });
};

const normalizePurchaseReportQuantities = () => {
    document.querySelectorAll('table').forEach((table) => {
        const headers = [...table.querySelectorAll('thead th')].map((header) => header.textContent.trim());
        if (headers[5] !== 'QTY' || headers[7] !== 'TOTAL SEBELUM PAJAK') return;

        table.querySelectorAll('tbody tr').forEach((row) => {
            const dateCell = row.cells[0];
            if (dateCell) {
                dateCell.style.fontSize = '.7rem';
                dateCell.style.whiteSpace = 'nowrap';
            }

            const quantityCell = row.cells[5];
            if (!quantityCell) return;

            const [quantity, ...unit] = quantityCell.textContent.trim().split(/\s+/);
            const numericQuantity = Number(quantity.replace(/\./g, '').replace(',', '.'));
            if (!Number.isFinite(numericQuantity)) return;

            const formattedQuantity = new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 3,
            }).format(numericQuantity);
            quantityCell.textContent = [formattedQuantity, ...unit].join(' ');
        });
    });
};

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    const openModal = document.querySelector('dialog.pelunasan-modal[open]');
    if (!openModal) return;

    event.preventDefault();
    openModal.close();
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializePurchasePaymentModals();
        normalizePurchaseReportQuantities();
    });
} else {
    initializePurchasePaymentModals();
    normalizePurchaseReportQuantities();
}
