<?php
/**
 * Quotation Studio - Business Registration Screen
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $name = $_POST['name'] ?? '';
        $companyName = $_POST['company_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $res = register_user($name, $email, $password, $companyName);

        if ($res['success']) {
            flash_set('success', 'Your business account was registered successfully! Welcome to Quotation Studio.');
            header("Location: " . BASE_URL . "/dashboard.php");
            exit;
        } else {
            $error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Business — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= APP_VERSION ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: radial-gradient(circle at top right, #134e62, #082834);
            padding: 30px 20px;
        }
        .auth-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 480px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        }
        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            justify-content: center;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="auth-brand">
        <div class="sidebar-logo">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div>
            <div style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: var(--text-primary); line-height: 1.2;">
                <?= APP_NAME ?>
            </div>
            <div style="font-size: 12px; color: var(--text-muted);">Create your quotation workspace</div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="company_name">Company / Business Name <span class="required">*</span></label>
            <input type="text" name="company_name" id="company_name" class="form-control" placeholder="e.g. Acme Tech Solutions Pvt Ltd" required value="<?= e($_POST['company_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="name">Your Full Name <span class="required">*</span></label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. John Doe" required value="<?= e($_POST['name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Work Email Address <span class="required">*</span></label>
            <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Choose Password <span class="required">*</span></label>
            <input type="password" name="password" id="password" class="form-control" placeholder="At least 6 characters" minlength="6" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;">
            <i class="fas fa-rocket"></i> Create Account & Start Quoting
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; font-size: 13px; color: var(--text-muted);">
        Already registered? <a href="<?= BASE_URL ?>/login.php" style="font-weight: 600;">Sign in here</a>
    </div>
</div>

</body>
</html>
