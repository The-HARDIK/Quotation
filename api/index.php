<?php
/**
 * Quotation Studio - Vercel Serverless Entry Point & Front Controller
 */

$root = dirname(__DIR__);
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Clean up URI
$uri = '/' . ltrim($uri, '/');

// 1. Root / Default
if ($uri === '/' || $uri === '/index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    require $root . '/index.php';
    exit;
}

// 2. Direct static file handling (fallback if not served by Vercel static route)
$targetPath = $root . $uri;

if (str_starts_with($uri, '/public/') && file_exists($targetPath) && is_file($targetPath)) {
    $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    $mimes = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf'
    ];
    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
        header('Cache-Control: public, max-age=86400');
        readfile($targetPath);
        exit;
    }
}

// 3. Direct PHP file execution (e.g., /login.php, /quotations/create.php, /api/customer.php)
if (file_exists($targetPath) && is_file($targetPath) && str_ends_with($targetPath, '.php')) {
    $_SERVER['SCRIPT_NAME'] = $uri;
    $_SERVER['PHP_SELF'] = $uri;
    require $targetPath;
    exit;
}

// 4. Directory index (e.g., /quotations/ -> /quotations/index.php)
if (is_dir($targetPath) && file_exists($targetPath . '/index.php')) {
    $_SERVER['SCRIPT_NAME'] = rtrim($uri, '/') . '/index.php';
    $_SERVER['PHP_SELF'] = rtrim($uri, '/') . '/index.php';
    require $targetPath . '/index.php';
    exit;
}

// 5. Query without .php extension (e.g., /dashboard -> /dashboard.php)
if (file_exists($targetPath . '.php') && is_file($targetPath . '.php')) {
    $_SERVER['SCRIPT_NAME'] = $uri . '.php';
    $_SERVER['PHP_SELF'] = $uri . '.php';
    require $targetPath . '.php';
    exit;
}

// 6. Fallback to index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
require $root . '/index.php';
