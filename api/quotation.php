<?php
/**
 * Quotation Studio - Quotation Save & Auto-Save API
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/calculation-engine.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$biz = current_business();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    json_response(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
}

$id = !empty($data['id']) ? (int)$data['id'] : null;
$quoteNumber = trim($data['quotation_number'] ?? '');
if (empty($quoteNumber)) {
    $quoteNumber = generate_quotation_number($biz);
}

$refNumber = trim($data['ref_number'] ?? '');
$date = !empty($data['date']) ? $data['date'] : date('Y-m-d');
$validUntil = !empty($data['valid_until']) ? $data['valid_until'] : date('Y-m-d', strtotime('+15 days'));
$subject = trim($data['subject'] ?? '');
$currency = trim($data['currency'] ?? 'INR');
$status = trim($data['status'] ?? 'Draft');
$templateId = trim($data['template_id'] ?? 'classic');
$preparedBy = trim($data['prepared_by'] ?? ($biz['contact_person'] ?? ''));
$salesPerson = trim($data['sales_person'] ?? ($biz['signatory_name'] ?? ''));

$customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
$customerSnapshot = !empty($data['customer_snapshot']) ? $data['customer_snapshot'] : [];
$businessSnapshot = !empty($data['business_snapshot']) ? $data['business_snapshot'] : $biz;

$items = !empty($data['items']) && is_array($data['items']) ? $data['items'] : [];

// Run Centralized Calculation Engine
$calcOptions = [
    'tax_type' => $data['tax_type'] ?? 'GST',
    'tax_inclusive' => !empty($data['tax_inclusive']),
    'gst_rate' => $data['gst_rate'] ?? 18.0,
    'overall_discount_type' => $data['overall_discount_type'] ?? 'fixed',
    'overall_discount' => $data['overall_discount'] ?? 0.0,
    'additional_charges' => $data['additional_charges'] ?? 0.0,
    'advance_amount' => $data['advance_amount'] ?? 0.0,
    'enable_round_off' => $data['enable_round_off'] ?? true
];

$calc = CalculationEngine::calculate($items, $calcOptions);

$introSection = !empty($data['intro_section']) ? $data['intro_section'] : [];
$scopeSection = !empty($data['scope_section']) ? $data['scope_section'] : [];
$customizationSection = !empty($data['customization_section']) ? $data['customization_section'] : [];
$systemRequirements = !empty($data['system_requirements']) ? $data['system_requirements'] : [];
$termsConditions = !empty($data['terms_conditions']) ? $data['terms_conditions'] : [];
$notesSection = !empty($data['notes_section']) ? $data['notes_section'] : [];
$paymentTermsText = trim($data['payment_terms_text'] ?? '');

try {
    $pdo->beginTransaction();

    if ($id) {
        // Update existing quotation
        $stmt = $pdo->prepare("
            UPDATE quotations SET
                customer_id = ?, quotation_number = ?, ref_number = ?, date = ?, valid_until = ?,
                subject = ?, currency = ?, status = ?, template_id = ?, prepared_by = ?, sales_person = ?,
                subtotal = ?, total_discount = ?, taxable_amount = ?, tax_type = ?, tax_inclusive = ?,
                gst_rate = ?, cgst_amount = ?, sgst_amount = ?, igst_amount = ?, additional_charges = ?,
                round_off = ?, grand_total = ?, advance_amount = ?, balance_amount = ?,
                customer_snapshot = ?, business_snapshot = ?, intro_section = ?, scope_section = ?,
                customization_section = ?, system_requirements = ?, terms_conditions = ?,
                payment_terms_text = ?, notes_section = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");

        $stmt->execute([
            $customerId, $quoteNumber, $refNumber, $date, $validUntil,
            $subject, $currency, $status, $templateId, $preparedBy, $salesPerson,
            $calc['subtotal'], $calc['total_discount'], $calc['taxable_amount'],
            $calc['tax_type'], $calc['tax_inclusive'] ? 1 : 0, $calc['gst_rate'],
            $calc['cgst_amount'], $calc['sgst_amount'], $calc['igst_amount'],
            $calc['additional_charges'], $calc['round_off'], $calc['grand_total'],
            $calc['advance_amount'], $calc['balance_amount'],
            json_encode($customerSnapshot), json_encode($businessSnapshot),
            json_encode($introSection), json_encode($scopeSection),
            json_encode($customizationSection), json_encode($systemRequirements),
            json_encode($termsConditions), $paymentTermsText, json_encode($notesSection),
            $id, $userId
        ]);
        $quotationId = $id;

        // Delete existing items to replace with updated ones
        $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?")->execute([$quotationId]);
    } else {
        // Insert new quotation
        $publicShareId = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("
            INSERT INTO quotations (
                user_id, customer_id, quotation_number, ref_number, date, valid_until,
                subject, currency, status, template_id, prepared_by, sales_person,
                subtotal, total_discount, taxable_amount, tax_type, tax_inclusive,
                gst_rate, cgst_amount, sgst_amount, igst_amount, additional_charges,
                round_off, grand_total, advance_amount, balance_amount,
                customer_snapshot, business_snapshot, intro_section, scope_section,
                customization_section, system_requirements, terms_conditions,
                payment_terms_text, notes_section, public_share_id
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?
            )
        ");

        $stmt->execute([
            $userId, $customerId, $quoteNumber, $refNumber, $date, $validUntil,
            $subject, $currency, $status, $templateId, $preparedBy, $salesPerson,
            $calc['subtotal'], $calc['total_discount'], $calc['taxable_amount'],
            $calc['tax_type'], $calc['tax_inclusive'] ? 1 : 0, $calc['gst_rate'],
            $calc['cgst_amount'], $calc['sgst_amount'], $calc['igst_amount'],
            $calc['additional_charges'], $calc['round_off'], $calc['grand_total'],
            $calc['advance_amount'], $calc['balance_amount'],
            json_encode($customerSnapshot), json_encode($businessSnapshot),
            json_encode($introSection), json_encode($scopeSection),
            json_encode($customizationSection), json_encode($systemRequirements),
            json_encode($termsConditions), $paymentTermsText, json_encode($notesSection),
            $publicShareId
        ]);
        $quotationId = (int)$pdo->lastInsertId();

        // Increment sequence in business profile
        increment_quotation_sequence($biz['id']);
    }

    // Insert Items
    $itemStmt = $pdo->prepare("
        INSERT INTO quotation_items (
            quotation_id, product_id, item_order, description, hsn_sac,
            quantity, unit, unit_price, discount_percent, discount_amount,
            tax_rate, line_total, billing_period, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($calc['items'] as $order => $it) {
        $itemStmt->execute([
            $quotationId,
            !empty($it['product_id']) ? (int)$it['product_id'] : null,
            $order + 1,
            $it['description'] ?? 'Item',
            $it['hsn_sac'] ?? '',
            $it['quantity'] ?? 1,
            $it['unit'] ?? 'Nos',
            $it['unit_price'] ?? 0,
            $it['discount_percent'] ?? 0,
            $it['discount_amount'] ?? 0,
            $it['tax_rate'] ?? $calc['gst_rate'],
            $it['line_total'] ?? 0,
            $it['billing_period'] ?? null,
            $it['notes'] ?? null
        ]);
    }

    $pdo->commit();

    json_response([
        'success' => true,
        'quotation_id' => $quotationId,
        'quotation_number' => $quoteNumber,
        'calculations' => $calc,
        'message' => 'Quotation saved successfully.'
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['success' => false, 'message' => 'Save failed: ' . $e->getMessage()], 500);
}
