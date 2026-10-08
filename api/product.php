<?php
/**
 * Quotation Studio - Products JSON API
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products_services WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $product = $stmt->fetch();
    json_response(['success' => (bool)$product, 'product' => $product]);
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("
        SELECT id, name, sku, hsn_sac, unit, unit_price, default_tax_rate, default_discount, description
        FROM products_services
        WHERE user_id = ? AND (name LIKE ? OR sku LIKE ?)
        LIMIT 20
    ");
    $like = "%{$search}%";
    $stmt->execute([$userId, $like, $like]);
    json_response(['success' => true, 'products' => $stmt->fetchAll()]);
}

$stmt = $pdo->prepare("SELECT id, name, sku, hsn_sac, unit, unit_price, default_tax_rate, default_discount, description FROM products_services WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$userId]);
json_response(['success' => true, 'products' => $stmt->fetchAll()]);
