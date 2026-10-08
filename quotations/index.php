<?php
/**
 * Quotation Studio - Quotations List & Management (Modern Pipeline Edition)
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

$query .= " ORDER BY q.date DESC, q.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$quotations = $stmt->fetchAll();

// Count per status for pipeline tabs
$countsStmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_all,
        SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as total_draft,
        SUM(CASE WHEN status = 'Sent' THEN 1 ELSE 0 END) as total_sent,
        SUM(CASE WHEN status = 'Viewed' THEN 1 ELSE 0 END) as total_viewed,
        SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) as total_accepted,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as total_rejected,
        SUM(CASE WHEN status = 'Expired' OR (valid_until < CURRENT_DATE AND status IN ('Sent', 'Viewed')) THEN 1 ELSE 0 END) as total_expired
    FROM quotations WHERE user_id = ?
");
$countsStmt->execute([$userId]);
$tabCounts = $countsStmt->fetch() ?: [];

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
        <h1 class="page-title">Commercial Proposals</h1>
        <div class="page-subtitle">Track, duplicate, share, and manage enterprise proposals throughout the sales lifecycle.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/quotations/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Quotation
        </a>
    </div>
</div>

<!-- Pipeline Tab Bar with Counters -->
<div style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 8px; margin-bottom: 18px; scrollbar-width: thin;">
    <a href="<?= BASE_URL ?>/quotations/index.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?>">
        All Proposals <span style="font-family: var(--font-mono); font-size: 11px; opacity: 0.85; margin-left: 4px;">(<?= (int)($tabCounts['total_all'] ?? 0) ?>)</span>
    </a>
    <a href="<?= BASE_URL ?>/quotations/index.php?status=Draft" class="btn btn-sm <?= ($statusFilter === 'Draft') ? 'btn-primary' : 'btn-secondary' ?>">
        Drafts <span style="font-family: var(--font-mono); font-size: 11px; opacity: 0.85; margin-left: 4px;">(<?= (int)($tabCounts['total_draft'] ?? 0) ?>)</span>
    </a>
    <a href="<?= BASE_URL ?>/quotations/index.php?status=Sent" class="btn btn-sm <?= ($statusFilter === 'Sent') ? 'btn-primary' : 'btn-secondary' ?>">
        Sent <span style="font-family: var(--font-mono); font-size: 11px; opacity: 0.85; margin-left: 4px;">(<?= (int)($tabCounts['total_sent'] ?? 0) ?>)</span>
    </a>
    <a href="<?= BASE_URL ?>/quotations/index.php?status=Viewed" class="btn btn-sm <?= ($statusFilter === 'Viewed') ? 'btn-primary' : 'btn-secondary' ?>">
        Viewed <span style="font-family: var(--font-mono); font-size: 11px; opacity: 0.85; margin-left: 4px;">(<?= (int)($tabCounts['total_viewed'] ?? 0) ?>)</span>
    </a>
    <a href="<?= BASE_URL ?>/quotations/index.php?status=Accepted" class="btn btn-sm <?= ($statusFilter === 'Accepted') ? 'btn-primary' : 'btn-secondary' ?>">
        Accepted <span style="font-family: var(--font-mono); font-size: 11px; opacity: 0.85; margin-left: 4px;">(<?= (int)($tabCounts['total_accepted'] ?? 0) ?>)</span>
    </a>
    <a href="<?= BASE_URL ?>/quotations/index.php?status=Rejected" class="btn btn-sm <?= ($statusFilter === 'Rejected') ? 'btn-primary' : 'btn-secondary' ?>">
        Rejected <span style="font-family: var(--font-mono); font-size: 11px; opacity: 0.85; margin-left: 4px;">(<?= (int)($tabCounts['total_rejected'] ?? 0) ?>)</span>
    </a>
    <a href="<?= BASE_URL ?>/quotations/index.php?status=Expired" class="btn btn-sm <?= ($statusFilter === 'Expired') ? 'btn-primary' : 'btn-secondary' ?>">
        Expired <span style="font-family: var(--font-mono); font-size: 11px; opacity: 0.85; margin-left: 4px;">(<?= (int)($tabCounts['total_expired'] ?? 0) ?>)</span>
    </a>
</div>

<!-- Filters & Search Bar -->
<div class="filter-bar">
    <form method="GET" action="" style="display: flex; gap: 10px; width: 100%; flex-wrap: wrap; align-items: center;">
        <div class="filter-input" style="min-width: 240px;">
            <input type="text" name="search" class="form-control" placeholder="Search quotation #, subject, or customer..." value="<?= e($search) ?>">
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

        <?php if (!empty($statusFilter)): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
        <?php endif; ?>

        <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Search</button>
        <?php if ($search !== '' || $customerFilter > 0): ?>
            <a href="<?= BASE_URL ?>/quotations/index.php<?= !empty($statusFilter) ? '?status=' . urlencode($statusFilter) : '' ?>" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Proposals Table Card -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Quotation #</th>
                    <th>Customer / Company</th>
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
                        <td colspan="7" style="text-align: center; padding: 48px 20px; color: var(--text-muted);">
                            <i class="fas fa-file-invoice" style="font-size: 36px; opacity: 0.35; margin-bottom: 12px; display: block;"></i>
                            No quotations found in this view. Click "+ Create Quotation" to launch the Quotation Builder.
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
                                <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= (int)$q['id'] ?>" style="font-weight: 700; color: var(--text-primary); text-decoration: none; font-size: 13.5px;">
                                    <?= e($q['quotation_number']) ?>
                                </a>
                                <?php if (!empty($q['subject'])): ?>
                                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;"><?= e(substr($q['subject'], 0, 48)) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 13px; color: var(--text-secondary);"><?= e($customerLabel) ?></div>
                            </td>
                            <td style="font-size: 12.5px; color: var(--text-muted);"><?= format_date($q['date']) ?></td>
                            <td style="font-size: 12.5px;">
                                <span class="<?= $isExpired ? 'text-danger' : 'text-muted' ?>">
                                    <?= format_date($q['valid_until']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-family: var(--font-mono); font-weight: 700; font-size: 13.5px; color: var(--text-primary);">
                                    <?= format_currency($q['grand_total']) ?>
                                </div>
                            </td>
                            <td><?= status_badge($displayStatus) ?></td>
                            <td style="text-align: right;">
                                <div class="row-actions">
                                    <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View Proposal">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/quotations/edit.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit Proposal">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/quotations/duplicate.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Duplicate">
                                        <i class="fas fa-copy"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/quotations/download.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Download PDF">
                                        <i class="fas fa-file-pdf" style="color: var(--danger);"></i>
                                    </a>
                                    <button type="button" class="btn btn-secondary btn-sm btn-icon" title="Copy Public Review Link" onclick="copyToClipboard('<?= $shareUrl ?>', 'Client proposal review link copied!')">
                                        <i class="fas fa-share-nodes" style="color: var(--info);"></i>
                                    </button>
                                    <?php if ($q['status'] === 'Accepted'): ?>
                                        <a href="<?= BASE_URL ?>/invoices/convert-from-quotation.php?quotation_id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" style="color: var(--success);" title="Convert to Invoice">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-secondary btn-sm btn-icon delete-quote-btn" data-id="<?= (int)$q['id'] ?>" data-number="<?= e($q['quotation_number']) ?>" style="color: var(--danger);" title="Delete Proposal">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Hidden POST form for Delete -->
<form id="deleteQuoteForm" method="POST" action="<?= BASE_URL ?>/quotations/delete.php" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="deleteQuoteId" value="">
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.delete-quote-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            const num = btn.getAttribute('data-number');

            const confirmed = await UIEngine.confirm(
                'Delete Quotation',
                `Are you sure you want to permanently delete quotation "${num}"? This will also remove any generated records.`,
                'Delete Quotation',
                true
            );

            if (confirmed) {
                document.getElementById('deleteQuoteId').value = id;
                document.getElementById('deleteQuoteForm').submit();
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
