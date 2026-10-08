<?php
/**
 * Quotation Studio - Reports & Business Analytics
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

// Date range filters
$startDate = trim($_GET['start_date'] ?? date('Y-m-01', strtotime('-5 months')));
$endDate = trim($_GET['end_date'] ?? date('Y-m-d'));

// 1. Core KPIs
$kpiStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_quotes,
        SUM(grand_total) as total_quoted_value,
        SUM(CASE WHEN status IN ('Accepted', 'Invoiced') THEN grand_total ELSE 0 END) as accepted_value,
        SUM(CASE WHEN status = 'Rejected' THEN grand_total ELSE 0 END) as rejected_value,
        SUM(CASE WHEN status = 'Draft' THEN grand_total ELSE 0 END) as draft_value,
        COUNT(CASE WHEN status IN ('Accepted', 'Invoiced') THEN 1 END) as accepted_count,
        COUNT(CASE WHEN status = 'Rejected' THEN 1 END) as rejected_count,
        COUNT(CASE WHEN status = 'Draft' THEN 1 END) as draft_count,
        COUNT(CASE WHEN status = 'Sent' THEN 1 END) as sent_count,
        COUNT(CASE WHEN status = 'Viewed' THEN 1 END) as viewed_count,
        COUNT(CASE WHEN status = 'Expired' THEN 1 END) as expired_count,
        AVG(grand_total) as avg_quote_value
    FROM quotations 
    WHERE user_id = ? AND date BETWEEN ? AND ?
");
$kpiStmt->execute([$userId, $startDate, $endDate]);
$kpis = $kpiStmt->fetch();

$totalQuotes = (int)($kpis['total_quotes'] ?? 0);
$totalQuotedValue = (float)($kpis['total_quoted_value'] ?? 0);
$acceptedValue = (float)($kpis['accepted_value'] ?? 0);
$rejectedValue = (float)($kpis['rejected_value'] ?? 0);
$acceptedCount = (int)($kpis['accepted_count'] ?? 0);
$rejectedCount = (int)($kpis['rejected_count'] ?? 0);
$avgQuoteValue = (float)($kpis['avg_quote_value'] ?? 0);

// Win rate / Conversion rate
$decidedCount = $acceptedCount + $rejectedCount;
$conversionRate = ($decidedCount > 0) ? round(($acceptedCount / $decidedCount) * 100, 1) : 0;
$overallConversionRate = ($totalQuotes > 0) ? round(($acceptedCount / $totalQuotes) * 100, 1) : 0;

// 2. Invoice & Collection KPIs
$invStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_invoices,
        SUM(grand_total) as total_invoiced,
        SUM(paid_amount) as total_collected,
        SUM(balance_due) as total_due
    FROM invoices 
    WHERE user_id = ? AND issue_date BETWEEN ? AND ?
");
$invStmt->execute([$userId, $startDate, $endDate]);
$invKpis = $invStmt->fetch();

$totalInvoiced = (float)($invKpis['total_invoiced'] ?? 0);
$totalCollected = (float)($invKpis['total_collected'] ?? 0);
$totalDue = (float)($invKpis['total_due'] ?? 0);

// 3. Monthly Trends (last 6 months)
$monthlyStmt = $pdo->prepare("
    SELECT 
        substr(date, 1, 7) as month_key,
        COUNT(*) as quote_count,
        SUM(grand_total) as total_val,
        SUM(CASE WHEN status IN ('Accepted', 'Invoiced') THEN grand_total ELSE 0 END) as accepted_val
    FROM quotations
    WHERE user_id = ?
    GROUP BY month_key
    ORDER BY month_key ASC
");
$monthlyStmt->execute([$userId]);
$monthlyTrends = $monthlyStmt->fetchAll();

// 4. Customer-wise Quotation Distribution
$custStmt = $pdo->prepare("
    SELECT 
        c.name as customer_name,
        c.company_name,
        COUNT(q.id) as quote_count,
        SUM(q.grand_total) as total_quoted,
        SUM(CASE WHEN q.status IN ('Accepted', 'Invoiced') THEN q.grand_total ELSE 0 END) as total_accepted
    FROM quotations q
    JOIN customers c ON q.customer_id = c.id
    WHERE q.user_id = ? AND q.date BETWEEN ? AND ?
    GROUP BY c.id, c.name, c.company_name
    ORDER BY total_quoted DESC
    LIMIT 10
");
$custStmt->execute([$userId, $startDate, $endDate]);
$topCustomers = $custStmt->fetchAll();

// 5. Product/Service contribution
$prodStmt = $pdo->prepare("
    SELECT 
        description as item_name,
        COUNT(*) as usage_count,
        SUM(quantity) as total_qty,
        SUM(line_total) as total_revenue
    FROM quotation_items qi
    JOIN quotations q ON qi.quotation_id = q.id
    WHERE q.user_id = ? AND q.date BETWEEN ? AND ?
    GROUP BY description
    ORDER BY total_revenue DESC
    LIMIT 10
");
$prodStmt->execute([$userId, $startDate, $endDate]);
$topProducts = $prodStmt->fetchAll();

// 6. Status distribution for doughnut chart
$statusCounts = [
    'Draft' => (int)($kpis['draft_count'] ?? 0),
    'Sent' => (int)($kpis['sent_count'] ?? 0),
    'Viewed' => (int)($kpis['viewed_count'] ?? 0),
    'Accepted' => (int)($kpis['accepted_count'] ?? 0),
    'Rejected' => (int)($kpis['rejected_count'] ?? 0),
    'Expired' => (int)($kpis['expired_count'] ?? 0)
];

$pageTitle = 'Reports & Analytics';
$activeNav = 'reports';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Executive Reports & Sales Intelligence</h1>
        <div class="page-subtitle">Track proposal volume, conversion win-rates, customer pipeline, and financial performance.</div>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 160px;">
                <label class="form-label" style="margin-bottom: 4px; font-size: 12px;">From Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
            </div>
            <div style="flex: 1; min-width: 160px;">
                <label class="form-label" style="margin-bottom: 4px; font-size: 12px;">To Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
                <a href="<?= BASE_URL ?>/reports/index.php" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Financial Highlights -->
<div class="metrics-grid">
    <div class="metric-card accent-primary">
        <div class="metric-header">
            <span class="metric-title">Total Quoted Pipeline</span>
            <div class="metric-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($totalQuotedValue) ?></div>
        <div class="metric-subtext"><?= $totalQuotes ?> total quotations issued</div>
    </div>

    <div class="metric-card accent-success">
        <div class="metric-header">
            <span class="metric-title">Accepted / Won Value</span>
            <div class="metric-icon"><i class="fas fa-award"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($acceptedValue) ?></div>
        <div class="metric-subtext text-success"><?= $acceptedCount ?> proposals successfully converted</div>
    </div>

    <div class="metric-card accent-info">
        <div class="metric-header">
            <span class="metric-title">Win Rate (Conversion)</span>
            <div class="metric-icon"><i class="fas fa-percentage"></i></div>
        </div>
        <div class="metric-value"><?= $conversionRate ?>%</div>
        <div class="metric-subtext"><?= $acceptedCount ?> of <?= $decidedCount ?> decided proposals won</div>
    </div>

    <div class="metric-card accent-warning">
        <div class="metric-header">
            <span class="metric-title">Payments Collected</span>
            <div class="metric-icon"><i class="fas fa-hand-holding-usd"></i></div>
        </div>
        <div class="metric-value"><?= format_currency($totalCollected) ?></div>
        <div class="metric-subtext"><?= format_currency($totalDue) ?> outstanding balance</div>
    </div>
</div>

<!-- Secondary KPIs -->
<div class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 24px;">
    <div class="card" style="padding: 16px;">
        <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Average Deal Size</div>
        <div style="font-size: 20px; font-weight: 700; color: var(--text-primary); margin-top: 4px;"><?= format_currency($avgQuoteValue) ?></div>
    </div>
    <div class="card" style="padding: 16px;">
        <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Draft Stage Value</div>
        <div style="font-size: 20px; font-weight: 700; color: var(--text-muted); margin-top: 4px;"><?= format_currency($kpis['draft_value'] ?? 0) ?></div>
    </div>
    <div class="card" style="padding: 16px;">
        <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Lost / Rejected Value</div>
        <div style="font-size: 20px; font-weight: 700; color: var(--danger); margin-top: 4px;"><?= format_currency($rejectedValue) ?></div>
    </div>
    <div class="card" style="padding: 16px;">
        <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Invoiced Value</div>
        <div style="font-size: 20px; font-weight: 700; color: var(--primary); margin-top: 4px;"><?= format_currency($totalInvoiced) ?></div>
    </div>
</div>

<!-- Visual Charts Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 28px;">
    <!-- Trend Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-line" style="color: var(--primary); margin-right: 8px;"></i> Monthly Quoted vs Won Trend</h3>
        </div>
        <div class="card-body">
            <div style="height: 280px; position: relative;">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-pie" style="color: var(--primary); margin-right: 8px;"></i> Proposal Pipeline Distribution</h3>
        </div>
        <div class="card-body" style="display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <div style="width: 220px; height: 220px; position: relative;">
                <canvas id="statusDonutChart"></canvas>
            </div>
            <div style="margin-top: 16px; font-size: 12px; display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
                <span><i class="fas fa-circle" style="color: #0d5c75;"></i> Accepted: <strong><?= $statusCounts['Accepted'] ?></strong></span>
                <span><i class="fas fa-circle" style="color: #0ea5e9;"></i> Sent: <strong><?= $statusCounts['Sent'] ?></strong></span>
                <span><i class="fas fa-circle" style="color: #64748b;"></i> Draft: <strong><?= $statusCounts['Draft'] ?></strong></span>
                <span><i class="fas fa-circle" style="color: #ef4444;"></i> Rejected: <strong><?= $statusCounts['Rejected'] ?></strong></span>
            </div>
        </div>
    </div>
</div>

<!-- Tables: Top Customers & Product Revenue Breakdown -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px;">
    <!-- Customer Breakdown -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title"><i class="fas fa-building" style="color: var(--primary); margin-right: 8px;"></i> Top Customers by Quoted Value</h3>
            <span style="font-size: 12px; color: var(--text-muted);"><?= count($topCustomers) ?> active accounts</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th style="text-align: center;">Quotes</th>
                        <th style="text-align: right;">Total Quoted</th>
                        <th style="text-align: right;">Won Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topCustomers)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">No customer records in range.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topCustomers as $tc): ?>
                            <tr>
                                <td>
                                    <strong><?= e($tc['company_name'] ?: $tc['customer_name']) ?></strong>
                                    <?php if ($tc['company_name'] && $tc['customer_name']): ?>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= e($tc['customer_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;"><span class="badge badge-draft"><?= $tc['quote_count'] ?></span></td>
                                <td style="text-align: right; font-weight: 600;"><?= format_currency($tc['total_quoted']) ?></td>
                                <td style="text-align: right; font-weight: 700; color: var(--secondary);"><?= format_currency($tc['total_accepted']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Product Revenue Breakdown -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title"><i class="fas fa-boxes" style="color: var(--primary); margin-right: 8px;"></i> Product & Service Contribution</h3>
            <span style="font-size: 12px; color: var(--text-muted);"><?= count($topProducts) ?> items quoted</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product / Service</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Quoted Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topProducts)): ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 24px;">No line item records in range.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topProducts as $tp): ?>
                            <tr>
                                <td>
                                    <strong><?= e($tp['item_name']) ?></strong>
                                    <div style="font-size: 11px; color: var(--text-muted);">Quoted <?= $tp['usage_count'] ?> times</div>
                                </td>
                                <td style="text-align: center; font-weight: 600;"><?= rtrim(rtrim((string)$tp['total_qty'], '0'), '.') ?></td>
                                <td style="text-align: right; font-weight: 700; color: var(--primary);"><?= format_currency($tp['total_revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Monthly Trends Chart
    const monthlyLabels = <?= json_encode(array_column($monthlyTrends, 'month_key') ?: [date('Y-m')]) ?>;
    const monthlyTotal = <?= json_encode(array_map('floatval', array_column($monthlyTrends, 'total_val') ?: [0])) ?>;
    const monthlyWon = <?= json_encode(array_map('floatval', array_column($monthlyTrends, 'accepted_val') ?: [0])) ?>;

    const ctxTrend = document.getElementById('monthlyTrendChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [
                    {
                        label: 'Total Quoted Value',
                        data: monthlyTotal,
                        backgroundColor: 'rgba(13, 92, 117, 0.4)',
                        borderColor: '#0d5c75',
                        borderWidth: 1.5,
                        borderRadius: 4
                    },
                    {
                        label: 'Accepted / Won Value',
                        data: monthlyWon,
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₹' + Number(context.raw).toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + Number(value).toLocaleString('en-IN');
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Status Donut Chart
    const ctxStatus = document.getElementById('statusDonutChart');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Accepted', 'Sent', 'Draft', 'Rejected', 'Expired'],
                datasets: [{
                    data: [
                        <?= $statusCounts['Accepted'] ?>,
                        <?= $statusCounts['Sent'] ?>,
                        <?= $statusCounts['Draft'] ?>,
                        <?= $statusCounts['Rejected'] ?>,
                        <?= $statusCounts['Expired'] ?>
                    ],
                    backgroundColor: [
                        '#10b981',
                        '#0284c7',
                        '#94a3b8',
                        '#ef4444',
                        '#f59e0b'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
