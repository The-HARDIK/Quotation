/**
 * Quotation Studio - Live Financial Calculation Engine (Vanilla JavaScript)
 * Strictly mirrors the backend PHP CalculationEngine.
 */

const CalculationEngine = {
    calculate(items, options = {}) {
        const taxType = (options.tax_type || 'GST').toUpperCase();
        const taxInclusive = Boolean(options.tax_inclusive);
        const defaultGstRate = parseFloat(options.gst_rate !== undefined ? options.gst_rate : 18.0);
        const overallDiscountType = options.overall_discount_type || 'fixed';
        const overallDiscountVal = parseFloat(options.overall_discount || 0);
        const additionalCharges = parseFloat(options.additional_charges || 0);
        const advanceAmount = parseFloat(options.advance_amount || 0);
        const enableRoundOff = options.enable_round_off !== undefined ? Boolean(options.enable_round_off) : true;

        let subtotal = 0;
        let totalItemDiscounts = 0;
        let totalItemTax = 0;

        const processedItems = items.map((item, idx) => {
            const qty = parseFloat(item.quantity) || 0;
            const unitPrice = parseFloat(item.unit_price) || 0;
            const discountPct = parseFloat(item.discount_percent) || 0;
            const discountAmt = parseFloat(item.discount_amount) || 0;
            const itemTaxRate = (item.tax_rate !== undefined && item.tax_rate !== '') ? parseFloat(item.tax_rate) : defaultGstRate;

            const grossAmount = qty * unitPrice;
            let calcDiscount = 0;

            if (discountPct > 0) {
                calcDiscount = (grossAmount * discountPct) / 100;
            } else if (discountAmt > 0) {
                calcDiscount = discountAmt;
            }

            if (calcDiscount > grossAmount) {
                calcDiscount = grossAmount;
            }

            const afterDiscount = grossAmount - calcDiscount;
            let lineTaxable = afterDiscount;
            let lineTax = 0;
            let lineTotal = 0;

            if (taxInclusive && itemTaxRate > 0) {
                lineTaxable = afterDiscount / (1 + (itemTaxRate / 100));
                lineTax = afterDiscount - lineTaxable;
                lineTotal = afterDiscount;
            } else {
                lineTaxable = afterDiscount;
                lineTax = (lineTaxable * itemTaxRate) / 100;
                lineTotal = lineTaxable + lineTax;
            }

            subtotal += grossAmount;
            totalItemDiscounts += calcDiscount;
            totalItemTax += lineTax;

            return {
                ...item,
                index: idx + 1,
                quantity: qty,
                unit_price: unitPrice,
                gross_amount: Number(grossAmount.toFixed(2)),
                discount_amount: Number(calcDiscount.toFixed(2)),
                taxable_amount: Number(lineTaxable.toFixed(2)),
                tax_rate: itemTaxRate,
                tax_amount: Number(lineTax.toFixed(2)),
                line_total: Number(lineTotal.toFixed(2))
            };
        });

        const baseAfterItemDiscounts = subtotal - totalItemDiscounts;
        let overallDiscountAmount = 0;
        if (overallDiscountType === 'percent' && overallDiscountVal > 0) {
            overallDiscountAmount = (baseAfterItemDiscounts * overallDiscountVal) / 100;
        } else {
            overallDiscountAmount = overallDiscountVal;
        }

        if (overallDiscountAmount > baseAfterItemDiscounts) {
            overallDiscountAmount = baseAfterItemDiscounts;
        }

        const totalDiscount = totalItemDiscounts + overallDiscountAmount;
        const taxableAmount = Math.max(0, subtotal - totalDiscount + additionalCharges);

        let cgstRate = 0;
        let sgstRate = 0;
        let igstRate = 0;
        let cgstAmount = 0;
        let sgstAmount = 0;
        let igstAmount = 0;
        let totalTax = 0;
        let unroundedGrandTotal = 0;

        if (taxInclusive) {
            cgstRate = (taxType === 'GST') ? (defaultGstRate / 2) : 0;
            sgstRate = (taxType === 'GST') ? (defaultGstRate / 2) : 0;
            igstRate = (taxType === 'IGST') ? defaultGstRate : 0;

            cgstAmount = (taxType === 'GST') ? Number((totalItemTax / 2).toFixed(2)) : 0;
            sgstAmount = (taxType === 'GST') ? Number((totalItemTax / 2).toFixed(2)) : 0;
            igstAmount = (taxType === 'IGST') ? Number(totalItemTax.toFixed(2)) : 0;
            totalTax = totalItemTax;
            unroundedGrandTotal = taxableAmount;
        } else {
            cgstRate = (taxType === 'GST') ? (defaultGstRate / 2) : 0;
            sgstRate = (taxType === 'GST') ? (defaultGstRate / 2) : 0;
            igstRate = (taxType === 'IGST') ? defaultGstRate : 0;

            cgstAmount = (taxType === 'GST') ? Number(((taxableAmount * cgstRate) / 100).toFixed(2)) : 0;
            sgstAmount = (taxType === 'GST') ? Number(((taxableAmount * sgstRate) / 100).toFixed(2)) : 0;
            igstAmount = (taxType === 'IGST') ? Number(((taxableAmount * igstRate) / 100).toFixed(2)) : 0;
            totalTax = cgstAmount + sgstAmount + igstAmount;
            unroundedGrandTotal = taxableAmount + totalTax;
        }

        let grandTotal = unroundedGrandTotal;
        let roundOff = 0;

        if (enableRoundOff) {
            grandTotal = Math.round(unroundedGrandTotal);
            roundOff = Number((grandTotal - unroundedGrandTotal).toFixed(2));
        } else {
            grandTotal = Number(unroundedGrandTotal.toFixed(2));
            roundOff = 0;
        }

        const balanceAmount = Math.max(0, grandTotal - advanceAmount);

        return {
            items: processedItems,
            subtotal: Number(subtotal.toFixed(2)),
            total_item_discounts: Number(totalItemDiscounts.toFixed(2)),
            overall_discount_amount: Number(overallDiscountAmount.toFixed(2)),
            total_discount: Number(totalDiscount.toFixed(2)),
            additional_charges: Number(additionalCharges.toFixed(2)),
            taxable_amount: Number(taxableAmount.toFixed(2)),
            tax_type: taxType,
            tax_inclusive: taxInclusive,
            gst_rate: defaultGstRate,
            cgst_rate: cgstRate,
            sgst_rate: sgstRate,
            igst_rate: igstRate,
            cgst_amount: cgstAmount,
            sgst_amount: sgstAmount,
            igst_amount: igstAmount,
            total_tax: Number(totalTax.toFixed(2)),
            unrounded_total: Number(unroundedGrandTotal.toFixed(2)),
            round_off: roundOff,
            grand_total: grandTotal,
            advance_amount: Number(advanceAmount.toFixed(2)),
            balance_amount: Number(balanceAmount.toFixed(2)),
            amount_in_words: this.numberToWords(grandTotal)
        };
    },

    numberToWords(num) {
        if (!num || isNaN(num)) return 'Zero Rupees Only';
        const n = Math.floor(Math.abs(num));
        const decimal = Math.round((Math.abs(num) - n) * 100);

        const a = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
        ];
        const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        function inWords(val) {
            if (val === 0) return '';
            if (val < 20) return a[val] + ' ';
            if (val < 100) return b[Math.floor(val / 10)] + (val % 10 !== 0 ? ' ' + a[val % 10] : '') + ' ';
            if (val < 1000) return a[Math.floor(val / 100)] + ' Hundred ' + inWords(val % 100);
            return '';
        }

        let str = '';
        const crore = Math.floor(n / 10000000);
        let rem = n % 10000000;
        const lakh = Math.floor(rem / 100000);
        rem = rem % 100000;
        const thousand = Math.floor(rem / 1000);
        const hundreds = rem % 1000;

        if (crore > 0) str += inWords(crore) + 'Crore ';
        if (lakh > 0) str += inWords(lakh) + 'Lakh ';
        if (thousand > 0) str += inWords(thousand) + 'Thousand ';
        if (hundreds > 0) str += inWords(hundreds);

        str = str.trim();
        const rupees = str ? str + ' Rupees' : 'Zero Rupees';
        let paise = '';
        if (decimal > 0) {
            paise = ' and ' + inWords(decimal).trim() + ' Paise';
        }

        return rupees + paise + ' Only';
    },

    formatCurrency(amount, currency = '₹') {
        const val = parseFloat(amount) || 0;
        return currency + ' ' + val.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
};

if (typeof module !== 'undefined' && module.exports) {
    module.exports = CalculationEngine;
}
