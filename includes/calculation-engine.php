<?php
/**
 * Quotation Studio - Centralized Calculation Engine
 * Single Source of Truth for Quotations, Invoices, Previews, PDFs, and Reports.
 */

class CalculationEngine {
    /**
     * Compute comprehensive financial calculations for a quotation or invoice.
     *
     * @param array $items Array of line items:
     *                     [['quantity' => 2, 'unit_price' => 1000, 'discount_percent' => 5, 'discount_amount' => 0, 'tax_rate' => 18]]
     * @param array $options Configuration options:
     *                       [
     *                          'tax_type' => 'GST'|'IGST',
     *                          'tax_inclusive' => false,
     *                          'gst_rate' => 18.0,
     *                          'overall_discount_type' => 'fixed'|'percent',
     *                          'overall_discount' => 0.0,
     *                          'additional_charges' => 0.0,
     *                          'advance_amount' => 0.0,
     *                          'enable_round_off' => true
     *                       ]
     * @return array Calculated summary and enriched line items.
     */
    public static function calculate(array $items, array $options = []): array {
        // If passed a single config array containing 'items'
        if (isset($items['items']) && is_array($items['items']) && empty($options)) {
            $options = $items;
            $items = $items['items'];
        }

        $taxType = strtoupper($options['tax_type'] ?? 'GST'); // GST (CGST+SGST) or IGST
        if (!empty($options['is_interstate'])) {
            $taxType = 'IGST';
        }
        $taxInclusive = !empty($options['tax_inclusive']);
        $defaultGstRate = (float)($options['gst_rate'] ?? $options['tax_rate'] ?? 18.0);
        $overallDiscountType = $options['overall_discount_type'] ?? 'fixed';
        $overallDiscountVal = (float)($options['overall_discount'] ?? 0.0);
        $additionalCharges = (float)($options['additional_charges'] ?? 0.0);
        $advanceAmount = (float)($options['advance_amount'] ?? 0.0);
        $enableRoundOff = array_key_exists('round_off', $options) ? (bool)$options['round_off'] : (array_key_exists('enable_round_off', $options) ? (bool)$options['enable_round_off'] : true);

        $processedItems = [];
        $subtotal = 0.0;
        $totalItemDiscounts = 0.0;
        $totalItemTax = 0.0;
        $counter = 0;

        foreach ($items as $idx => $item) {
            $counter++;
            $qty = (float)($item['quantity'] ?? 1);
            $unitPrice = (float)($item['unit_price'] ?? 0);
            
            // Check flexible discount keys
            $discountPct = (float)($item['discount_percent'] ?? 0);
            $discountAmt = (float)($item['discount_amount'] ?? 0);
            if (isset($item['discount_type']) && isset($item['discount_rate'])) {
                if ($item['discount_type'] === 'percentage' || $item['discount_type'] === 'percent') {
                    $discountPct = (float)$item['discount_rate'];
                } else {
                    $discountAmt = (float)$item['discount_rate'];
                }
            }

            $itemTaxRate = isset($item['tax_rate']) && $item['tax_rate'] !== '' ? (float)$item['tax_rate'] : $defaultGstRate;

            $grossAmount = $qty * $unitPrice;

            // Compute item discount
            if ($discountPct > 0) {
                $calcDiscount = ($grossAmount * $discountPct) / 100.0;
            } elseif ($discountAmt > 0) {
                $calcDiscount = $discountAmt;
            } else {
                $calcDiscount = 0.0;
            }

            if ($calcDiscount > $grossAmount) {
                $calcDiscount = $grossAmount;
            }

            $afterDiscount = $grossAmount - $calcDiscount;

            // Handle Tax Inclusive vs Exclusive
            if ($taxInclusive && $itemTaxRate > 0) {
                // If price includes tax: Taxable = Gross / (1 + Rate/100)
                $lineTaxable = $afterDiscount / (1.0 + ($itemTaxRate / 100.0));
                $lineTax = $afterDiscount - $lineTaxable;
                $lineTotal = $afterDiscount; // Inclusive total
            } else {
                $lineTaxable = $afterDiscount;
                $lineTax = ($lineTaxable * $itemTaxRate) / 100.0;
                $lineTotal = $lineTaxable + $lineTax;
            }

            $subtotal += $grossAmount;
            $totalItemDiscounts += $calcDiscount;
            $totalItemTax += $lineTax;

            $processedItems[] = array_merge($item, [
                'index' => $counter,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'gross_amount' => round($grossAmount, 2),
                'discount_amount' => round($calcDiscount, 2),
                'taxable_amount' => round($lineTaxable, 2),
                'tax_rate' => $itemTaxRate,
                'tax_amount' => round($lineTax, 2),
                'line_total' => round($lineTotal, 2)
            ]);
        }

        // Overall discount computation
        $baseAfterItemDiscounts = $subtotal - $totalItemDiscounts;
        if ($overallDiscountType === 'percent' && $overallDiscountVal > 0) {
            $overallDiscountAmount = ($baseAfterItemDiscounts * $overallDiscountVal) / 100.0;
        } else {
            $overallDiscountAmount = $overallDiscountVal;
        }

        if ($overallDiscountAmount > $baseAfterItemDiscounts) {
            $overallDiscountAmount = $baseAfterItemDiscounts;
        }

        $totalDiscount = $totalItemDiscounts + $overallDiscountAmount;
        $taxableAmount = max(0.0, $subtotal - $totalDiscount + $additionalCharges);

        // Tax calculation on Taxable Amount
        if ($taxInclusive) {
            // Already factored into items
            $cgstRate = ($taxType === 'GST') ? ($defaultGstRate / 2.0) : 0.0;
            $sgstRate = ($taxType === 'GST') ? ($defaultGstRate / 2.0) : 0.0;
            $igstRate = ($taxType === 'IGST') ? $defaultGstRate : 0.0;

            $cgstAmount = ($taxType === 'GST') ? round($totalItemTax / 2.0, 2) : 0.0;
            $sgstAmount = ($taxType === 'GST') ? round($totalItemTax / 2.0, 2) : 0.0;
            $igstAmount = ($taxType === 'IGST') ? round($totalItemTax, 2) : 0.0;
            $totalTax = $totalItemTax;
            $unroundedGrandTotal = $taxableAmount; // Since inclusive
        } else {
            $cgstRate = ($taxType === 'GST') ? ($defaultGstRate / 2.0) : 0.0;
            $sgstRate = ($taxType === 'GST') ? ($defaultGstRate / 2.0) : 0.0;
            $igstRate = ($taxType === 'IGST') ? $defaultGstRate : 0.0;

            $cgstAmount = ($taxType === 'GST') ? round(($taxableAmount * $cgstRate) / 100.0, 2) : 0.0;
            $sgstAmount = ($taxType === 'GST') ? round(($taxableAmount * $sgstRate) / 100.0, 2) : 0.0;
            $igstAmount = ($taxType === 'IGST') ? round(($taxableAmount * $igstRate) / 100.0, 2) : 0.0;
            $totalTax = $cgstAmount + $sgstAmount + $igstAmount;
            $unroundedGrandTotal = $taxableAmount + $totalTax;
        }

        // Round Off calculation
        if ($enableRoundOff) {
            $grandTotal = round($unroundedGrandTotal);
            $roundOff = round($grandTotal - $unroundedGrandTotal, 2);
        } else {
            $grandTotal = round($unroundedGrandTotal, 2);
            $roundOff = 0.0;
        }

        // Balance Due
        $balanceAmount = max(0.0, $grandTotal - $advanceAmount);

        return [
            'items' => $processedItems,
            'subtotal' => round($subtotal, 2),
            'total_item_discounts' => round($totalItemDiscounts, 2),
            'overall_discount_amount' => round($overallDiscountAmount, 2),
            'total_discount' => round($totalDiscount, 2),
            'additional_charges' => round($additionalCharges, 2),
            'taxable_amount' => round($taxableAmount, 2),
            'tax_type' => $taxType,
            'tax_inclusive' => $taxInclusive,
            'gst_rate' => $defaultGstRate,
            'cgst_rate' => $cgstRate,
            'sgst_rate' => $sgstRate,
            'igst_rate' => $igstRate,
            'cgst_amount' => $cgstAmount,
            'sgst_amount' => $sgstAmount,
            'igst_amount' => $igstAmount,
            'total_tax' => round($totalTax, 2),
            'unrounded_total' => round($unroundedGrandTotal, 2),
            'round_off' => $roundOff,
            'grand_total' => $grandTotal,
            'advance_amount' => round($advanceAmount, 2),
            'balance_amount' => round($balanceAmount, 2),
            'amount_in_words' => self::numberToWords($grandTotal)
        ];
    }

