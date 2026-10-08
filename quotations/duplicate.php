<?php
/**
 * Quotation Studio - Duplicate Quotation
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$id = (int)($_GET['id'] ?? 0);
$userId = current_user_id();
$biz = current_business();
$pdo = get_db();

$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$old = $stmt->fetch();

if (!$old) {
    flash_set('error', 'Quotation not found.');
    header("Location: " . BASE_URL . "/quotations/index.php");
    exit;
}

$newNumber = generate_quotation_number($biz);
$newShareId = bin2hex(random_bytes(16));

$pdo->beginTransaction();
try {
    $insStmt = $pdo->prepare("
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
            ?, ?, ?, ?, CURRENT_DATE, ?,
            ?, ?, 'Draft', ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?
        )
    ");

    $validUntil = date('Y-m-d', strtotime('+15 days'));

    $insStmt->execute([
        $userId, $old['customer_id'], $newNumber, $old['ref_number'], $validUntil,
        $old['subject'] . ' (Duplicate)', $old['currency'], $old['template_id'], $old['prepared_by'], $old['sales_person'],
        $old['subtotal'], $old['total_discount'], $old['taxable_amount'], $old['tax_type'], $old['tax_inclusive'],
        $old['gst_rate'], $old['cgst_amount'], $old['sgst_amount'], $old['igst_amount'], $old['additional_charges'],
        $old['round_off'], $old['grand_total'], 0.00, $old['grand_total'],
        $old['customer_snapshot'], $old['business_snapshot'], $old['intro_section'], $old['scope_section'],
        $old['customization_section'], $old['system_requirements'], $old['terms_conditions'],
        $old['payment_terms_text'], $old['notes_section'], $newShareId
    ]);
    $newId = (int)$pdo->lastInsertId();

    // Copy Items
    $itStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
    $itStmt->execute([$id]);
    $items = $itStmt->fetchAll();

    $insItem = $pdo->prepare("
        INSERT INTO quotation_items (
            quotation_id, product_id, item_order, description, hsn_sac, quantity,
            unit, unit_price, discount_percent, discount_amount, tax_rate, line_total, billing_period, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $it) {
        $insItem->execute([
            $newId, $it['product_id'], $it['item_order'], $it['description'], $it['hsn_sac'],
            $it['quantity'], $it['unit'], $it['unit_price'], $it['discount_percent'],
            $it['discount_amount'], $it['tax_rate'], $it['line_total'], $it['billing_period'], $it['notes']
        ]);
    }

    increment_quotation_sequence($biz['id']);
    $pdo->commit();

    flash_set('success', 'Quotation duplicated as new draft ' . $newNumber . '. You may now customize and save it.');
    header("Location: " . BASE_URL . "/quotations/edit.php?id=" . $newId);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    flash_set('error', 'Duplication failed: ' . $e->getMessage());
    header("Location: " . BASE_URL . "/quotations/index.php");
    exit;
}
