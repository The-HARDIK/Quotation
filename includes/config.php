<?php
/**
 * Quotation Studio - Global Configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    if (!empty(getenv('VERCEL')) || !is_writable(session_save_path() ?: sys_get_temp_dir())) {
        @ini_set('session.save_path', sys_get_temp_dir());
    }
    session_start();
}

// Application Constants
define('APP_NAME', 'Quotation Studio');
define('APP_VERSION', '1.0.5');
define('APP_TAGLINE', 'Enterprise Quotation & Commercial Proposal Management');

// Base Paths
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');

// Database Credentials
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'quotation_studio');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// SQLite Database Path (with automated serverless / Vercel fallback)
$defaultSqlite = BASE_PATH . '/database/quotation_studio.sqlite';
if (!empty(getenv('VERCEL')) || (file_exists($defaultSqlite) && !is_writable($defaultSqlite))) {
    $tmpSqlite = sys_get_temp_dir() . '/quotation_studio.sqlite';
    if (!file_exists($tmpSqlite) && file_exists($defaultSqlite)) {
        @copy($defaultSqlite, $tmpSqlite);
    }
    define('SQLITE_PATH', $tmpSqlite);
} else {
    define('SQLITE_PATH', $defaultSqlite);
}

// Automatic Base URL Detection
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
$protocol = $isHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Normalize root path
$projectRoot = preg_replace('#/(quotations|customers|products|invoices|payments|reports|settings|templates|public|api).*$#', '', $scriptDir);
if ($projectRoot === '/' || $projectRoot === '\\') $projectRoot = '';
$baseUrl = rtrim($protocol . $host . $projectRoot, '/');
if (empty($baseUrl) || $baseUrl === 'http://' || $baseUrl === 'https://') {
    $baseUrl = 'http://localhost:8000';
}
define('BASE_URL', $baseUrl);

// System Defaults
define('DEFAULT_CURRENCY', 'INR');
define('DEFAULT_CURRENCY_SYMBOL', '₹');
define('DEFAULT_GST_RATE', 18.0);
define('TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set(TIMEZONE);

// Error Handling (Set to false in production)
define('APP_DEBUG', true);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
