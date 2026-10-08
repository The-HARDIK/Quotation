<?php
/**
 * Quotation Studio - Payments History & Settlement Ledger
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

$stmt = $pdo->prepare("
    SELECT p.*, i.invoice_number, i.grand_total as invoice_total, c.company_name, c.name as customer_name
    FROM payments p
    JOIN invoices i ON p.invoice_id = i.id
    LEFT JOIN customers c ON i.customer_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.payment_date DESC, p.id DESC
");
$stmt->execute([$userId]);
$payments = $stmt->fetchAll();

$totalCollections = 0;
foreach ($payments as $p) {
    $totalCollections += (float)$p['amount'];
}

$pageTitle = 'Payments';
$activeNav = 'payments';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Payment Settlements & Receipts</h1>
        <div class="page-subtitle">Track collections, bank references, and reconciled receipts against tax invoices.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/payments/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Record Payment
        </a>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-card accent-success">
        <div class="metric-header"><span class="metric-title">Total Payments Collected</span><div class="metric-icon"><i class="fas fa-hand-holding-usd"></i></div></div>
        <div class="metric-value"><?= format_currency($totalCollections) ?></div>
        <div class="metric-subtext text-success"><?= count($payments) ?> settlements reconciled</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Payment #</th>
                    <th>Payment Date</th>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Payment Method</th>
                    <th>Reference / UTR</th>
                    <th>Notes</th>
                    <th style="text-align: right;">Amount Received</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-wallet" style="font-size: 32px; opacity: 0.4; margin-bottom: 10px; display: block;"></i>
                            No payments recorded yet. Payments can be recorded directly from invoices.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><code><?= e($p['payment_number']) ?></code></td>
                            <td><?= format_date($p['payment_date']) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/invoices/view.php?id=<?= (int)$p['invoice_id'] ?>" style="font-weight: 600;">
                                    <?= e($p['invoice_number']) ?>
                                </a>
                            </td>
                            <td><?= e($p['company_name'] ?: $p['customer_name'] ?: '—') ?></td>
                            <td><span class="badge badge-draft"><?= e($p['payment_method']) ?></span></td>
                            <td><code><?= e($p['reference_number'] ?: '—') ?></code></td>
                            <td><span style="font-size: 12px; color: var(--text-muted);"><?= e($p['notes'] ?: '—') ?></span></td>
                            <td style="text-align: right; font-weight: 700; color: var(--secondary); font-size: 14px;">
                                <?= format_currency($p['amount']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
