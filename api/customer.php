<?php
/**
 * Quotation Studio - Customers JSON API
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

// 1. Fetch by ID
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $cust = $stmt->fetch();
    json_response(['success' => (bool)$cust, 'customer' => $cust]);
}

// 2. Quick Create on the fly
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $name = trim($data['name'] ?? '');
    $company = trim($data['company_name'] ?? '');
    $contact = trim($data['contact_person'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $gstin = trim($data['gstin'] ?? '');
    $address = trim($data['billing_address'] ?? '');
    $city = trim($data['city'] ?? '');
    $state = trim($data['state'] ?? '');

    if (empty($name) && empty($company)) {
        json_response(['success' => false, 'message' => 'Customer name or company is required.'], 400);
    }
    if (empty($name)) $name = $company;

    $stmt = $pdo->prepare("
        INSERT INTO customers (user_id, name, company_name, contact_person, email, phone, billing_address, city, state, gstin)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $name, $company, $contact, $email, $phone, $address, $city, $state, $gstin]);
    $newId = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$newId]);
    json_response(['success' => true, 'customer' => $stmt->fetch()]);
}

// 3. List
$stmt = $pdo->prepare("SELECT id, name, company_name, contact_person, email, phone, gstin, city, state, billing_address FROM customers WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$userId]);
json_response(['success' => true, 'customers' => $stmt->fetchAll()]);
