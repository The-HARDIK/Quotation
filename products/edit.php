<?php
/**
 * Quotation Studio - Edit Product or Service
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM products_services WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$product = $stmt->fetch();

if (!$product) {
    flash_set('error', 'Product/Service not found.');
    header("Location: " . BASE_URL . "/products/index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $hsnSac = trim($_POST['hsn_sac'] ?? '');
        $unit = trim($_POST['unit'] ?? 'Nos');
        $unitPrice = (float)($_POST['unit_price'] ?? 0);
        $defaultDiscount = (float)($_POST['default_discount'] ?? 0);
        $defaultTaxRate = (float)($_POST['default_tax_rate'] ?? 18);
        $category = trim($_POST['category'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name)) {
            $error = 'Product or service name is required.';
        } else {
            $stmt = $pdo->prepare("
                UPDATE products_services SET
                    name = ?, sku = ?, description = ?, hsn_sac = ?, unit = ?,
                    unit_price = ?, default_discount = ?, default_tax_rate = ?,
                    category = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");

            $stmt->execute([
                $name, $sku, $description, $hsnSac, $unit,
                $unitPrice, $defaultDiscount, $defaultTaxRate,
                $category, $notes, $id, $userId
            ]);

            flash_set('success', 'Product/Service updated successfully.');
            header("Location: " . BASE_URL . "/products/index.php");
            exit;
        }
    }
}

$pageTitle = 'Edit ' . $product['name'];
$activeNav = 'products';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Product / Service</h1>
        <div class="page-subtitle"><?= e($product['name']) ?></div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/products/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Catalog
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width: 900px;">
    <form method="POST" action="">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="name">Product / Service Name <span class="required">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required value="<?= e($product['name']) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="sku">SKU / Item Code</label>
                    <input type="text" name="sku" id="sku" class="form-control" value="<?= e($product['sku'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <input type="text" name="category" id="category" class="form-control" value="<?= e($product['category'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="hsn_sac">HSN / SAC Code</label>
                    <input type="text" name="hsn_sac" id="hsn_sac" class="form-control" value="<?= e($product['hsn_sac'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="unit">Billing Unit</label>
                    <input type="text" name="unit" id="unit" class="form-control" value="<?= e($product['unit'] ?? 'Nos') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="unit_price">Default Unit Rate (₹) <span class="required">*</span></label>
                    <input type="number" step="0.01" name="unit_price" id="unit_price" class="form-control" required value="<?= (float)$product['unit_price'] ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="default_tax_rate">Default Tax Rate (% GST)</label>
                    <select name="default_tax_rate" id="default_tax_rate" class="form-control">
                        <option value="18" <?= ((float)$product['default_tax_rate'] == 18) ? 'selected' : '' ?>>18% GST (Standard Services)</option>
                        <option value="12" <?= ((float)$product['default_tax_rate'] == 12) ? 'selected' : '' ?>>12% GST</option>
                        <option value="5" <?= ((float)$product['default_tax_rate'] == 5) ? 'selected' : '' ?>>5% GST</option>
                        <option value="28" <?= ((float)$product['default_tax_rate'] == 28) ? 'selected' : '' ?>>28% GST</option>
                        <option value="0" <?= ((float)$product['default_tax_rate'] == 0) ? 'selected' : '' ?>>0% (Exempt)</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="description">Detailed Description / Scope Summary</label>
                    <textarea name="description" id="description" class="form-control" rows="3"><?= e($product['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="notes">Technical Notes / Prerequisites</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2"><?= e($product['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer" style="text-align: right;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
