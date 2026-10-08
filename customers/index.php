<?php
/**
 * Quotation Studio - Customers Directory
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

$search = trim($_GET['search'] ?? '');
$query = "
    SELECT c.*,
           COUNT(DISTINCT q.id) as quotation_count,
           COUNT(DISTINCT i.id) as invoice_count,
           COALESCE(SUM(DISTINCT q.grand_total), 0) as total_quoted
    FROM customers c
    LEFT JOIN quotations q ON c.id = q.customer_id
    LEFT JOIN invoices i ON c.id = i.customer_id
    WHERE c.user_id = ?
";
$params = [$userId];

if ($search !== '') {
    $query .= " AND (c.name LIKE ? OR c.company_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.gstin LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like, $like);
}

$query .= " GROUP BY c.id ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Customers';
$activeNav = 'customers';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Customers CRM</h1>
        <div class="page-subtitle">Manage client accounts, contact persons, tax registration, and billing addresses.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/customers/create.php" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Add Customer
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="" style="display: flex; gap: 12px; width: 100%; flex-wrap: wrap;">
        <div class="filter-input">
            <input type="text" name="search" class="form-control" placeholder="Search by name, company, email, phone, GSTIN..." value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn btn-secondary">
            <i class="fas fa-search"></i> Search
        </button>
        <?php if ($search !== ''): ?>
            <a href="<?= BASE_URL ?>/customers/index.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Customer / Company</th>
                    <th>Contact Person</th>
                    <th>Email & Phone</th>
                    <th>GSTIN / Location</th>
                    <th>Proposals</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-users" style="font-size: 32px; opacity: 0.4; margin-bottom: 10px; display: block;"></i>
                            No customers found. <?= $search !== '' ? 'Try adjusting your search query.' : 'Click "+ Add Customer" to create your first client profile.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/customers/view.php?id=<?= (int)$c['id'] ?>" style="font-weight: 600; font-size: 14px;">
                                    <?= e($c['company_name'] ?: $c['name']) ?>
                                </a>
                                <?php if (!empty($c['company_name']) && $c['company_name'] !== $c['name']): ?>
                                    <div style="font-size: 12px; color: var(--text-muted);"><?= e($c['name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= e($c['contact_person'] ?: '—') ?></td>
                            <td>
                                <div><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email'] ?: '—') ?></a></div>
                                <div style="font-size: 12px; color: var(--text-muted);"><?= e($c['phone'] ?: '') ?></div>
                            </td>
                            <td>
                                <?php if (!empty($c['gstin'])): ?>
                                    <span class="badge badge-draft" style="font-family: monospace;"><?= e($c['gstin']) ?></span>
                                <?php endif; ?>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                    <?= e($c['city'] ? $c['city'] . ', ' . $c['state'] : ($c['state'] ?: '—')) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-sent"><?= (int)$c['quotation_count'] ?> Quotes</span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL ?>/customers/view.php?id=<?= (int)$c['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/customers/edit.php?id=<?= (int)$c['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="<?= BASE_URL ?>/customers/delete.php" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
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
