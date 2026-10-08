<?php
/**
 * Quotation Studio - Authentication & Session Management
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function require_auth(): void {
    if (!is_logged_in()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header("Location: " . BASE_URL . "/login.php?redirect=" . $redirect);
        exit;
    }
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_user(): ?array {
    $userId = current_user_id();
    if (!$userId) return null;

    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function current_business(): ?array {
    $userId = current_user_id();
    if (!$userId) return null;

    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM business_profiles WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $biz = $stmt->fetch();

    if (!$biz) {
        // Create an empty initial profile if missing
        $stmt = $pdo->prepare("
            INSERT INTO business_profiles (user_id, company_name, email)
            VALUES (?, 'My Business', ?)
        ");
        $user = current_user();
        $stmt->execute([$userId, $user['email'] ?? '']);
        $bizId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare("SELECT * FROM business_profiles WHERE id = ?");
        $stmt->execute([$bizId]);
        $biz = $stmt->fetch();
    }

    return $biz;
}

function login_user(string $email, string $password): array {
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Please provide both email and password.'];
    }

    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email address or password.'];
    }

    // Set session
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    return ['success' => true, 'user' => $user];
}

function register_user(string $name, string $email, string $password, string $companyName = ''): array {
    $name = trim($name);
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    $companyName = trim($companyName);

    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Please complete all required fields.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }

    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'An account with this email address already exists.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    $stmt->execute([$name, $email, $hash]);
    $userId = (int)$pdo->lastInsertId();

    // Create Business Profile
    $company = $companyName ?: ($name . ' Enterprises');
    $bizStmt = $pdo->prepare("
        INSERT INTO business_profiles (
            user_id, company_name, email, primary_color, secondary_color, numbering_prefix
        ) VALUES (?, ?, ?, '#0d5c75', '#85a438', 'QT')
    ");
    $bizStmt->execute([$userId, $company, $email]);

    // Log the user in
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = 'admin';

    return ['success' => true, 'user_id' => $userId];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

// CSRF Protection
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): bool {
    $token = $token ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
