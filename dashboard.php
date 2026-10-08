<?php
/**
 * Quotation Studio - Main Dashboard
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

// 1. Fetch Real Database Metrics
$statusStmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_count,
        SUM(CASE WHEN status = 'Draft' THEN 1 ELSE 0 END) as draft_count,
        SUM(CASE WHEN status = 'Sent' THEN 1 ELSE 0 END) as sent_count,
        SUM(CASE WHEN status = 'Viewed' THEN 1 ELSE 0 END) as viewed_count,
        SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) as accepted_count,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(CASE WHEN status = 'Expired' OR (valid_until < CURRENT_DATE AND status IN ('Sent', 'Viewed')) THEN 1 ELSE 0 END) as expired_count,
        SUM(CASE WHEN status = 'Invoiced' THEN 1 ELSE 0 END) as invoiced_count,
        COALESCE(SUM(grand_total), 0) as total_quoted_value,
        COALESCE(SUM(CASE WHEN status IN ('Accepted', 'Invoiced') THEN grand_total ELSE 0 END), 0) as accepted_value,
        COALESCE(SUM(CASE WHEN status IN ('Draft', 'Sent', 'Viewed') THEN grand_total ELSE 0 END), 0) as pending_value
    FROM quotations
    WHERE user_id = ?
");
$statusStmt->execute([$userId]);
$metrics = $statusStmt->fetch() ?: [
    'total_count' => 0, 'draft_count' => 0, 'sent_count' => 0, 'viewed_count' => 0,
    'accepted_count' => 0, 'rejected_count' => 0, 'expired_count' => 0, 'invoiced_count' => 0,
    'total_quoted_value' => 0, 'accepted_value' => 0, 'pending_value' => 0
];

$decidedCount = (int)$metrics['accepted_count'] + (int)$metrics['invoiced_count'] + (int)$metrics['rejected_count'];
$conversionRate = $decidedCount > 0 ? round((((int)$metrics['accepted_count'] + (int)$metrics['invoiced_count']) / $decidedCount) * 100, 1) : 0.0;

// 2. Fetch Recent Quotations
$recentStmt = $pdo->prepare("
    SELECT q.*, c.name as customer_name, c.company_name as customer_company
    FROM quotations q
    LEFT JOIN customers c ON q.customer_id = c.id
    WHERE q.user_id = ?
    ORDER BY q.created_at DESC
    LIMIT 6
");
$recentStmt->execute([$userId]);
$recentQuotations = $recentStmt->fetchAll();

// 3. Status Breakdown for Chart
$statusLabels = ['Draft', 'Sent', 'Viewed', 'Accepted', 'Rejected', 'Expired'];
$statusData = [
    (int)$metrics['draft_count'],
    (int)$metrics['sent_count'],
    (int)$metrics['viewed_count'],
    (int)$metrics['accepted_count'],
    (int)$metrics['rejected_count'],
    (int)$metrics['expired_count']
];

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Commercial Dashboard</h1>
        <div class="page-subtitle">Real-time overview of quotations, pipeline performance, and conversions.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/quotations/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Quotation
        </a>
    </div>
</div>

<!-- Primary Financial KPIs -->
<div class="metrics-grid">
    <div class="metric-card accent-info">
        <div class="metric-header">
            <span class="metric-title">Total Quoted Value</span>
            <div class="metric-icon"><i class="fas fa-coins"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($metrics['total_quoted_value']) ?></div>
        <div class="metric-subtext"><i class="fas fa-file-invoice"></i> Across <?= (int)$metrics['total_count'] ?> quotations</div>
    </div>

    <div class="metric-card accent-success">
        <div class="metric-header">
            <span class="metric-title">Accepted Pipeline Value</span>
            <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($metrics['accepted_value']) ?></div>
        <div class="metric-subtext text-success"><i class="fas fa-arrow-up"></i> Confirmed orders</div>
    </div>

    <div class="metric-card accent-warning">
        <div class="metric-header">
            <span class="metric-title">Pending / In Review</span>
            <div class="metric-icon"><i class="fas fa-hourglass-half"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($metrics['pending_value']) ?></div>
        <div class="metric-subtext"><i class="fas fa-paper-plane"></i> Active customer opportunities</div>
    </div>

    <div class="metric-card accent-purple">
        <div class="metric-header">
            <span class="metric-title">Win Conversion Rate</span>
            <div class="metric-icon"><i class="fas fa-percentage"></i></div>
        </div>
        <div class="metric-value"><?= $conversionRate ?>%</div>
        <div class="metric-subtext"><i class="fas fa-bullseye"></i> Accepted vs Rejected proposals</div>
    </div>
</div>

<!-- Status Counts Grid -->
<div class="status-breakdown-pills">
    <div class="status-pill-box">
        <span class="label">Drafts</span>
        <span class="count"><?= (int)$metrics['draft_count'] ?></span>
    </div>
    <div class="status-pill-box">
        <span class="label">Sent</span>
        <span class="count" style="color: var(--info);"><?= (int)$metrics['sent_count'] ?></span>
    </div>
    <div class="status-pill-box">
        <span class="label">Viewed</span>
        <span class="count" style="color: var(--purple);"><?= (int)$metrics['viewed_count'] ?></span>
    </div>
    <div class="status-pill-box">
        <span class="label">Accepted</span>
        <span class="count" style="color: var(--secondary);"><?= (int)$metrics['accepted_count'] ?></span>
    </div>
    <div class="status-pill-box">
        <span class="label">Rejected</span>
        <span class="count" style="color: var(--danger);"><?= (int)$metrics['rejected_count'] ?></span>
    </div>
    <div class="status-pill-box">
        <span class="label">Expired</span>
        <span class="count" style="color: var(--warning);"><?= (int)$metrics['expired_count'] ?></span>
    </div>
</div>

<!-- Charts Grid -->
<div class="charts-grid">
    <div class="chart-card">
        <div class="card-header" style="border:none; padding: 0 0 16px 0;">
            <div class="card-title"><i class="fas fa-chart-line" style="color: var(--primary); margin-right: 8px;"></i> Quotation Pipeline Overview</div>
        </div>
        <div class="chart-container">
            <canvas id="pipelineBarChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="card-header" style="border:none; padding: 0 0 16px 0;">
            <div class="card-title"><i class="fas fa-chart-pie" style="color: var(--secondary); margin-right: 8px;"></i> Status Distribution</div>
        </div>
        <div class="chart-container">
            <canvas id="statusDoughnutChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Quotations Card -->
<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-history" style="color: var(--text-muted); margin-right: 8px;"></i> Recent Quotations</div>
        <a href="<?= BASE_URL ?>/quotations/index.php" class="btn btn-secondary btn-sm">
            View All Quotations (<?= (int)$metrics['total_count'] ?>)
        </a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Quotation #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Valid Until</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentQuotations)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 36px; color: var(--text-muted);">
                            <i class="fas fa-file-invoice-dollar" style="font-size: 32px; margin-bottom: 10px; display: block; opacity: 0.4;"></i>
                            No quotations found yet. Create your first quotation to get started!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentQuotations as $q):
                        $customerLabel = $q['customer_company'] ?: ($q['customer_name'] ?: 'Ad-hoc Client');
                        $isExpired = (!empty($q['valid_until']) && strtotime($q['valid_until']) < time() && in_array($q['status'], ['Sent', 'Viewed']));
                        $displayStatus = $isExpired ? 'Expired' : $q['status'];
                    ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= (int)$q['id'] ?>" style="font-weight: 600;">
                                    <?= e($q['quotation_number']) ?>
                                </a>
                                <?php if (!empty($q['subject'])): ?>
                                    <div style="font-size: 11.5px; color: var(--text-muted);"><?= e(substr($q['subject'], 0, 45)) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?= e($customerLabel) ?></div>
                            </td>
                            <td><?= format_date($q['date']) ?></td>
                            <td>
                                <span class="<?= $isExpired ? 'text-danger' : '' ?>">
                                    <?= format_date($q['valid_until']) ?>
                                </span>
                            </td>
                            <td style="font-weight: 700;"><?= format_currency($q['grand_total']) ?></td>
                            <td><?= status_badge($displayStatus) ?></td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View Proposal">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/quotations/edit.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit Proposal">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/quotations/duplicate.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Duplicate">
                                        <i class="fas fa-copy"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/quotations/download.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Download PDF">
                                        <i class="fas fa-file-pdf" style="color: var(--danger);"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Pipeline Bar Chart
    const ctxBar = document.getElementById('pipelineBarChart')?.getContext('2d');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Total Quoted', 'Accepted Value', 'Pending Value'],
                datasets: [{
                    label: 'Amount (INR)',
                    data: [
                        <?= (float)$metrics['total_quoted_value'] ?>,
                        <?= (float)$metrics['accepted_value'] ?>,
                        <?= (float)$metrics['pending_value'] ?>
                    ],
                    backgroundColor: ['#0d5c75', '#85a438', '#f59e0b'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹ ' + Number(value).toLocaleString('en-IN'); }
                        }
                    }
                }
            }
        });
    }

    // 2. Status Doughnut Chart
    const ctxPie = document.getElementById('statusDoughnutChart')?.getContext('2d');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($statusLabels) ?>,
                datasets: [{
                    data: <?= json_encode($statusData) ?>,
                    backgroundColor: ['#94a3b8', '#0284c7', '#9333ea', '#16a34a', '#dc2626', '#f59e0b']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
