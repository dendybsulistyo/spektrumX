import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const formatRupiah = (input) => {
    const nominal = input.value.replace(/\D/g, '');
    input.value = nominal ? new Intl.NumberFormat('id-ID').format(nominal) : '';
};

// Fields marked data-rupiah show thousands separators while the user types.
// Immediately before submit we restore the raw digits, so request validation
// and the values persisted in the database remain unchanged.
const initializeRupiahInputs = () => {
    document.querySelectorAll('input[data-rupiah]').forEach((input) => {
        if (input.dataset.rupiahReady) return;

        input.dataset.rupiahReady = 'true';
        input.type = 'text';
        input.inputMode = 'numeric';
        input.pattern = '[0-9.]*';
        formatRupiah(input);
        input.addEventListener('input', () => formatRupiah(input));

        input.form?.addEventListener('submit', () => {
            input.value = input.value.replace(/\D/g, '');
        });
    });
};

// Some accounting forms accept decimal values, written in the Indonesian
// style (for example 1.250,5). Keep that familiar display while submitting
// the database-friendly decimal form (1250.5).
const formatRupiahDecimal = (input) => {
    const cleaned = input.value.replace(/[^\d,]/g, '');
    const [whole, ...decimal] = cleaned.split(',');
    const formattedWhole = whole ? new Intl.NumberFormat('id-ID').format(whole) : '';
    input.value = decimal.length ? `${formattedWhole},${decimal.join('').slice(0, 2)}` : formattedWhole;
};

const initializeRupiahDecimalInputs = () => {
    document.querySelectorAll('input[data-rupiah-decimal]').forEach((input) => {
        if (input.dataset.rupiahDecimalReady) return;

        input.dataset.rupiahDecimalReady = 'true';
        input.type = 'text';
        input.inputMode = 'decimal';
        formatRupiahDecimal(input);
        input.addEventListener('input', () => formatRupiahDecimal(input));
        input.form?.addEventListener('submit', () => {
            input.value = input.value.replace(/\./g, '').replace(',', '.');
        });
    });
};

const observeRupiahInputs = () => {
    new MutationObserver(() => {
        initializeRupiahInputs();
        initializeRupiahDecimalInputs();
    }).observe(document.body, { childList: true, subtree: true });
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
        initializeRupiahInputs();
        initializeRupiahDecimalInputs();
        observeRupiahInputs();
        normalizePurchaseReportQuantities();
    });
} else {
    initializePurchasePaymentModals();
    initializeRupiahInputs();
    initializeRupiahDecimalInputs();
    observeRupiahInputs();
    normalizePurchaseReportQuantities();
}
