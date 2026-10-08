<?php
/**
 * Quotation Studio - Invoice API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = get_db();
$userId = current_user_id();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $inv = $stmt->fetch();
        if ($inv) {
            $itStmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
            $itStmt->execute([$id]);
            $inv['items'] = $itStmt->fetchAll();
            echo json_encode(['success' => true, 'invoice' => $inv]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invoice not found']);
        }
    } else {
        $stmt = $pdo->prepare("SELECT id, invoice_number, grand_total, balance_due, status FROM invoices WHERE user_id = ? ORDER BY id DESC LIMIT 50");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true, 'invoices' => $stmt->fetchAll()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
