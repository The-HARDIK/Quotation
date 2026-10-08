<?php
/**
 * Quotation Studio - Invoices Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$query = "
    SELECT i.*, c.name as customer_name, c.company_name as customer_company
    FROM invoices i
    LEFT JOIN customers c ON i.customer_id = c.id
    WHERE i.user_id = ?
";
$params = [$userId];

if ($search !== '') {
    $query .= " AND (i.invoice_number LIKE ? OR c.company_name LIKE ? OR c.name LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like);
}

if ($statusFilter !== '') {
    $query .= " AND i.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY i.issue_date DESC, i.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Invoice Metrics
$totalInvoiced = 0;
$totalCollected = 0;
$totalOutstanding = 0;
foreach ($invoices as $inv) {
    $totalInvoiced += (float)$inv['grand_total'];
    $totalCollected += (float)$inv['paid_amount'];
    $totalOutstanding += (float)$inv['balance_due'];
}

$pageTitle = 'Invoices';
$activeNav = 'invoices';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Tax Invoices</h1>
        <div class="page-subtitle">Track billed orders, receivables, customer payments, and payment statuses.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/invoices/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Direct Invoice
        </a>
    </div>
</div>

<!-- Metrics Overview -->
<div class="metrics-grid">
    <div class="metric-card accent-info">
        <div class="metric-header">
            <span class="metric-title">Total Invoiced</span>
            <div class="metric-icon"><i class="fas fa-receipt"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($totalInvoiced) ?></div>
        <div class="metric-subtext"><?= count($invoices) ?> invoices</div>
    </div>

    <div class="metric-card accent-success">
        <div class="metric-header">
            <span class="metric-title">Collected Revenue</span>
            <div class="metric-icon"><i class="fas fa-check-double"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($totalCollected) ?></div>
        <div class="metric-subtext text-success">Settled in bank account</div>
    </div>

    <div class="metric-card accent-warning">
        <div class="metric-header">
            <span class="metric-title">Outstanding Balance</span>
            <div class="metric-icon"><i class="fas fa-hourglass-half"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($totalOutstanding) ?></div>
        <div class="metric-subtext">Pending client payments</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap;">
        <div class="filter-input">
            <input type="text" name="search" class="form-control" placeholder="Search invoice number, client..." value="<?= e($search) ?>">
        </div>
        <div style="min-width: 160px;">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="Unpaid" <?= ($statusFilter === 'Unpaid') ? 'selected' : '' ?>>Unpaid</option>
                <option value="Partially Paid" <?= ($statusFilter === 'Partially Paid') ? 'selected' : '' ?>>Partially Paid</option>
                <option value="Paid" <?= ($statusFilter === 'Paid') ? 'selected' : '' ?>>Paid</option>
                <option value="Overdue" <?= ($statusFilter === 'Overdue') ? 'selected' : '' ?>>Overdue</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Search</button>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="<?= BASE_URL ?>/invoices/index.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Invoice Amount</th>
                    <th>Paid</th>
                    <th>Balance Due</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-receipt" style="font-size: 32px; opacity: 0.4; margin-bottom: 10px; display: block;"></i>
                            No invoices created yet. Accepted quotations can be converted to invoices in one click.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv):
                        $customerLabel = $inv['customer_company'] ?: ($inv['customer_name'] ?: 'Ad-hoc Client');
                    ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/invoices/view.php?id=<?= (int)$inv['id'] ?>" style="font-weight: 700;">
                                    <?= e($inv['invoice_number']) ?>
                                </a>
                            </td>
                            <td><div style="font-weight: 600;"><?= e($customerLabel) ?></div></td>
                            <td><?= format_date($inv['issue_date']) ?></td>
                            <td><?= format_date($inv['due_date']) ?></td>
                            <td style="font-weight: 700;"><?= format_currency($inv['grand_total']) ?></td>
                            <td style="color: var(--secondary); font-weight: 600;"><?= format_currency($inv['paid_amount']) ?></td>
                            <td style="color: <?= ((float)$inv['balance_due'] > 0) ? 'var(--danger)' : 'var(--text-muted)' ?>; font-weight: 700;">
                                <?= format_currency($inv['balance_due']) ?>
                            </td>
                            <td><?= status_badge($inv['status']) ?></td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL ?>/invoices/view.php?id=<?= (int)$inv['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View & Settle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="<?= BASE_URL ?>/invoices/delete.php" style="display: inline-block;" onsubmit="return confirm('Delete this invoice?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm btn-icon" style="color: var(--danger);" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
