<?php
/**
 * Quotation Studio - Delete Product
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash_set('error', 'Invalid token.');
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $userId = current_user_id();

        $pdo = get_db();
        $stmt = $pdo->prepare("DELETE FROM products_services WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        flash_set('success', 'Product deleted successfully.');
    }
}

header("Location: " . BASE_URL . "/products/index.php");
exit;
