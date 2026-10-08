<?php
/**
 * Quotation Studio - Login Screen
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
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $res = login_user($email, $password);

        if ($res['success']) {
            $redirect = $_GET['redirect'] ?? (BASE_URL . '/dashboard.php');
            header("Location: " . $redirect);
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
    <title>Sign In — <?= APP_NAME ?></title>
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
            padding: 20px;
        }
        .auth-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 440px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
        }
        .auth-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
            justify-content: center;
        }
        .demo-credential-box {
            background: var(--bg-alt);
            border: 1px dashed #cbd5e1;
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 12.5px;
            color: var(--text-secondary);
        }
        .demo-fill-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
            margin-left: 6px;
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
            <div style="font-size: 12px; color: var(--text-muted);"><?= APP_TAGLINE ?></div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <div class="demo-credential-box">
        <strong>Demo Account:</strong>
        <div>Email: <code>admin@quotationstudio.com</code></div>
        <div>Password: <code>password123</code></div>
        <button type="button" class="demo-fill-btn" onclick="fillDemo()">Auto-Fill Demo Credentials</button>
    </div>

    <form method="POST" action="">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" for="email">Work Email Address</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <label class="form-label" for="password">Password</label>
            </div>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;">
            <i class="fas fa-sign-in-alt"></i> Sign In to Quotation Studio
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; font-size: 13px; color: var(--text-muted);">
        Don't have an account? <a href="<?= BASE_URL ?>/register.php" style="font-weight: 600;">Register New Business</a>
    </div>
</div>

<script>
function fillDemo() {
    document.getElementById('email').value = 'admin@quotationstudio.com';
    document.getElementById('password').value = 'password123';
}
</script>

</body>
</html>
