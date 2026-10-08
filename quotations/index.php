<?php
/**
 * Quotation Studio - Quotations List & Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

// Filters & Search
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$customerFilter = (int)($_GET['customer_id'] ?? 0);
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$query = "
    SELECT q.*, c.name as customer_name, c.company_name as customer_company
    FROM quotations q
    LEFT JOIN customers c ON q.customer_id = c.id
    WHERE q.user_id = ?
";
$params = [$userId];

if ($search !== '') {
    $query .= " AND (q.quotation_number LIKE ? OR q.subject LIKE ? OR c.company_name LIKE ? OR c.name LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like);
}

if ($statusFilter !== '') {
    if ($statusFilter === 'Expired') {
        $query .= " AND (q.status = 'Expired' OR (q.valid_until < CURRENT_DATE AND q.status IN ('Sent', 'Viewed')))";
    } else {
        $query .= " AND q.status = ?";
        $params[] = $statusFilter;
    }
}

if ($customerFilter > 0) {
    $query .= " AND q.customer_id = ?";
    $params[] = $customerFilter;
}

if ($dateFrom !== '') {
    $query .= " AND q.date >= ?";
    $params[] = $dateFrom;
}

if ($dateTo !== '') {
    $query .= " AND q.date <= ?";
    $params[] = $dateTo;
}

$query .= " ORDER BY q.date DESC, q.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$quotations = $stmt->fetchAll();

// Customers for filter dropdown
$cListStmt = $pdo->prepare("SELECT id, name, company_name FROM customers WHERE user_id = ? ORDER BY company_name ASC, name ASC");
$cListStmt->execute([$userId]);
$customerList = $cListStmt->fetchAll();

$pageTitle = 'Quotations';
$activeNav = 'quotations';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Commercial Quotations</h1>
        <div class="page-subtitle">Track, duplicate, share, and manage enterprise proposals throughout the sales pipeline.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/quotations/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Quotation
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" action="" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: center;">
        <div class="filter-input" style="min-width: 220px;">
            <input type="text" name="search" class="form-control" placeholder="Search number, subject, customer..." value="<?= e($search) ?>">
        </div>

        <div style="min-width: 140px;">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="Draft" <?= ($statusFilter === 'Draft') ? 'selected' : '' ?>>Draft</option>
                <option value="Sent" <?= ($statusFilter === 'Sent') ? 'selected' : '' ?>>Sent</option>
                <option value="Viewed" <?= ($statusFilter === 'Viewed') ? 'selected' : '' ?>>Viewed</option>
                <option value="Accepted" <?= ($statusFilter === 'Accepted') ? 'selected' : '' ?>>Accepted</option>
                <option value="Rejected" <?= ($statusFilter === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                <option value="Expired" <?= ($statusFilter === 'Expired') ? 'selected' : '' ?>>Expired</option>
                <option value="Invoiced" <?= ($statusFilter === 'Invoiced') ? 'selected' : '' ?>>Invoiced</option>
            </select>
        </div>

        <div style="min-width: 180px;">
            <select name="customer_id" class="form-control" onchange="this.form.submit()">
                <option value="0">All Customers</option>
                <?php foreach ($customerList as $cl): ?>
                    <option value="<?= $cl['id'] ?>" <?= ($customerFilter == $cl['id']) ? 'selected' : '' ?>>
                        <?= e($cl['company_name'] ?: $cl['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($search !== '' || $statusFilter !== '' || $customerFilter > 0 || $dateFrom !== '' || $dateTo !== ''): ?>
            <a href="<?= BASE_URL ?>/quotations/index.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Quotation #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Valid Until</th>
                    <th>Grand Total</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($quotations)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-file-invoice" style="font-size: 32px; opacity: 0.4; margin-bottom: 10px; display: block;"></i>
                            No quotations found. Click "+ Create Quotation" to launch the Quotation Builder.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($quotations as $q):
                        $customerLabel = $q['customer_company'] ?: ($q['customer_name'] ?: 'Ad-hoc Client');
                        $isExpired = (!empty($q['valid_until']) && strtotime($q['valid_until']) < time() && in_array($q['status'], ['Sent', 'Viewed']));
                        $displayStatus = $isExpired ? 'Expired' : $q['status'];
                        $shareUrl = BASE_URL . '/public/quotation.php?id=' . e($q['public_share_id']);
                    ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= (int)$q['id'] ?>" style="font-weight: 700; color: var(--primary);">
                                    <?= e($q['quotation_number']) ?>
                                </a>
                                <?php if (!empty($q['subject'])): ?>
                                    <div style="font-size: 11.5px; color: var(--text-muted);"><?= e(substr($q['subject'], 0, 50)) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?= e($customerLabel) ?></div>
                            </td>
                            <td><?= format_date($q['date']) ?></td>
                            <td>
                                <span class="<?= $isExpired ? 'text-danger' : '' ?>">
                                    <?= format_date($q['valid_until']) ?>
                                </span>
                            </td>
                            <td style="font-weight: 700; font-size: 14px;">
                                <?= format_currency($q['grand_total']) ?>
                            </td>
                            <td><?= status_badge($displayStatus) ?></td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 4px;">
                                    <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View Proposal">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/quotations/edit.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit Proposal">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/quotations/duplicate.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Duplicate">
                                        <i class="fas fa-copy"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/quotations/download.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Download PDF">
                                        <i class="fas fa-file-pdf" style="color: var(--danger);"></i>
                                    </a>
                                    <button type="button" class="btn btn-secondary btn-sm btn-icon" title="Copy Public Share Link" onclick="copyToClipboard('<?= $shareUrl ?>', 'Public customer review link copied!')">
                                        <i class="fas fa-share-alt" style="color: var(--info);"></i>
                                    </button>
                                    <?php if ($q['status'] === 'Accepted'): ?>
                                        <a href="<?= BASE_URL ?>/invoices/convert-from-quotation.php?quotation_id=<?= (int)$q['id'] ?>" class="btn btn-success btn-sm btn-icon" title="Convert to Invoice">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                    <?php endif; ?>
                                    <form method="POST" action="<?= BASE_URL ?>/quotations/delete.php" style="display: inline-block;" onsubmit="return confirm('Delete this quotation?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
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
