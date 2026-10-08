<?php
/**
 * Quotation Studio - Main Entry Point
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit;
} else {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
