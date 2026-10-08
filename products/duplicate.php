<?php
/**
 * Quotation Studio - Duplicate Product
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$id = (int)($_GET['id'] ?? 0);
$userId = current_user_id();
$pdo = get_db();

$stmt = $pdo->prepare("SELECT * FROM products_services WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$prod = $stmt->fetch();

if ($prod) {
    $insStmt = $pdo->prepare("
        INSERT INTO products_services (
            user_id, name, sku, description, hsn_sac, unit, unit_price,
            default_discount, default_tax_rate, category, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insStmt->execute([
        $userId,
        $prod['name'] . ' (Copy)',
        $prod['sku'] ? $prod['sku'] . '-COPY' : null,
        $prod['description'],
        $prod['hsn_sac'],
        $prod['unit'],
        $prod['unit_price'],
        $prod['default_discount'],
        $prod['default_tax_rate'],
        $prod['category'],
        $prod['notes']
    ]);
    flash_set('success', 'Product duplicated successfully.');
} else {
    flash_set('error', 'Product not found.');
}

header("Location: " . BASE_URL . "/products/index.php");
exit;
