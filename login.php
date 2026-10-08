<?php
/**
 * Quotation Studio - Login Screen (Split-Screen Modern SaaS Edition)
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
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/tokens.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= APP_VERSION ?>">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #090e17;
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            font-family: var(--font-sans);
        }
        .split-layout {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            width: 100vw;
            min-height: 100vh;
        }
        @media (max-width: 992px) {
            .split-layout {
                grid-template-columns: 1fr;
            }
            .brand-showcase-panel {
                display: none !important;
            }
        }
        .brand-showcase-panel {
            background: radial-gradient(circle at 20% 20%, rgba(13, 92, 117, 0.45) 0%, transparent 60%),
                        radial-gradient(circle at 80% 80%, rgba(99, 102, 241, 0.35) 0%, transparent 60%),
                        linear-gradient(135deg, #09131d 0%, #0d1e2c 100%);
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }
        .floating-mock-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: var(--radius-md);
            padding: 20px 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            color: #ffffff;
            margin-bottom: 20px;
            max-width: 480px;
            animation: floatSlow 6s ease-in-out infinite alternate;
        }
        @keyframes floatSlow {
            from { transform: translateY(0px); }
            to { transform: translateY(-8px); }
        }
        .form-side-panel {
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 30px;
        }
        .auth-box {
            width: 100%;
            max-width: 420px;
        }
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
        }
    </style>
</head>
<body>

<div class="split-layout">
    <!-- Left Showcase Panel -->
    <div class="brand-showcase-panel">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div class="sidebar-logo" style="width: 42px; height: 42px; font-size: 20px;">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div>
                <div style="font-family: var(--font-heading); font-size: 20px; font-weight: 800; color: #ffffff;"><?= APP_NAME ?></div>
                <div style="font-size: 11.5px; color: #94a3b8;"><?= APP_TAGLINE ?></div>
            </div>
        </div>

        <!-- Floating Mock Cards -->
        <div style="margin: 40px 0;">
            <div class="floating-mock-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #38bdf8; letter-spacing: 0.5px;">Quotation Accepted</span>
                    <span class="badge badge-accepted" style="font-size: 10.5px;">PIPL/26-27/UiPrime/01</span>
                </div>
                <div style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">Apex Motors Private Limited</div>
                <div style="font-family: var(--font-mono); font-size: 22px; font-weight: 800; color: #34d399;">₹ 2,47,800.00</div>
                <div style="font-size: 11.5px; color: #94a3b8; margin-top: 8px;">
                    <i class="fas fa-signature" style="color: #38bdf8; margin-right: 4px;"></i> Digitally e-signed by Mr. Amit Agarwal (IT Head)
                </div>
            </div>

            <div class="floating-mock-card" style="margin-left: 40px; animation-delay: -3s;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 12px; font-weight: 600; color: #e2e8f0;">Live 3-Page A4 Proposals</span>
                    <span style="color: #fbbf24; font-size: 11px;"><i class="fas fa-bolt"></i> Real-time CAD</span>
                </div>
                <div style="font-size: 12px; color: #94a3b8;">
                    Dynamic calculations, GST formulation, milestone payments, and multi-template generation built in.
                </div>
            </div>
        </div>

        <div style="font-size: 12px; color: #64748b; display: flex; justify-content: space-between;">
            <span>&copy; <?= date('Y') ?> <?= APP_NAME ?> Inc.</span>
            <span>Enterprise B2B Security</span>
        </div>
    </div>

    <!-- Right Login Panel -->
    <div class="form-side-panel">
        <div class="auth-box">
            <h2 style="font-family: var(--font-heading); font-size: 24px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; letter-spacing: -0.5px;">
                Welcome back
            </h2>
            <p style="font-size: 13.5px; color: var(--text-muted); margin-bottom: 24px;">
                Sign in to manage your quotations, invoices, and clients.
            </p>

            <?php if ($error): ?>
                <div style="background: var(--status-rejected-bg); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px 16px; border-radius: var(--radius-sm); font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-circle-exclamation"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Quick Auto-Fill Demo Card -->
            <div style="background: var(--surface-subtle); border: 1px dashed var(--border-strong); border-radius: var(--radius-md); padding: 12px 16px; margin-bottom: 22px; font-size: 12.5px; color: var(--text-secondary);">
                <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">Demo Credentials:</div>
                <div style="font-family: var(--font-mono); font-size: 12px;">admin@quotationstudio.com / password123</div>
                <button type="button" onclick="fillDemo()" style="background: none; border: none; color: var(--primary); font-weight: 700; font-size: 12px; cursor: pointer; text-decoration: underline; padding: 0; margin-top: 6px;">
                    Auto-fill demo credentials
                </button>
            </div>

            <form method="POST" action="">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="email">Work Email</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="name@company.com" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
                </div>

                <div class="form-group" style="position: relative;">
                    <label class="form-label" for="password">Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" class="form-control" style="padding-right: 40px;" placeholder="••••••••" required>
                        <button type="button" class="password-toggle-btn" id="togglePasswordBtn" title="Show/Hide password">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;">
                    Sign In
                </button>
            </form>

            <div style="text-align: center; margin-top: 24px; font-size: 13px; color: var(--text-muted);">
                Don't have an account? <a href="<?= BASE_URL ?>/register.php" style="color: var(--primary); font-weight: 700; text-decoration: none;">Register New Business</a>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemo() {
    document.getElementById('email').value = 'admin@quotationstudio.com';
    document.getElementById('password').value = 'password123';
}

document.getElementById('togglePasswordBtn')?.addEventListener('click', () => {
    const input = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
});
</script>

</body>
</html>
