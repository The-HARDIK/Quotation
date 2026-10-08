<?php
/**
 * Quotation Studio - View Customer Profile & History
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$customer = $stmt->fetch();

if (!$customer) {
    flash_set('error', 'Customer not found.');
    header("Location: " . BASE_URL . "/customers/index.php");
    exit;
}

// Fetch Quotation History
$qStmt = $pdo->prepare("
    SELECT * FROM quotations WHERE customer_id = ? AND user_id = ? ORDER BY date DESC, id DESC
");
$qStmt->execute([$id, $userId]);
$quotations = $qStmt->fetchAll();

// Fetch Invoices History
$iStmt = $pdo->prepare("
    SELECT * FROM invoices WHERE customer_id = ? AND user_id = ? ORDER BY issue_date DESC, id DESC
");
$iStmt->execute([$id, $userId]);
$invoices = $iStmt->fetchAll();

// Metrics
$totalQuoted = 0;
$acceptedCount = 0;
$rejectedCount = 0;
foreach ($quotations as $q) {
    $totalQuoted += (float)$q['grand_total'];
    if (in_array($q['status'], ['Accepted', 'Invoiced'])) $acceptedCount++;
    if ($q['status'] === 'Rejected') $rejectedCount++;
}

$totalInvoiced = 0;
foreach ($invoices as $inv) {
    $totalInvoiced += (float)$inv['grand_total'];
}

$pageTitle = $customer['company_name'] ?: $customer['name'];
$activeNav = 'customers';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($customer['company_name'] ?: $customer['name']) ?></h1>
        <div class="page-subtitle"><?= e($customer['contact_person'] ? $customer['contact_person'] . ' — ' : '') ?><?= e($customer['name']) ?></div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/quotations/create.php?customer_id=<?= $id ?>" class="btn btn-primary">
            <i class="fas fa-file-contract"></i> Create Quotation for Client
        </a>
        <a href="<?= BASE_URL ?>/customers/edit.php?id=<?= $id ?>" class="btn btn-secondary">
            <i class="fas fa-edit"></i> Edit Details
        </a>
    </div>
</div>

<!-- Customer Metrics -->
<div class="metrics-grid">
    <div class="metric-card accent-info">
        <div class="metric-header">
            <span class="metric-title">Total Quoted Amount</span>
            <div class="metric-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($totalQuoted) ?></div>
        <div class="metric-subtext"><?= count($quotations) ?> proposals issued</div>
    </div>

    <div class="metric-card accent-success">
        <div class="metric-header">
            <span class="metric-title">Accepted Orders</span>
            <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <div class="metric-value"><?= $acceptedCount ?></div>
        <div class="metric-subtext text-success"><?= count($quotations) > 0 ? round(($acceptedCount / count($quotations)) * 100) : 0 ?>% Acceptance Rate</div>
    </div>

    <div class="metric-card accent-purple">
        <div class="metric-header">
            <span class="metric-title">Invoiced Revenue</span>
            <div class="metric-icon"><i class="fas fa-receipt"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($totalInvoiced) ?></div>
        <div class="metric-subtext"><?= count($invoices) ?> invoices generated</div>
    </div>
</div>

<!-- Client Profile Cards -->
<div class="form-grid" style="grid-template-columns: 1fr 2fr; margin-bottom: 24px;">
    <!-- Details Card -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-id-card" style="color: var(--primary); margin-right: 8px;"></i> Client Details</div>
        </div>
        <div class="card-body" style="font-size: 13px;">
            <div style="margin-bottom: 12px;">
                <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">GSTIN</div>
                <div style="font-weight: 600; font-family: monospace;"><?= e($customer['gstin'] ?: '—') ?></div>
            </div>
            <div style="margin-bottom: 12px;">
                <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Email</div>
                <div><?= e($customer['email'] ?: '—') ?></div>
            </div>
            <div style="margin-bottom: 12px;">
                <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Phone</div>
                <div><?= e($customer['phone'] ?: '—') ?></div>
            </div>
            <div style="margin-bottom: 12px;">
                <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Billing Address</div>
                <div><?= nl2br(e($customer['billing_address'] ?: '—')) ?></div>
                <div style="color: var(--text-muted); margin-top: 4px;">
                    <?= e($customer['city'] ? $customer['city'] . ', ' . $customer['state'] . ' ' . $customer['pincode'] : '') ?>
                </div>
            </div>
            <?php if (!empty($customer['notes'])): ?>
                <div>
                    <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">Internal Notes</div>
                    <div style="background: var(--bg-main); padding: 8px; border-radius: 4px;"><?= nl2br(e($customer['notes'])) ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quotation History -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-file-contract" style="color: var(--primary); margin-right: 8px;"></i> Quotation History</div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Quote #</th>
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($quotations)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No quotations created yet for this client.</td></tr>
                    <?php else: ?>
                        <?php foreach ($quotations as $q): ?>
                            <tr>
                                <td><a href="<?= BASE_URL ?>/quotations/view.php?id=<?= $q['id'] ?>" style="font-weight: 600;"><?= e($q['quotation_number']) ?></a></td>
                                <td><?= format_date($q['date']) ?></td>
                                <td><?= e(substr($q['subject'] ?? '—', 0, 30)) ?></td>
                                <td style="font-weight: 700;"><?= format_currency($q['grand_total']) ?></td>
                                <td><?= status_badge($q['status']) ?></td>
                                <td style="text-align: right;">
                                    <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= $q['id'] ?>" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
