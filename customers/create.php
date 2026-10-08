<?php
/**
 * Quotation Studio - Add Customer
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

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
                INSERT INTO customers (
                    user_id, name, company_name, contact_person, email, phone,
                    billing_address, shipping_address, city, state, country, pincode,
                    gstin, pan, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $userId, $name, $companyName, $contactPerson, $email, $phone,
                $billingAddress, $shippingAddress, $city, $state, $country, $pincode,
                $gstin, $pan, $notes
            ]);

            flash_set('success', 'Customer added successfully.');
            header("Location: " . BASE_URL . "/customers/index.php");
            exit;
        }
    }
}

$pageTitle = 'Add Customer';
$activeNav = 'customers';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add New Customer</h1>
        <div class="page-subtitle">Register a client account with tax IDs and address for instant proposal selection.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/customers/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Customers
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
                    <input type="text" name="company_name" id="company_name" class="form-control" placeholder="e.g. Apex Motors Pvt. Ltd." value="<?= e($_POST['company_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="name">Primary Contact Name <span class="required">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Amit Agarwal" required value="<?= e($_POST['name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="contact_person">Designation / Title</label>
                    <input type="text" name="contact_person" id="contact_person" class="form-control" placeholder="e.g. IT Head / Director" value="<?= e($_POST['contact_person'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="client@company.com" value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone / Mobile</label>
                    <input type="text" name="phone" id="phone" class="form-control" placeholder="+91 98290 12345" value="<?= e($_POST['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="gstin">GSTIN (India)</label>
                    <input type="text" name="gstin" id="gstin" class="form-control" placeholder="08AAACA1111A1Z1" value="<?= e($_POST['gstin'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="pan">PAN</label>
                    <input type="text" name="pan" id="pan" class="form-control" placeholder="AAACA1111A" value="<?= e($_POST['pan'] ?? '') ?>">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="billing_address">Billing Address</label>
                    <textarea name="billing_address" id="billing_address" class="form-control" rows="2" placeholder="Full registered street address"><?= e($_POST['billing_address'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="city">City</label>
                    <input type="text" name="city" id="city" class="form-control" placeholder="Jaipur" value="<?= e($_POST['city'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="state">State</label>
                    <input type="text" name="state" id="state" class="form-control" placeholder="Rajasthan" value="<?= e($_POST['state'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="pincode">PIN / ZIP</label>
                    <input type="text" name="pincode" id="pincode" class="form-control" placeholder="302015" value="<?= e($_POST['pincode'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="country">Country</label>
                    <input type="text" name="country" id="country" class="form-control" value="<?= e($_POST['country'] ?? 'India') ?>">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="notes">Internal Client Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Private internal notes regarding customer preference..."><?= e($_POST['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="card-footer" style="text-align: right;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save Customer
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
