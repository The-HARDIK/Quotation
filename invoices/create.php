<?php
/**
 * Quotation Studio - Create Direct Tax Invoice
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$biz = current_business();

// Fetch Customers & Products
$custStmt = $pdo->prepare("SELECT id, name, company_name, contact_person, email, phone, billing_address, gstin FROM customers WHERE user_id = ? ORDER BY name ASC");
$custStmt->execute([$userId]);
$customers = $custStmt->fetchAll();

$prodStmt = $pdo->prepare("SELECT id, name, sku, hsn_sac, unit, unit_price, default_tax_rate FROM products_services WHERE user_id = ? ORDER BY name ASC");
$prodStmt->execute([$userId]);
$products = $prodStmt->fetchAll();

$invCountStmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE user_id = ?");
$invCountStmt->execute([$userId]);
$invSeq = (int)$invCountStmt->fetchColumn() + 1;
$defaultInvNumber = 'INV-' . date('Y') . '-' . str_pad((string)$invSeq, 3, '0', STR_PAD_LEFT);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid token.';
    } else {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $invNumber = trim($_POST['invoice_number'] ?? $defaultInvNumber);
        $issueDate = trim($_POST['issue_date'] ?? date('Y-m-d'));
        $dueDate = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+15 days')));
        $notes = trim($_POST['notes'] ?? '');

        // Line items from POST
        $descriptions = $_POST['item_desc'] ?? [];
        $hsnList = $_POST['item_hsn'] ?? [];
        $quantities = $_POST['item_qty'] ?? [];
        $rates = $_POST['item_rate'] ?? [];

        if (!$customerId) {
            $error = 'Please select a customer.';
        } elseif (empty($descriptions)) {
            $error = 'At least one line item is required.';
        } else {
            // Find customer
            $cStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $cStmt->execute([$customerId]);
            $cust = $cStmt->fetch();

            $subtotal = 0;
            $itemsData = [];
            for ($i = 0; $i < count($descriptions); $i++) {
                $desc = trim($descriptions[$i]);
                if (empty($desc)) continue;
                $qty = (float)($quantities[$i] ?? 1);
                $rate = (float)($rates[$i] ?? 0);
                $line = $qty * $rate;
                $subtotal += $line;
                $itemsData[] = [
                    'description' => $desc,
                    'hsn_sac' => $hsnList[$i] ?? '',
                    'quantity' => $qty,
                    'unit' => 'Nos',
                    'unit_price' => $rate,
                    'discount_percent' => 0,
                    'tax_rate' => 18.0,
                    'line_total' => $line
                ];
            }

            $gstAmount = round($subtotal * 0.18, 2);
            $cgst = round($gstAmount / 2, 2);
            $sgst = round($gstAmount / 2, 2);
            $grandTotal = round($subtotal + $gstAmount);
            $roundOff = round($grandTotal - ($subtotal + $gstAmount), 2);

            $pdo->beginTransaction();
            try {
                $ins = $pdo->prepare("
                    INSERT INTO invoices (
                        user_id, customer_id, invoice_number, issue_date, due_date, status,
                        subtotal, total_discount, taxable_amount, cgst_amount, sgst_amount,
                        igst_amount, round_off, grand_total, paid_amount, balance_due,
                        notes, customer_snapshot, business_snapshot
                    ) VALUES (
                        ?, ?, ?, ?, ?, 'Unpaid',
                        ?, 0.00, ?, ?, ?,
                        0.00, ?, ?, 0.00, ?,
                        ?, ?, ?
                    )
                ");
                $ins->execute([
                    $userId, $customerId, $invNumber, $issueDate, $dueDate,
                    $subtotal, $subtotal, $cgst, $sgst,
                    $roundOff, $grandTotal, $grandTotal,
                    $notes, json_encode($cust), json_encode($biz)
                ]);
                $invId = (int)$pdo->lastInsertId();

                $insIt = $pdo->prepare("
                    INSERT INTO invoice_items (invoice_id, description, hsn_sac, quantity, unit, unit_price, tax_rate, line_total)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($itemsData as $row) {
                    $insIt->execute([$invId, $row['description'], $row['hsn_sac'], $row['quantity'], $row['unit'], $row['unit_price'], $row['tax_rate'], $row['line_total']]);
                }

                $pdo->commit();
                flash_set('success', 'Tax Invoice created successfully.');
                header("Location: " . BASE_URL . "/invoices/view.php?id=" . $invId);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Error creating invoice: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Create Direct Invoice';
$activeNav = 'invoices';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Create Direct Tax Invoice</h1>
        <div class="page-subtitle">Issue standalone invoices directly without prior quotation.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/invoices/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
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
                <div class="form-group">
                    <label class="form-label">Customer <span class="required">*</span></label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">-- Choose Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['company_name'] ?: $c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Invoice Number <span class="required">*</span></label>
                    <input type="text" name="invoice_number" class="form-control" value="<?= e($defaultInvNumber) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Issue Date</label>
                    <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+15 days')) ?>">
                </div>
            </div>

            <div style="font-weight: 700; margin: 18px 0 10px;">Line Items</div>
            <div id="directItems">
                <div style="display: grid; grid-template-columns: 3fr 1fr 1fr 1fr; gap: 10px; margin-bottom: 8px;">
                    <input type="text" name="item_desc[]" class="form-control" placeholder="Item description" required>
                    <input type="text" name="item_hsn[]" class="form-control" placeholder="HSN/SAC">
                    <input type="number" step="0.1" name="item_qty[]" class="form-control" placeholder="Qty" value="1" required>
                    <input type="number" step="0.01" name="item_rate[]" class="form-control" placeholder="Rate (₹)" required>
                </div>
            </div>

            <div class="form-group" style="margin-top: 18px;">
                <label class="form-label">Notes & Payment Instructions</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Payment due within 15 days..."></textarea>
            </div>
        </div>
        <div class="card-footer" style="text-align: right;">
            <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Generate Tax Invoice</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
