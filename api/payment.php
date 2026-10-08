<?php
/**
 * Quotation Studio - Payment API Endpoint
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

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;

    $invoiceId = (int)($data['invoice_id'] ?? 0);
    $amount = (float)($data['amount'] ?? 0);
    $paymentDate = trim($data['payment_date'] ?? date('Y-m-d'));
    $paymentMethod = trim($data['payment_method'] ?? 'Bank Transfer');
    $referenceNumber = trim($data['reference_number'] ?? '');
    $notes = trim($data['notes'] ?? '');

    if ($invoiceId <= 0 || $amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invoice ID and valid positive amount are required.']);
        exit;
    }

    $chk = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
    $chk->execute([$invoiceId, $userId]);
    $invoice = $chk->fetch();

    if (!$invoice) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $payNumber = 'PAY-' . date('Ymd') . '-' . rand(100, 999);
        $ins = $pdo->prepare("
            INSERT INTO payments (user_id, invoice_id, payment_number, payment_date, amount, payment_method, reference_number, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$userId, $invoiceId, $payNumber, $paymentDate, $amount, $paymentMethod, $referenceNumber, $notes]);

        $newPaid = (float)$invoice['paid_amount'] + $amount;
        $newBalance = max(0.0, (float)$invoice['grand_total'] - $newPaid);
        $newStatus = ($newBalance <= 0) ? 'Paid' : 'Partially Paid';

        $up = $pdo->prepare("
            UPDATE invoices SET paid_amount = ?, balance_due = ?, status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        $up->execute([$newPaid, $newBalance, $newStatus, $invoiceId, $userId]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'payment_number' => $payNumber,
            'new_paid' => $newPaid,
            'new_balance' => $newBalance,
            'new_status' => $newStatus
        ]);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

if ($method === 'GET') {
    $invoiceId = (int)($_GET['invoice_id'] ?? 0);
    if ($invoiceId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE invoice_id = ? AND user_id = ? ORDER BY id DESC");
        $stmt->execute([$invoiceId, $userId]);
        echo json_encode(['success' => true, 'payments' => $stmt->fetchAll()]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY id DESC LIMIT 50");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true, 'payments' => $stmt->fetchAll()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
