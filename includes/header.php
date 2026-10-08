<?php
/**
 * Quotation Studio - Global App Header & Shell
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
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>

    <!-- Immediate Theme Application Script (Prevents flash of unstyled theme) -->
    <script>
        (function() {
            const saved = localStorage.getItem('qs_theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>

    <!-- Google Fonts: Plus Jakarta Sans for UI + JetBrains Mono for Numbers/Codes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons 6.5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Core App CSS & Design System Tokens -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/tokens.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/dashboard.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/quotation-builder.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/quotation-print.css?v=<?= APP_VERSION ?>" media="print">

    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Business Brand Overrides -->
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
                <!-- Single Unified Navigation Toggle (Collapses desktop, opens mobile) -->
                <button type="button" class="sidebar-toggle-btn" id="sidebarToggle" title="Toggle Navigation">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Cmd+K Command Palette Trigger -->
                <button type="button" class="topbar-search-trigger" id="openCommandPaletteBtn">
                    <i class="fas fa-search"></i>
                    <span>Quick find quotations, clients, actions...</span>
                    <kbd>Ctrl+K</kbd>
                </button>
            </div>

            <div class="topbar-right">
                <!-- Theme Toggle Button (Light / Dark) -->
                <button type="button" class="theme-toggle-btn" id="themeToggleBtn" title="Toggle Light / Dark Mode">
                    <i class="fas fa-moon"></i>
                </button>

                <a href="<?= BASE_URL ?>/quotations/create.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> New Quotation
                </a>

                <!-- User Profile Dropdown -->
                <div class="user-dropdown-wrapper" style="position: relative;">
                    <button type="button" class="btn btn-secondary btn-sm" id="userMenuBtn" style="border-radius: 9999px; padding: 4px 12px; gap: 8px;">
                        <span class="user-avatar-mini" style="width: 24px; height: 24px; font-size: 11px;">
                            <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
                        </span>
                        <span style="font-weight: 600; font-size: 12.5px;"><?= e($currentUser['name'] ?? 'Admin') ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 10px; color: var(--text-muted);"></i>
                    </button>
                    <div id="userMenuDropdown" style="display: none; position: absolute; right: 0; top: 115%; width: 230px; background: var(--surface-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); z-index: var(--z-dropdown); padding: 8px 0; overflow: hidden;">
                        <div style="padding: 10px 16px; border-bottom: 1px solid var(--border-subtle); background: var(--surface-subtle);">
                            <div style="font-weight: 700; font-size: 13px; color: var(--text-primary);"><?= e($currentUser['name'] ?? '') ?></div>
                            <div style="font-size: 11px; color: var(--text-muted);"><?= e($currentUser['email'] ?? '') ?></div>
                        </div>
                        <a href="<?= BASE_URL ?>/settings/business.php" class="nav-link" style="color: var(--text-primary); border-radius: 0; padding: 10px 16px;">
                            <i class="fas fa-building"></i> Business Profile
                        </a>
                        <a href="<?= BASE_URL ?>/settings/branding.php" class="nav-link" style="color: var(--text-primary); border-radius: 0; padding: 10px 16px;">
                            <i class="fas fa-palette"></i> Branding & Colors
                        </a>
                        <a href="<?= BASE_URL ?>/settings/numbering.php" class="nav-link" style="color: var(--text-primary); border-radius: 0; padding: 10px 16px;">
                            <i class="fas fa-hashtag"></i> Quotation Numbering
                        </a>
                        <div style="border-top: 1px solid var(--border-subtle); margin: 4px 0;"></div>
                        <a href="<?= BASE_URL ?>/logout.php" class="nav-link" style="color: var(--danger); border-radius: 0; padding: 10px 16px;">
                            <i class="fas fa-arrow-right-from-bracket"></i> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Command Palette Modal (Ctrl+K) -->
        <div class="command-palette-backdrop" id="commandPaletteModal">
            <div class="command-palette-box">
                <div class="command-palette-input-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="commandPaletteInput" class="command-palette-input" placeholder="Type a command, search quotes or clients..." autocomplete="off">
                    <kbd style="font-size: 11px; padding: 2px 6px; border-radius: 4px; border: 1px solid var(--border-color); color: var(--text-muted);">ESC</kbd>
                </div>
                <div class="command-palette-results" id="commandPaletteResults">
                    <div style="padding: 6px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Navigation</div>
                    <a href="<?= BASE_URL ?>/dashboard.php" class="command-item">
                        <i class="fas fa-chart-line"></i> Dashboard
                        <span class="command-badge">Jump</span>
                    </a>
                    <a href="<?= BASE_URL ?>/quotations/create.php" class="command-item">
                        <i class="fas fa-plus"></i> Create New Quotation
                        <span class="command-badge">Action</span>
                    </a>
                    <a href="<?= BASE_URL ?>/quotations/index.php" class="command-item">
                        <i class="fas fa-file-contract"></i> All Quotations & Proposals
                        <span class="command-badge">List</span>
                    </a>
                    <a href="<?= BASE_URL ?>/customers/index.php" class="command-item">
                        <i class="fas fa-users"></i> Customers CRM
                        <span class="command-badge">Directory</span>
                    </a>
                    <a href="<?= BASE_URL ?>/products/index.php" class="command-item">
                        <i class="fas fa-cubes"></i> Products & Price Catalog
                        <span class="command-badge">Catalog</span>
                    </a>
                    <a href="<?= BASE_URL ?>/invoices/index.php" class="command-item">
                        <i class="fas fa-receipt"></i> Invoices & Billing
                        <span class="command-badge">Finance</span>
                    </a>
                    <a href="<?= BASE_URL ?>/reports/index.php" class="command-item">
                        <i class="fas fa-chart-pie"></i> Reports & Pipeline Insights
                        <span class="command-badge">Analytics</span>
                    </a>
                    <a href="<?= BASE_URL ?>/settings/business.php" class="command-item">
                        <i class="fas fa-gear"></i> Settings & Business Configuration
                        <span class="command-badge">Config</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="content-wrapper">
