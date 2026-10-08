<?php
/**
 * Quotation Studio - Business Profile Settings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$biz = current_business();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Security token invalid. Please try again.';
    } else {
        $companyName = trim($_POST['company_name'] ?? '');
        $tagline = trim($_POST['tagline'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $country = trim($_POST['country'] ?? 'India');
        $pincode = trim($_POST['pincode'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $gstin = trim($_POST['gstin'] ?? '');
        $pan = trim($_POST['pan'] ?? '');
        $registrationNo = trim($_POST['registration_no'] ?? '');

        $contactPerson = trim($_POST['contact_person'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $signatoryName = trim($_POST['signatory_name'] ?? '');
        $signatoryDesignation = trim($_POST['signatory_designation'] ?? '');

        $bankName = trim($_POST['bank_name'] ?? '');
        $accountName = trim($_POST['account_name'] ?? '');
        $accountNumber = trim($_POST['account_number'] ?? '');
        $ifscCode = trim($_POST['ifsc_code'] ?? '');
        $branch = trim($_POST['branch'] ?? '');
        $upiId = trim($_POST['upi_id'] ?? '');

        // Handle File Uploads
        $logoUrl = $biz['logo_url'] ?? null;
        if (!empty($_FILES['logo']['tmp_name'])) {
            try {
                $uploaded = handle_file_upload($_FILES['logo'], 'logos');
                if ($uploaded) $logoUrl = $uploaded;
            } catch (Exception $e) {
                $error = 'Logo upload error: ' . $e->getMessage();
            }
        }

        $signatureUrl = $biz['signature_url'] ?? null;
        if (!empty($_FILES['signature']['tmp_name'])) {
            try {
                $uploadedSig = handle_file_upload($_FILES['signature'], 'signatures');
                if ($uploadedSig) $signatureUrl = $uploadedSig;
            } catch (Exception $e) {
                $error = 'Signature upload error: ' . $e->getMessage();
            }
        }

        if (empty($companyName)) {
            $error = 'Company name is required.';
        }

        if (!$error) {
            $stmt = $pdo->prepare("
                UPDATE business_profiles SET
                    company_name = ?, tagline = ?, logo_url = ?, address = ?, city = ?, state = ?,
                    country = ?, pincode = ?, phone = ?, email = ?, website = ?, gstin = ?, pan = ?,
                    registration_no = ?, contact_person = ?, designation = ?, signatory_name = ?,
                    signatory_designation = ?, signature_url = ?, bank_name = ?, account_name = ?,
                    account_number = ?, ifsc_code = ?, branch = ?, upi_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");

            $stmt->execute([
                $companyName, $tagline, $logoUrl, $address, $city, $state,
                $country, $pincode, $phone, $email, $website, $gstin, $pan,
                $registrationNo, $contactPerson, $designation, $signatoryName,
                $signatoryDesignation, $signatureUrl, $bankName, $accountName,
                $accountNumber, $ifscCode, $branch, $upiId, $biz['id'], $userId
            ]);

            flash_set('success', 'Business Profile updated successfully.');
            header("Location: " . BASE_URL . "/settings/business.php");
            exit;
        }
    }
}

$pageTitle = 'Business Profile Settings';
$activeNav = 'settings_business';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Business Profile</h1>
        <div class="page-subtitle">Configure company details, banking information, and authorized signatories.</div>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= e($error) ?></div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- 1. Company Information -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-building" style="color: var(--primary); margin-right: 8px;"></i> Company Information</div>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="company_name">Company Name <span class="required">*</span></label>
                    <input type="text" name="company_name" id="company_name" class="form-control" value="<?= e($biz['company_name'] ?? '') ?>" required>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="tagline">Tagline / Motto</label>
                    <input type="text" name="tagline" id="tagline" class="form-control" value="<?= e($biz['tagline'] ?? '') ?>" placeholder="e.g. Adding Value Across Businesses">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="address">Registered Office Address</label>
                    <textarea name="address" id="address" class="form-control" rows="2"><?= e($biz['address'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="city">City</label>
                    <input type="text" name="city" id="city" class="form-control" value="<?= e($biz['city'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="state">State</label>
                    <input type="text" name="state" id="state" class="form-control" value="<?= e($biz['state'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="pincode">PIN / Postal Code</label>
                    <input type="text" name="pincode" id="pincode" class="form-control" value="<?= e($biz['pincode'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="country">Country</label>
                    <input type="text" name="country" id="country" class="form-control" value="<?= e($biz['country'] ?? 'India') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Phone / Support Numbers</label>
                    <input type="text" name="phone" id="phone" class="form-control" value="<?= e($biz['phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Official Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="<?= e($biz['email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="website">Company Website</label>
                    <input type="url" name="website" id="website" class="form-control" value="<?= e($biz['website'] ?? '') ?>" placeholder="https://">
                </div>

                <div class="form-group">
                    <label class="form-label" for="gstin">GSTIN (India GST)</label>
                    <input type="text" name="gstin" id="gstin" class="form-control" value="<?= e($biz['gstin'] ?? '') ?>" placeholder="e.g. 08AABCP1234F1Z5">
                </div>

                <div class="form-group">
                    <label class="form-label" for="pan">PAN Number</label>
                    <input type="text" name="pan" id="pan" class="form-control" value="<?= e($biz['pan'] ?? '') ?>" placeholder="e.g. AABCP1234F">
                </div>

                <div class="form-group">
                    <label class="form-label" for="registration_no">CIN / Registration No</label>
                    <input type="text" name="registration_no" id="registration_no" class="form-control" value="<?= e($biz['registration_no'] ?? '') ?>">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Company Logo</label>
                    <?php if (!empty($biz['logo_url'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?= BASE_URL ?>/<?= e($biz['logo_url']) ?>" alt="Current Logo" style="max-height: 50px; background: #f8fafc; padding: 4px; border: 1px solid var(--border-color); border-radius: 4px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" id="logo" class="form-control" accept="image/*">
                    <span style="font-size: 11.5px; color: var(--text-muted);">Recommended: Transparent PNG or SVG (max 5MB)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Authorized Signatory & Contact Person -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-signature" style="color: var(--secondary); margin-right: 8px;"></i> Contact Person & Authorized Signatory</div>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="contact_person">Contact Person Name</label>
                    <input type="text" name="contact_person" id="contact_person" class="form-control" value="<?= e($biz['contact_person'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="designation">Designation</label>
                    <input type="text" name="designation" id="designation" class="form-control" value="<?= e($biz['designation'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="signatory_name">Authorized Signatory Name</label>
                    <input type="text" name="signatory_name" id="signatory_name" class="form-control" value="<?= e($biz['signatory_name'] ?? '') ?>" placeholder="Person appearing on proposal closing">
                </div>

                <div class="form-group">
                    <label class="form-label" for="signatory_designation">Signatory Designation</label>
                    <input type="text" name="signatory_designation" id="signatory_designation" class="form-control" value="<?= e($biz['signatory_designation'] ?? '') ?>">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label">Digital Signature Image</label>
                    <?php if (!empty($biz['signature_url'])): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?= BASE_URL ?>/<?= e($biz['signature_url']) ?>" alt="Current Signature" style="max-height: 45px; background: #f8fafc; padding: 4px; border: 1px solid var(--border-color); border-radius: 4px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="signature" id="signature" class="form-control" accept="image/*">
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Banking & Settlement Information -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-university" style="color: var(--primary); margin-right: 8px;"></i> Settlement Bank & UPI Details</div>
        </div>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="bank_name">Bank Name</label>
                    <input type="text" name="bank_name" id="bank_name" class="form-control" value="<?= e($biz['bank_name'] ?? '') ?>" placeholder="e.g. HDFC Bank Ltd.">
                </div>

                <div class="form-group">
                    <label class="form-label" for="account_name">Account Beneficiary Name</label>
                    <input type="text" name="account_name" id="account_name" class="form-control" value="<?= e($biz['account_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="account_number">Bank Account Number</label>
                    <input type="text" name="account_number" id="account_number" class="form-control" value="<?= e($biz['account_number'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="ifsc_code">IFSC Code</label>
                    <input type="text" name="ifsc_code" id="ifsc_code" class="form-control" value="<?= e($biz['ifsc_code'] ?? '') ?>" placeholder="e.g. HDFC0001585">
                </div>

                <div class="form-group">
                    <label class="form-label" for="branch">Bank Branch</label>
                    <input type="text" name="branch" id="branch" class="form-control" value="<?= e($biz['branch'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="upi_id">UPI ID / VPA</label>
                    <input type="text" name="upi_id" id="upi_id" class="form-control" value="<?= e($biz['upi_id'] ?? '') ?>" placeholder="company@bank">
                </div>
            </div>
        </div>
        <div class="card-footer" style="text-align: right;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save Business Profile
            </button>
        </div>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