    /**
     * Convert currency number into Indian format words (Rupees and Paise).
     */
    public static function numberToWords(float $number): string {
        $no = floor($number);
        $decimal = round(($number - $no) * 100);
        $digits_length = strlen($no);
        $i = 0;
        $str = [];
        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
            30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
            80 => 'Eighty', 90 => 'Ninety'
        ];
        $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number_part = $no % $divider;
            $no = floor($no / $divider);
            $i += ($divider == 10) ? 1 : 2;

            if ($number_part) {
                $plural = (($counter = count($str)) && $number_part > 9) ? '' : '';
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : '';
                if ($number_part < 21) {
                    $unitWord = $words[$number_part];
                } else {
                    $unitWord = $words[10 * floor($number_part / 10)] . ' ' . $words[$number_part % 10];
                }
                $digitWord = isset($digits[$counter]) ? $digits[$counter] : '';
                $str[] = trim($unitWord . ' ' . $digitWord . ' ' . $hundred);
            } else {
                $str[] = null;
            }
        }

        $rupees = implode(' ', array_filter(array_reverse($str)));
        $rupees = trim($rupees) ? $rupees . ' Rupees' : 'Zero Rupees';

        $paise = '';
        if ($decimal > 0) {
            if ($decimal < 21) {
                $pWord = $words[$decimal];
            } else {
                $pWord = $words[10 * floor($decimal / 10)] . ' ' . $words[$decimal % 10];
            }
            $paise = ' and ' . trim($pWord) . ' Paise';
        }

        return $rupees . $paise . ' Only';
    }
}
