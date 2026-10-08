<?php
/**
 * Quotation Studio - Global App Header
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = current_user();
$currentBiz = current_business();
$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Core App CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboard.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/quotation-builder.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/quotation-print.css?v=<?= APP_VERSION ?>" media="print">

    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS Variables for Business Branding -->
    <?php if ($currentBiz): ?>
    <style>
        :root {
            --primary: <?= e($currentBiz['primary_color'] ?: '#0d5c75') ?>;
            --secondary: <?= e($currentBiz['secondary_color'] ?: '#85a438') ?>;
        }
    </style>
    <?php endif; ?>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="app-main">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearchInput" placeholder="Search quotations, customers, products...">
                </div>
            </div>

            <div class="topbar-right">
                <a href="<?= BASE_URL ?>/quotations/create.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> New Quotation
                </a>

                <div class="user-dropdown-wrapper" style="position: relative;">
                    <button type="button" class="btn btn-secondary btn-sm" id="userMenuBtn" style="border-radius: 9999px; padding: 4px 10px; gap: 8px;">
                        <span class="user-avatar-mini" style="width: 26px; height: 26px; font-size: 11px;">
                            <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
                        </span>
                        <span><?= e($currentUser['name'] ?? 'Admin') ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 10px; color: var(--text-muted);"></i>
                    </button>
                    <div id="userMenuDropdown" style="display: none; position: absolute; right: 0; top: 110%; width: 220px; background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-sm); box-shadow: var(--shadow-lg); z-index: 100; padding: 6px 0;">
                        <div style="padding: 10px 14px; border-bottom: 1px solid var(--border-color);">
                            <div style="font-weight: 600; font-size: 13px;"><?= e($currentUser['name'] ?? '') ?></div>
                            <div style="font-size: 11px; color: var(--text-muted);"><?= e($currentUser['email'] ?? '') ?></div>
                        </div>
                        <a href="<?= BASE_URL ?>/settings/business.php" class="nav-link" style="color: var(--text-primary); border-radius: 0;">
                            <i class="fas fa-building"></i> Business Profile
                        </a>
                        <a href="<?= BASE_URL ?>/settings/branding.php" class="nav-link" style="color: var(--text-primary); border-radius: 0;">
                            <i class="fas fa-palette"></i> Branding & Colors
                        </a>
                        <a href="<?= BASE_URL ?>/settings/numbering.php" class="nav-link" style="color: var(--text-primary); border-radius: 0;">
                            <i class="fas fa-hashtag"></i> Quotation Numbering
                        </a>
                        <div style="border-top: 1px solid var(--border-color); margin: 4px 0;"></div>
                        <a href="<?= BASE_URL ?>/logout.php" class="nav-link" style="color: var(--danger); border-radius: 0;">
                            <i class="fas fa-sign-out-alt"></i> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="content-wrapper">
            <?= render_flash() ?>
