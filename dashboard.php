<?php
/**
 * Quotation Studio - Main Commercial Dashboard (Linear / Stripe SaaS Edition)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$user = current_user();

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

// 3. Fetch Quotations Expiring Soon (within 7 days)
$expiringStmt = $pdo->prepare("
    SELECT q.id, q.quotation_number, q.grand_total, q.valid_until, q.status, c.company_name, c.name as customer_name
    FROM quotations q
    LEFT JOIN customers c ON q.customer_id = c.id
    WHERE q.user_id = ? AND q.status IN ('Sent', 'Viewed') AND q.valid_until >= CURRENT_DATE
    ORDER BY q.valid_until ASC
    LIMIT 4
");
$expiringStmt->execute([$userId]);
$expiringQuotes = $expiringStmt->fetchAll();

// Greeting Calculation
$hour = (int)date('H');
$greeting = 'Good evening';
if ($hour >= 5 && $hour < 12) $greeting = 'Good morning';
elseif ($hour >= 12 && $hour < 17) $greeting = 'Good afternoon';
$userName = !empty($user['name']) ? explode(' ', trim($user['name']))[0] : 'Rhythm';

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Header with Greeting & Quick Actions -->
<div style="background: linear-gradient(135deg, rgba(13, 92, 117, 0.08) 0%, rgba(99, 102, 241, 0.06) 100%); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 24px 28px; margin-bottom: 26px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; box-shadow: var(--shadow-xs);">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
            <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--primary);">Live Pipeline</span>
            <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--success); box-shadow: 0 0 6px var(--success);"></span>
            <span style="font-size: 12px; color: var(--text-muted);">&bull; <?= date('l, d F Y') ?></span>
        </div>
        <h1 style="font-family: var(--font-heading); font-size: 26px; font-weight: 800; color: var(--text-primary); margin: 0; letter-spacing: -0.5px;">
            <?= e($greeting) ?>, <?= e($userName) ?>
        </h1>
        <div style="font-size: 13.5px; color: var(--text-muted); margin-top: 4px;">
            Here is your live commercial proposal pipeline, revenue conversion metrics, and pending client actions.
        </div>
    </div>

    <!-- Quick Action Row -->
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <a href="<?= BASE_URL ?>/quotations/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Quotation
        </a>
        <a href="<?= BASE_URL ?>/customers/create.php" class="btn btn-secondary">
            <i class="fas fa-user-plus"></i> Add Client
        </a>
        <a href="<?= BASE_URL ?>/invoices/index.php" class="btn btn-secondary">
            <i class="fas fa-receipt"></i> View Invoices
        </a>
    </div>
</div>

<!-- Primary Financial KPI Cards with Animated Numbers & Sparklines -->
<div class="metrics-grid">
    <div class="metric-card accent-info">
        <div class="metric-header">
            <span class="metric-title">Total Quoted Value</span>
            <div class="metric-icon"><i class="fas fa-wallet"></i></div>
        </div>
        <div class="metric-value">
            ₹ <span class="count-up-val" data-target="<?= (float)$metrics['total_quoted_value'] ?>" data-decimals="2">0.00</span>
        </div>
        <div class="metric-subtext">
            <span style="color: var(--info); font-weight: 700;"><i class="fas fa-layer-group"></i> <?= (int)$metrics['total_count'] ?></span>
            <span>proposals in pipeline</span>
        </div>
    </div>

    <div class="metric-card accent-success">
        <div class="metric-header">
            <span class="metric-title">Accepted Pipeline Value</span>
            <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <div class="metric-value">
            ₹ <span class="count-up-val" data-target="<?= (float)$metrics['accepted_value'] ?>" data-decimals="2">0.00</span>
        </div>
        <div class="metric-subtext">
            <span style="color: var(--success); font-weight: 700;"><i class="fas fa-arrow-trend-up"></i> Confirmed orders</span>
        </div>
    </div>

    <div class="metric-card accent-warning">
        <div class="metric-header">
            <span class="metric-title">Pending / In Review</span>
            <div class="metric-icon"><i class="fas fa-hourglass-half"></i></div>
        </div>
        <div class="metric-value">
            ₹ <span class="count-up-val" data-target="<?= (float)$metrics['pending_value'] ?>" data-decimals="2">0.00</span>
        </div>
        <div class="metric-subtext">
            <span style="color: var(--warning); font-weight: 700;"><i class="fas fa-clock"></i> Active proposals awaiting client sign-off</span>
        </div>
    </div>

    <div class="metric-card accent-purple">
        <div class="metric-header">
            <span class="metric-title">Win Conversion Rate</span>
            <div class="metric-icon"><i class="fas fa-bullseye"></i></div>
        </div>
        <div class="metric-value">
            <span class="count-up-val" data-target="<?= (float)$conversionRate ?>" data-decimals="1" data-suffix="%">0%</span>
        </div>
        <div class="metric-subtext">
            <span>Accepted vs decided opportunities</span>
        </div>
    </div>
</div>

<!-- Proposal Pipeline Summary Strips -->
<div class="status-breakdown-pills">
    <div class="status-pill-box status-draft">
        <span class="label">Drafts</span>
        <span class="count"><?= (int)$metrics['draft_count'] ?></span>
    </div>
    <div class="status-pill-box status-sent">
        <span class="label">Sent</span>
        <span class="count" style="color: var(--info);"><?= (int)$metrics['sent_count'] ?></span>
    </div>
    <div class="status-pill-box status-viewed">
        <span class="label">Viewed by Client</span>
        <span class="count" style="color: var(--status-viewed-text);"><?= (int)$metrics['viewed_count'] ?></span>
    </div>
    <div class="status-pill-box status-accepted">
        <span class="label">Accepted</span>
        <span class="count" style="color: var(--success);"><?= (int)$metrics['accepted_count'] ?></span>
    </div>
    <div class="status-pill-box status-rejected">
        <span class="label">Rejected</span>
        <span class="count" style="color: var(--danger);"><?= (int)$metrics['rejected_count'] ?></span>
    </div>
    <div class="status-pill-box status-expired">
        <span class="label">Expired</span>
        <span class="count" style="color: var(--warning);"><?= (int)$metrics['expired_count'] ?></span>
    </div>
</div>

<!-- Charts Grid: Velocity & Status Distribution -->
<div class="charts-grid">
    <div class="chart-card">
        <div class="card-header" style="border:none; padding: 0 0 16px 0;">
            <div class="card-title"><i class="fas fa-chart-column" style="color: var(--primary); margin-right: 8px;"></i> Financial Value Breakdown</div>
            <span class="badge badge-accepted">Live Aggregation</span>
        </div>
        <div class="chart-container">
            <canvas id="pipelineBarChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="card-header" style="border:none; padding: 0 0 16px 0;">
            <div class="card-title"><i class="fas fa-chart-pie" style="color: var(--secondary); margin-right: 8px;"></i> Proposal Status Distribution</div>
        </div>
        <div class="chart-container">
            <canvas id="statusDoughnutChart"></canvas>
        </div>
    </div>
</div>

<!-- Two-Column Grid: Recent Quotations & Expiring Soon Widget -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 22px; margin-bottom: 24px;">
    <!-- Column 1: Recent Quotations Table -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 18px 22px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
            <div style="font-weight: 700; font-size: 15px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-clock-rotate-left" style="color: var(--primary);"></i> Recent Quotations
            </div>
            <a href="<?= BASE_URL ?>/quotations/index.php" class="btn btn-secondary btn-sm">
                View All (<?= (int)$metrics['total_count'] ?>)
            </a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Quotation #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentQuotations)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
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
                                    <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= (int)$q['id'] ?>" style="font-weight: 700; font-size: 13px; color: var(--text-primary); text-decoration: none;">
                                        <?= e($q['quotation_number']) ?>
                                    </a>
                                    <?php if (!empty($q['subject'])): ?>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= e(substr($q['subject'], 0, 36)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 500; font-size: 12.5px;"><?= e($customerLabel) ?></div>
                                </td>
                                <td>
                                    <span style="font-family: var(--font-mono); font-weight: 700; font-size: 13px; color: var(--text-primary);">
                                        <?= format_currency($q['grand_total']) ?>
                                    </span>
                                </td>
                                <td><?= status_badge($displayStatus) ?></td>
                                <td style="text-align: right;">
                                    <div class="row-actions">
                                        <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/quotations/edit.php?id=<?= (int)$q['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                            <i class="fas fa-pen"></i>
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

    <!-- Column 2: Quotations Expiring Soon Widget -->
    <div class="card">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-subtle);">
            <div style="font-weight: 700; font-size: 14.5px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-hourglass-end" style="color: var(--warning);"></i> Expiring Soon
            </div>
            <span class="badge badge-expired"><?= count($expiringQuotes) ?> Active</span>
        </div>

        <?php if (empty($expiringQuotes)): ?>
            <div style="text-align: center; padding: 30px 10px; color: var(--text-muted); font-size: 12.5px;">
                <i class="fas fa-shield-check" style="font-size: 28px; color: var(--success); margin-bottom: 8px; display: block; opacity: 0.8;"></i>
                No pending quotations are expiring within the next 7 days.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($expiringQuotes as $eq): 
                    $daysLeft = max(0, round((strtotime($eq['valid_until']) - time()) / 86400));
                ?>
                    <div style="padding: 12px; background: var(--surface-subtle); border: 1px solid var(--border-color); border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= (int)$eq['id'] ?>" style="font-weight: 700; font-size: 12.5px; color: var(--text-primary); text-decoration: none;">
                                <?= e($eq['quotation_number']) ?>
                            </a>
                            <div style="font-size: 11px; color: var(--text-muted);"><?= e($eq['company_name'] ?: $eq['customer_name']) ?></div>
                            <div style="font-size: 10.5px; color: var(--warning); font-weight: 600; margin-top: 3px;">
                                <i class="fas fa-stopwatch"></i> <?= $daysLeft === 0 ? 'Expires today!' : "{$daysLeft} days remaining" ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-family: var(--font-mono); font-weight: 700; font-size: 12.5px;"><?= format_currency($eq['grand_total']) ?></div>
                            <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= (int)$eq['id'] ?>" class="btn btn-secondary btn-sm" style="margin-top: 6px; padding: 3px 8px; font-size: 11px;">
                                Review
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Restyle Chart.js with modern gradients and curves
    const ctxBar = document.getElementById('pipelineBarChart')?.getContext('2d');
    if (ctxBar) {
        const gradQuoted = ctxBar.createLinearGradient(0, 0, 0, 260);
        gradQuoted.addColorStop(0, '#0d5c75');
        gradQuoted.addColorStop(1, '#083b4c');

        const gradAccepted = ctxBar.createLinearGradient(0, 0, 0, 260);
        gradAccepted.addColorStop(0, '#10b981');
        gradAccepted.addColorStop(1, '#059669');

        const gradPending = ctxBar.createLinearGradient(0, 0, 0, 260);
        gradPending.addColorStop(0, '#f59e0b');
        gradPending.addColorStop(1, '#d97706');

        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Total Quoted', 'Accepted Revenue', 'Pending in Review'],
                datasets: [{
                    label: 'Amount (INR)',
                    data: [
                        <?= (float)$metrics['total_quoted_value'] ?>,
                        <?= (float)$metrics['accepted_value'] ?>,
                        <?= (float)$metrics['pending_value'] ?>
                    ],
                    backgroundColor: [gradQuoted, gradAccepted, gradPending],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 12, family: 'Plus Jakarta Sans', weight: '700' },
                        bodyFont: { size: 12, family: 'JetBrains Mono' },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: (ctx) => ' Amount: ₹ ' + Number(ctx.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 })
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (val) => '₹ ' + Number(val).toLocaleString('en-IN')
                        }
                    }
                }
            }
        });
    }

    const ctxPie = document.getElementById('statusDoughnutChart')?.getContext('2d');
    if (ctxPie) {
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: ['Draft', 'Sent', 'Viewed', 'Accepted', 'Rejected', 'Expired'],
                datasets: [{
                    data: [
                        <?= (int)$metrics['draft_count'] ?>,
                        <?= (int)$metrics['sent_count'] ?>,
                        <?= (int)$metrics['viewed_count'] ?>,
                        <?= (int)$metrics['accepted_count'] ?>,
                        <?= (int)$metrics['rejected_count'] ?>,
                        <?= (int)$metrics['expired_count'] ?>
                    ],
                    backgroundColor: ['#94a3b8', '#0284c7', '#7c3aed', '#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11, family: 'Plus Jakarta Sans' }, padding: 12 } }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
