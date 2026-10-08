<?php
/**
 * Quotation Studio - Products & Services Catalog
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$query = "SELECT * FROM products_services WHERE user_id = ?";
$params = [$userId];

if ($search !== '') {
    $query .= " AND (name LIKE ? OR sku LIKE ? OR description LIKE ? OR hsn_sac LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like);
}

if ($category !== '') {
    $query .= " AND category = ?";
    $params[] = $category;
}

$query .= " ORDER BY name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch distinct categories for filter
$catStmt = $pdo->prepare("SELECT DISTINCT category FROM products_services WHERE user_id = ? AND category IS NOT NULL AND category != ''");
$catStmt->execute([$userId]);
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Products & Services';
$activeNav = 'products';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Products & Services Catalog</h1>
        <div class="page-subtitle">Manage reusable items, subscription editions, hourly rates, and SAC tax codes.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/products/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Product / Service
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="" style="display: flex; gap: 12px; width: 100%; flex-wrap: wrap;">
        <div class="filter-input">
            <input type="text" name="search" class="form-control" placeholder="Search by name, SKU, HSN/SAC, description..." value="<?= e($search) ?>">
        </div>
        <div style="min-width: 180px;">
            <select name="category" class="form-control" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= ($category === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Search</button>
        <?php if ($search !== '' || $category !== ''): ?>
            <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Item Name & Description</th>
                    <th>SKU / HSN-SAC</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th>Default Price</th>
                    <th>Tax Rate</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <i class="fas fa-cubes" style="font-size: 32px; opacity: 0.4; margin-bottom: 10px; display: block;"></i>
                            No products or services found. Click "+ Add Product / Service" to build your catalog.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600; font-size: 14px;"><?= e($p['name']) ?></div>
                                <?php if (!empty($p['description'])): ?>
                                    <div style="font-size: 12px; color: var(--text-muted); max-width: 450px;"><?= e(substr($p['description'], 0, 90)) ?>...</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><code><?= e($p['sku'] ?: '—') ?></code></div>
                                <?php if (!empty($p['hsn_sac'])): ?>
                                    <div style="font-size: 11px; color: var(--text-muted);">HSN: <?= e($p['hsn_sac']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-draft"><?= e($p['category'] ?: 'General') ?></span>
                            </td>
                            <td><?= e($p['unit'] ?: 'Nos') ?></td>
                            <td style="font-weight: 700; color: var(--text-primary);">
                                <?= format_currency($p['unit_price']) ?>
                            </td>
                            <td><?= (float)$p['default_tax_rate'] ?>% GST</td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL ?>/products/edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/products/duplicate.php?id=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Duplicate">
                                        <i class="fas fa-copy"></i>
                                    </a>
                                    <form method="POST" action="<?= BASE_URL ?>/products/delete.php" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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
