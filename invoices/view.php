<?php
/**
 * Quotation Studio - View Invoice & Record Payments
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$id = (int)($_GET['id'] ?? 0);
$userId = current_user_id();
$pdo = get_db();

$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    flash_set('error', 'Invoice not found.');
    header("Location: " . BASE_URL . "/invoices/index.php");
    exit;
}

// Handle Record Payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    if (!verify_csrf()) {
        flash_set('error', 'Invalid token.');
    } else {
        $amount = (float)($_POST['amount'] ?? 0);
        $payDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $method = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $ref = trim($_POST['reference_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($amount <= 0) {
            flash_set('error', 'Payment amount must be greater than zero.');
        } else {
            $pdo->beginTransaction();
            try {
                // Insert payment record
                $payNumber = 'PAY-' . date('Ymd') . '-' . rand(100, 999);
                $insPay = $pdo->prepare("
                    INSERT INTO payments (user_id, invoice_id, payment_number, payment_date, amount, payment_method, reference_number, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insPay->execute([$userId, $id, $payNumber, $payDate, $amount, $method, $ref, $notes]);

                // Recalculate invoice paid amount & status
                $newPaid = (float)$invoice['paid_amount'] + $amount;
                $newBalance = max(0.0, (float)$invoice['grand_total'] - $newPaid);
                $newStatus = ($newBalance <= 0) ? 'Paid' : 'Partially Paid';

                $upInv = $pdo->prepare("
                    UPDATE invoices SET paid_amount = ?, balance_due = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND user_id = ?
                ");
                $upInv->execute([$newPaid, $newBalance, $newStatus, $id, $userId]);

                $pdo->commit();
                flash_set('success', "Payment of " . format_currency($amount) . " recorded successfully!");
                header("Location: " . BASE_URL . "/invoices/view.php?id=" . $id);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                flash_set('error', 'Error recording payment: ' . $e->getMessage());
            }
        }
    }
}

// Fetch invoice items
$itStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$itStmt->execute([$id]);
$items = $itStmt->fetchAll();

// Fetch payment history
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date DESC, id DESC");
$payStmt->execute([$id]);
$payments = $payStmt->fetchAll();

$customerSnapshot = json_decode($invoice['customer_snapshot'] ?? '[]', true) ?: [];
$businessSnapshot = json_decode($invoice['business_snapshot'] ?? '[]', true) ?: [];

$pageTitle = 'Invoice ' . $invoice['invoice_number'];
$activeNav = 'invoices';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header no-print">
    <div>
        <h1 class="page-title"><?= e($invoice['invoice_number']) ?></h1>
        <div class="page-subtitle">Status: <?= status_badge($invoice['status']) ?> &bull; Due: <?= format_date($invoice['due_date']) ?></div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/invoices/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> All Invoices
        </a>
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Print Invoice
        </button>
        <?php if ((float)$invoice['balance_due'] > 0): ?>
            <button type="button" class="btn btn-success" onclick="openPaymentModal()">
                <i class="fas fa-hand-holding-usd"></i> Record Payment
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Financial Summary Strip -->
<div class="metrics-grid no-print">
    <div class="metric-card accent-info">
        <div class="metric-header"><span class="metric-title">Invoice Amount</span><div class="metric-icon"><i class="fas fa-file-invoice"></i></div></div>
        <div class="metric-value"><?= format_currency($invoice['grand_total']) ?></div>
    </div>
    <div class="metric-card accent-success">
        <div class="metric-header"><span class="metric-title">Amount Paid</span><div class="metric-icon"><i class="fas fa-check-double"></i></div></div>
        <div class="metric-value"><?= format_currency($invoice['paid_amount']) ?></div>
    </div>
    <div class="metric-card accent-warning">
        <div class="metric-header"><span class="metric-title">Balance Remaining</span><div class="metric-icon"><i class="fas fa-hourglass-half"></i></div></div>
        <div class="metric-value" style="color: <?= ((float)$invoice['balance_due'] > 0) ? 'var(--danger)' : 'var(--text-primary)' ?>;">
            <?= format_currency($invoice['balance_due']) ?>
        </div>
    </div>
</div>

<div class="card" style="max-width: 850px; margin: 0 auto 30px;">
    <div class="card-body" style="padding: 36px;">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; border-bottom: 2px solid var(--primary); padding-bottom: 16px; margin-bottom: 20px;">
            <div>
                <h2 style="font-family: var(--font-heading); font-size: 24px; color: var(--primary);"><?= e($businessSnapshot['company_name'] ?? 'Company Name') ?></h2>
                <div style="font-size: 12px; color: var(--text-muted);"><?= nl2br(e($businessSnapshot['address'] ?? '')) ?></div>
                <div style="font-size: 12px; color: var(--text-muted);">GSTIN: <?= e($businessSnapshot['gstin'] ?? '—') ?></div>
            </div>
            <div style="text-align: right;">
                <h3 style="font-family: var(--font-heading); font-size: 20px; color: var(--text-primary);">TAX INVOICE</h3>
                <div style="font-weight: 700; font-size: 14px;"><?= e($invoice['invoice_number']) ?></div>
                <div style="font-size: 12px; color: var(--text-muted);">Date: <?= format_date($invoice['issue_date']) ?></div>
                <div style="font-size: 12px; color: var(--text-muted);">Due: <?= format_date($invoice['due_date']) ?></div>
            </div>
        </div>

        <!-- Billed To -->
        <div style="background: #f8fafc; padding: 14px 18px; border-radius: 6px; margin-bottom: 24px; border-left: 3px solid var(--primary);">
            <div style="font-weight: 700; font-size: 11px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px;">Billed To:</div>
            <div style="font-weight: 700; font-size: 14px;"><?= e($customerSnapshot['company_name'] ?? $customerSnapshot['name'] ?? '') ?></div>
            <div style="font-size: 12.5px;"><?= nl2br(e($customerSnapshot['billing_address'] ?? '')) ?></div>
            <?php if (!empty($customerSnapshot['gstin'])): ?>
                <div style="font-size: 12px; margin-top: 4px;"><strong>GSTIN:</strong> <?= e($customerSnapshot['gstin']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Line Items Table -->
        <table class="table" style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>HSN/SAC</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Rate</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><strong><?= e($it['description']) ?></strong></td>
                        <td><?= e($it['hsn_sac'] ?: '—') ?></td>
                        <td style="text-align: center;"><?= (float)$it['quantity'] ?> <?= e($it['unit'] ?: 'Nos') ?></td>
                        <td style="text-align: right;"><?= format_currency($it['unit_price']) ?></td>
                        <td style="text-align: right; font-weight: 600;"><?= format_currency($it['line_total']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals Breakdown -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
            <div style="width: 280px; font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span>Taxable Amount:</span>
                    <strong><?= format_currency($invoice['taxable_amount']) ?></strong>
                </div>
                <?php if ((float)$invoice['cgst_amount'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>CGST:</span>
                        <span><?= format_currency($invoice['cgst_amount']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>SGST:</span>
                        <span><?= format_currency($invoice['sgst_amount']) ?></span>
                    </div>
                <?php elseif ((float)$invoice['igst_amount'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>IGST:</span>
                        <span><?= format_currency($invoice['igst_amount']) ?></span>
                    </div>
                <?php endif; ?>
                <div style="border-top: 1px solid var(--border-color); padding-top: 8px; display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; color: var(--primary);">
                    <span>Total Amount:</span>
                    <span><?= format_currency($invoice['grand_total']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 6px; color: var(--secondary); font-weight: 600;">
                    <span>Amount Paid:</span>
                    <span><?= format_currency($invoice['paid_amount']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 6px; color: var(--danger); font-weight: 700; font-size: 14px;">
                    <span>Balance Due:</span>
                    <span><?= format_currency($invoice['balance_due']) ?></span>
                </div>
            </div>
        </div>

        <!-- Bank Details -->
        <div style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 12px 16px; border-radius: 6px; font-size: 12px;">
            <div style="font-weight: 700; color: var(--primary); margin-bottom: 4px;">Bank Settlement Details:</div>
            <div>Bank: <strong><?= e($businessSnapshot['bank_name'] ?? 'HDFC Bank Ltd.') ?></strong> &bull; Acc No: <code><?= e($businessSnapshot['account_number'] ?? '') ?></code> &bull; IFSC: <code><?= e($businessSnapshot['ifsc_code'] ?? '') ?></code></div>
        </div>
    </div>
</div>

<!-- Payments Ledger Card -->
<div class="card no-print" style="max-width: 850px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-history" style="color: var(--secondary); margin-right: 8px;"></i> Payment Transactions Log</div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Payment #</th>
                    <th>Method</th>
                    <th>Reference / UTR</th>
                    <th>Notes</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No payments recorded yet against this invoice.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= format_date($p['payment_date']) ?></td>
                            <td><code><?= e($p['payment_number']) ?></code></td>
                            <td><span class="badge badge-draft"><?= e($p['payment_method']) ?></span></td>
                            <td><?= e($p['reference_number'] ?: '—') ?></td>
                            <td><?= e($p['notes'] ?: '—') ?></td>
                            <td style="text-align: right; font-weight: 700; color: var(--secondary);"><?= format_currency($p['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Record Payment Modal -->
<div id="paymentModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; width: 100%; max-width: 480px; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div class="card-header">
            <div class="card-title">Record Payment for <?= e($invoice['invoice_number']) ?></div>
            <button type="button" class="alert-close" onclick="closePaymentModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="record_payment" value="1">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Payment Amount (₹) <span class="required">*</span></label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="<?= (float)$invoice['balance_due'] ?>" max="<?= (float)$invoice['balance_due'] ?>" required>
                    <span style="font-size: 11px; color: var(--text-muted);">Current balance due: <?= format_currency($invoice['balance_due']) ?></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Date <span class="required">*</span></label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="Bank Transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                        <option value="UPI">UPI</option>
                        <option value="Cheque">Cheque</option>
                        <option value="Cash">Cash</option>
                        <option value="Card">Debit / Credit Card</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Transaction / Reference Number (UTR)</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="e.g. UTR-984321774">
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="card-footer" style="text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Save Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal() {
    document.getElementById('paymentModal').style.display = 'flex';
}
function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
