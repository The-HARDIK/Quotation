/**
 * Quotation Studio - Invoice Management JavaScript Helpers
 */

const Invoices = {
    /**
     * Compute invoice line totals and summary
     */
    recalculate() {
        const rows = document.querySelectorAll('.invoice-item-row');
        let subtotal = 0;

        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty')?.value || '1');
            const rate = parseFloat(row.querySelector('.item-rate')?.value || '0');
            const total = qty * rate;
            subtotal += total;
            const lineTotalEl = row.querySelector('.item-total');
            if (lineTotalEl) {
                lineTotalEl.textContent = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
            }
        });

        const taxRate = parseFloat(document.getElementById('invoiceTaxRate')?.value || '18');
        const taxTotal = (subtotal * taxRate) / 100;
        const grandTotal = subtotal + taxTotal;

        const subEl = document.getElementById('invSubtotalDisplay');
        const taxEl = document.getElementById('invTaxDisplay');
        const grandEl = document.getElementById('invGrandDisplay');

        if (subEl) subEl.textContent = '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
        if (taxEl) taxEl.textContent = '₹' + taxTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
        if (grandEl) grandEl.textContent = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
    },

    /**
     * Record a quick payment via modal or form
     */
    async recordPayment(invoiceId, paymentData) {
        try {
            const res = await fetch(`${window.BASE_URL || ''}/api/payment.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ invoice_id: invoiceId, ...paymentData })
            });
            return await res.json();
        } catch (err) {
            console.error('Record payment error:', err);
            return { success: false, message: err.message };
        }
    }
};

window.Invoices = Invoices;
