<?php
/**
 * Quotation Studio - Convert Accepted Quotation into Tax Invoice
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$quoteId = (int)($_GET['quotation_id'] ?? 0);
$userId = current_user_id();
$pdo = get_db();

$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND user_id = ?");
$stmt->execute([$quoteId, $userId]);
$quotation = $stmt->fetch();

if (!$quotation) {
    flash_set('error', 'Quotation not found.');
    header("Location: " . BASE_URL . "/quotations/index.php");
    exit;
}

// Generate Invoice Number (e.g. INV-2026-001)
$invCountStmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE user_id = ?");
$invCountStmt->execute([$userId]);
$invSeq = (int)$invCountStmt->fetchColumn() + 1;
$invNumber = 'INV-' . date('Y') . '-' . str_pad((string)$invSeq, 3, '0', STR_PAD_LEFT);

$pdo->beginTransaction();
try {
    $insInv = $pdo->prepare("
        INSERT INTO invoices (
            user_id, quotation_id, customer_id, invoice_number, issue_date, due_date,
            status, subtotal, total_discount, taxable_amount, cgst_amount, sgst_amount,
            igst_amount, round_off, grand_total, paid_amount, balance_due, payment_terms,
            notes, customer_snapshot, business_snapshot
        ) VALUES (
            ?, ?, ?, ?, CURRENT_DATE, ?,
            'Unpaid', ?, ?, ?, ?, ?,
            ?, ?, ?, 0.00, ?, ?,
            ?, ?, ?
        )
    ");

    $dueDate = date('Y-m-d', strtotime('+15 days'));
    $notes = "Converted from Proposal #" . $quotation['quotation_number'];

    $insInv->execute([
        $userId,
        $quoteId,
        $quotation['customer_id'],
        $invNumber,
        $dueDate,
        $quotation['subtotal'],
        $quotation['total_discount'],
        $quotation['taxable_amount'],
        $quotation['cgst_amount'],
        $quotation['sgst_amount'],
        $quotation['igst_amount'],
        $quotation['round_off'],
        $quotation['grand_total'],
        $quotation['grand_total'], // Balance Due initially equals grand total
        $quotation['payment_terms_text'],
        $notes,
        $quotation['customer_snapshot'],
        $quotation['business_snapshot']
    ]);
    $invoiceId = (int)$pdo->lastInsertId();

    // Copy Items from quotation to invoice
    $itStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
    $itStmt->execute([$quoteId]);
    $items = $itStmt->fetchAll();

    $insInvItem = $pdo->prepare("
        INSERT INTO invoice_items (
            invoice_id, product_id, description, hsn_sac, quantity, unit,
            unit_price, discount_percent, tax_rate, line_total
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $it) {
        $insInvItem->execute([
            $invoiceId,
            $it['product_id'],
            $it['description'],
            $it['hsn_sac'],
            $it['quantity'],
            $it['unit'],
            $it['unit_price'],
            $it['discount_percent'],
            $it['tax_rate'],
            $it['line_total']
        ]);
    }

    // Update Quotation Status to 'Invoiced'
    $upQuote = $pdo->prepare("UPDATE quotations SET status = 'Invoiced' WHERE id = ?");
    $upQuote->execute([$quoteId]);

    $pdo->commit();

    flash_set('success', "Accepted proposal successfully converted to Tax Invoice {$invNumber}!");
    header("Location: " . BASE_URL . "/invoices/view.php?id=" . $invoiceId);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    flash_set('error', 'Invoice conversion failed: ' . $e->getMessage());
    header("Location: " . BASE_URL . "/quotations/view.php?id=" . $quoteId);
    exit;
}
