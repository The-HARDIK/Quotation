<?php
/**
 * Quotation Studio - Edit Customer
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$customer = $stmt->fetch();

if (!$customer) {
    flash_set('error', 'Customer not found.');
    header("Location: " . BASE_URL . "/customers/index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $contactPerson = trim($_POST['contact_person'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $billingAddress = trim($_POST['billing_address'] ?? '');
        $shippingAddress = trim($_POST['shipping_address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $country = trim($_POST['country'] ?? 'India');
        $pincode = trim($_POST['pincode'] ?? '');
        $gstin = trim($_POST['gstin'] ?? '');
        $pan = trim($_POST['pan'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($name) && empty($companyName)) {
            $error = 'Customer name or company name is required.';
        } else {
            if (empty($name)) $name = $companyName;

            $stmt = $pdo->prepare("
                UPDATE customers SET
                    name = ?, company_name = ?, contact_person = ?, email = ?, phone = ?,
                    billing_address = ?, shipping_address = ?, city = ?, state = ?, country = ?,
                    pincode = ?, gstin = ?, pan = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");

            $stmt->execute([
                $name, $companyName, $contactPerson, $email, $phone,
                $billingAddress, $shippingAddress, $city, $state, $country,
                $pincode, $gstin, $pan, $notes, $id, $userId
            ]);

            flash_set('success', 'Customer updated successfully.');
            header("Location: " . BASE_URL . "/customers/view.php?id=" . $id);
            exit;
        }
    }
}

$pageTitle = 'Edit ' . ($customer['company_name'] ?: $customer['name']);
$activeNav = 'customers';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Customer</h1>
        <div class="page-subtitle"><?= e($customer['company_name'] ?: $customer['name']) ?></div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/customers/view.php?id=<?= $id ?>" class="btn btn-secondary">
            <i class="fas fa-eye"></i> View Profile
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
                <div class="form-group">
                    <label class="form-label" for="company_name">Company / Organization Name</label>
                    <input type="text" name="company_name" id="company_name" class="form-control" value="<?= e($customer['company_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Primary Contact Name <span class="required">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" required value="<?= e($customer['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="contact_person">Designation / Title</label>
                    <input type="text" name="contact_person" id="contact_person" class="form-control" value="<?= e($customer['contact_person'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= e($customer['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone / Mobile</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="<?= e($customer['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="gstin">GSTIN (India)</label>
                    <input type="text" name="gstin" id="gstin" class="form-control" value="<?= e($customer['gstin'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="pan">PAN</label>
                    <input type="text" name="pan" id="pan" class="form-control" value="<?= e($customer['pan'] ?? '') ?>">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="billing_address">Billing Address</label>
                    <textarea name="billing_address" id="billing_address" class="form-control" rows="2"><?= e($customer['billing_address'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="city">City</label>
                    <input type="text" name="city" id="city" class="form-control" value="<?= e($customer['city'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="state">State</label>
                    <input type="text" name="state" id="state" class="form-control" value="<?= e($customer['state'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="pincode">PIN / ZIP</label>
                    <input type="text" name="pincode" id="pincode" class="form-control" value="<?= e($customer['pincode'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="country">Country</label>
                    <input type="text" name="country" id="country" class="form-control" value="<?= e($customer['country'] ?? 'India') ?>">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="notes">Internal Client Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2"><?= e($customer['notes'] ?? '') ?></textarea>
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
