<?php
/**
 * Quotation Studio - Record New Payment
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

$selectedInvoiceId = (int)($_GET['invoice_id'] ?? 0);

// Fetch unpaid or partially paid invoices for the user
$invStmt = $pdo->prepare("
    SELECT i.id, i.invoice_number, i.grand_total, i.paid_amount, i.balance_due, c.name as customer_name, c.company_name
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    WHERE i.user_id = ? AND i.status != 'Cancelled' AND i.balance_due > 0
    ORDER BY i.issue_date DESC, i.id DESC
");
$invStmt->execute([$userId]);
$unpaidInvoices = $invStmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Invalid session token.';
    }

    $invoiceId = (int)($_POST['invoice_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $paymentDate = trim($_POST['payment_date'] ?? date('Y-m-d'));
    $paymentMethod = trim($_POST['payment_method'] ?? 'Bank Transfer');
    $referenceNumber = trim($_POST['reference_number'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($invoiceId <= 0) {
        $errors[] = 'Please select a valid invoice.';
    }

    if ($amount <= 0) {
        $errors[] = 'Payment amount must be greater than zero.';
    }

    if (empty($paymentDate)) {
        $errors[] = 'Payment date is required.';
    }

    if (empty($errors)) {
        // Fetch target invoice
        $chkStmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
        $chkStmt->execute([$invoiceId, $userId]);
        $targetInvoice = $chkStmt->fetch();

        if (!$targetInvoice) {
            $errors[] = 'Selected invoice was not found.';
        } else {
            $pdo->beginTransaction();
            try {
                $payNumber = 'PAY-' . date('Ymd') . '-' . rand(100, 999);

                $insPay = $pdo->prepare("
                    INSERT INTO payments (user_id, invoice_id, payment_number, payment_date, amount, payment_method, reference_number, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insPay->execute([$userId, $invoiceId, $payNumber, $paymentDate, $amount, $paymentMethod, $referenceNumber, $notes]);

                // Recalculate invoice
                $newPaid = (float)$targetInvoice['paid_amount'] + $amount;
                $newBalance = max(0.0, (float)$targetInvoice['grand_total'] - $newPaid);
                $newStatus = ($newBalance <= 0) ? 'Paid' : 'Partially Paid';

                $upInv = $pdo->prepare("
                    UPDATE invoices SET paid_amount = ?, balance_due = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND user_id = ?
                ");
                $upInv->execute([$newPaid, $newBalance, $newStatus, $invoiceId, $userId]);

                $pdo->commit();
                flash_set('success', "Payment receipt $payNumber of " . format_currency($amount) . " recorded successfully!");
                header("Location: " . BASE_URL . "/payments/index.php");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Record Payment';
$activeNav = 'payments';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Record Payment Settlement</h1>
        <div class="page-subtitle">Reconcile an incoming payment receipt against an open tax invoice.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/payments/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Payment Ledger
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <i class="fas fa-exclamation-circle"></i>
        <div>
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div class="card" style="max-width: 720px; margin: 0 auto;">
    <div class="card-body" style="padding: 32px;">
        <form method="POST" action="">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label required">Select Open Tax Invoice</label>
                <select name="invoice_id" id="invoiceSelect" class="form-control" required onchange="updateInvoiceDetails()">
                    <option value="">-- Choose Invoice to Settle --</option>
                    <?php foreach ($unpaidInvoices as $inv): ?>
                        <option value="<?= (int)$inv['id'] ?>" 
                                data-total="<?= (float)$inv['grand_total'] ?>" 
                                data-balance="<?= (float)$inv['balance_due'] ?>"
                                data-customer="<?= e($inv['company_name'] ?: $inv['customer_name']) ?>"
                                <?= ($selectedInvoiceId === (int)$inv['id']) ? 'selected' : '' ?>>
                            <?= e($inv['invoice_number']) ?> &mdash; <?= e($inv['company_name'] ?: $inv['customer_name']) ?> (Due: <?= format_currency($inv['balance_due']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($unpaidInvoices)): ?>
                    <small style="color: var(--text-muted); margin-top: 4px; display: block;">No invoices currently have outstanding balances.</small>
                <?php endif; ?>
            </div>

            <!-- Balance Preview Callout -->
            <div id="invoiceInfoBox" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); padding: 16px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; color: #166534; font-weight: 600;">Customer</div>
                        <strong id="infoCustomer" style="color: #14532d; font-size: 15px;">—</strong>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 11px; text-transform: uppercase; color: #166534; font-weight: 600;">Outstanding Balance Due</div>
                        <strong id="infoBalance" style="color: #15803d; font-size: 18px;">—</strong>
                    </div>
                </div>
            </div>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label required">Payment Amount (₹)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="amountInput" class="form-control" placeholder="0.00" required>
                    <small style="color: var(--text-muted); cursor: pointer;" onclick="fillFullBalance()">Click to fill remaining balance</small>
                </div>

                <div class="form-group">
                    <label class="form-label required">Payment Date</label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label required">Payment Method</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="Bank Transfer">Bank Transfer / NEFT / RTGS</option>
                        <option value="UPI">UPI / QR Code</option>
                        <option value="Cheque">Cheque / Demand Draft</option>
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit / Debit Card</option>
                        <option value="Online Gateway">Online Gateway</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Reference / UTR / Cheque #</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="e.g. UTR198273645 / CHQ-1002">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 28px;">
                <label class="form-label">Settlement Notes</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Received via ICICI Current Account, milestone 1 settlement."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <a href="<?= BASE_URL ?>/payments/index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Save Payment Receipt
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateInvoiceDetails() {
    const sel = document.getElementById('invoiceSelect');
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('invoiceInfoBox');
    const amountInput = document.getElementById('amountInput');

    if (opt && opt.value) {
        const bal = parseFloat(opt.getAttribute('data-balance') || '0');
        const cust = opt.getAttribute('data-customer') || '—';
        document.getElementById('infoCustomer').textContent = cust;
        document.getElementById('infoBalance').textContent = '₹' + bal.toLocaleString('en-IN', {minimumFractionDigits: 2});
        box.style.display = 'block';
        if (!amountInput.value || parseFloat(amountInput.value) <= 0) {
            amountInput.value = bal.toFixed(2);
        }
    } else {
        box.style.display = 'none';
    }
}

function fillFullBalance() {
    const sel = document.getElementById('invoiceSelect');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        const bal = parseFloat(opt.getAttribute('data-balance') || '0');
        document.getElementById('amountInput').value = bal.toFixed(2);
    }
}

document.addEventListener('DOMContentLoaded', updateInvoiceDetails);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
