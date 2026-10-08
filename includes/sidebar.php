<?php
/**
 * Quotation Studio - Sidebar Navigation (Collapsible & Modernized)
 */
$activeNav = $activeNav ?? 'dashboard';
?>
<aside class="sidebar" id="appSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <div class="sidebar-brand-name">Quotation Studio</div>
        <span class="sidebar-brand-badge">SaaS</span>
    </div>

    <ul class="sidebar-nav">
        <li class="nav-section-title">Main Menu</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/dashboard.php" class="nav-link <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/quotations/index.php" class="nav-link <?= $activeNav === 'quotations' ? 'active' : '' ?>">
                <i class="fas fa-file-contract"></i>
                <span>Quotations</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/customers/index.php" class="nav-link <?= $activeNav === 'customers' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/products/index.php" class="nav-link <?= $activeNav === 'products' ? 'active' : '' ?>">
                <i class="fas fa-cubes"></i>
                <span>Products & Services</span>
            </a>
        </li>

        <li class="nav-section-title">Finance & Billing</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/invoices/index.php" class="nav-link <?= $activeNav === 'invoices' ? 'active' : '' ?>">
                <i class="fas fa-receipt"></i>
                <span>Invoices</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/payments/index.php" class="nav-link <?= $activeNav === 'payments' ? 'active' : '' ?>">
                <i class="fas fa-wallet"></i>
                <span>Payments</span>
            </a>
        </li>

        <li class="nav-section-title">Design & Insights</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/templates/index.php" class="nav-link <?= $activeNav === 'templates' ? 'active' : '' ?>">
                <i class="fas fa-layer-group"></i>
                <span>Templates (5)</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/reports/index.php" class="nav-link <?= $activeNav === 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Reports & Analytics</span>
            </a>
        </li>

        <li class="nav-section-title">Configuration</li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/settings/business.php" class="nav-link <?= $activeNav === 'settings_business' ? 'active' : '' ?>">
                <i class="fas fa-building"></i>
                <span>Business Profile</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/settings/numbering.php" class="nav-link <?= $activeNav === 'settings_numbering' ? 'active' : '' ?>">
                <i class="fas fa-hashtag"></i>
                <span>Numbering Format</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/settings/branding.php" class="nav-link <?= $activeNav === 'settings_branding' ? 'active' : '' ?>">
                <i class="fas fa-palette"></i>
                <span>Branding & Colors</span>
            </a>
        </li>
        <li class="nav-item">
            <a href="<?= BASE_URL ?>/settings/terms.php" class="nav-link <?= $activeNav === 'settings_terms' ? 'active' : '' ?>">
                <i class="fas fa-gavel"></i>
                <span>Terms Presets</span>
            </a>
        </li>
    </ul>

    <!-- Pro Subscription Status Card -->
    <div class="sidebar-pro-card">
        <strong>Enterprise Pro</strong>
        <p>Unlimited proposals & automated GST engine active.</p>
        <div class="sidebar-pro-progress">
            <div class="sidebar-pro-progress-bar"></div>
        </div>
        <div style="font-size: 10.5px; color: #94a3b8; display: flex; justify-content: space-between;">
            <span>Plan: Active</span>
            <span>99.9% SLA</span>
        </div>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-avatar-mini">
            <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
        </div>
        <div class="user-info-mini">
            <div class="name"><?= e($currentUser['name'] ?? 'Administrator') ?></div>
            <div class="biz"><?= e($currentBiz['company_name'] ?? 'Quotation Studio') ?></div>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" title="Sign Out" style="color: #94a3b8; padding: 6px; text-decoration: none;">
            <i class="fas fa-power-off"></i>
        </a>
    </div>
</aside>
