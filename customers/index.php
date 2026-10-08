<?php
/**
 * Quotation Studio - Customers Directory (Modern SaaS CRM Edition)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

$search = trim($_GET['search'] ?? '');
$query = "
    SELECT c.*,
           COUNT(DISTINCT q.id) as quotation_count,
           COUNT(DISTINCT i.id) as invoice_count,
           COALESCE(SUM(DISTINCT q.grand_total), 0) as total_quoted
    FROM customers c
    LEFT JOIN quotations q ON c.id = q.customer_id
    LEFT JOIN invoices i ON c.id = i.customer_id
    WHERE c.user_id = ?
";
$params = [$userId];

if ($search !== '') {
    $query .= " AND (c.name LIKE ? OR c.company_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.gstin LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like, $like);
}

$query .= " GROUP BY c.id ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Color gradients for avatars
$avatarGradients = [
    'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)',
    'linear-gradient(135deg, #10b981 0%, #047857 100%)',
    'linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%)',
    'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
    'linear-gradient(135deg, #ec4899 0%, #be185d 100%)',
    'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
];

$pageTitle = 'Customers CRM';
$activeNav = 'customers';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Customers CRM</h1>
        <div class="page-subtitle">Manage client accounts, GST registrations, contact persons, and proposal history.</div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/customers/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Customer
        </a>
    </div>
</div>

<!-- Modern Filter, Search & View Controls Bar -->
<div class="filter-bar" style="margin-bottom: 22px;">
    <div style="display: flex; gap: 12px; align-items: center; flex: 1; flex-wrap: wrap;">
        <!-- Live Instant Search -->
        <div style="position: relative; flex: 1; min-width: 260px;">
            <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px;"></i>
            <input type="text" id="customerLiveSearch" class="form-control" style="padding-left: 38px;" placeholder="Filter by name, company, email, phone, or GSTIN..." value="<?= e($search) ?>">
            <button type="button" id="clearSearchBtn" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-subtle); cursor: pointer; display: none;" title="Clear search">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>

        <!-- Quick Filter Chips -->
        <div style="display: flex; gap: 6px;" id="filterChips">
            <button type="button" class="btn btn-secondary btn-sm active-chip" data-filter="all">All (<?= count($customers) ?>)</button>
            <button type="button" class="btn btn-secondary btn-sm" data-filter="has-quotes">With Quotes</button>
            <button type="button" class="btn btn-secondary btn-sm" data-filter="has-gst">With GSTIN</button>
        </div>
    </div>

    <!-- View Mode Switcher: Table vs Cards -->
    <div style="display: flex; gap: 4px; background: var(--surface-muted); padding: 3px; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
        <button type="button" class="btn btn-ghost btn-sm" id="viewListBtn" title="List Table View" style="padding: 5px 10px; border-radius: var(--radius-xs);">
            <i class="fas fa-list"></i>
        </button>
        <button type="button" class="btn btn-ghost btn-sm" id="viewGridBtn" title="Grid Card View" style="padding: 5px 10px; border-radius: var(--radius-xs);">
            <i class="fas fa-grip-vertical"></i>
        </button>
    </div>
</div>

<?php if (empty($customers)): ?>
    <div class="card" style="text-align: center; padding: 60px 20px;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 16px;">
            <i class="fas fa-users"></i>
        </div>
        <h3 style="font-size: 17px; font-weight: 700; color: var(--text-primary); margin-bottom: 6px;">No Customers Found</h3>
        <p style="font-size: 13.5px; color: var(--text-muted); max-width: 420px; margin: 0 auto 20px;">
            <?= $search !== '' ? 'No client accounts match your search filter. Try clearing the search.' : 'Get started by creating your first client profile to attach to proposals.' ?>
        </p>
        <a href="<?= BASE_URL ?>/customers/create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create Customer
        </a>
    </div>
<?php else: ?>

    <!-- VIEW 1: Modern Data Table -->
    <div class="card" id="customerTableView" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="table" id="customersTable">
                <thead>
                    <tr>
                        <th>Customer / Company</th>
                        <th>Contact Person</th>
                        <th>Email & Phone</th>
                        <th>GSTIN / Location</th>
                        <th>Proposals</th>
                        <th>Quoted Total</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $idx => $c): 
                        $initials = strtoupper(substr(trim($c['company_name'] ?: $c['name']), 0, 2));
                        $bgGrad = $avatarGradients[$idx % count($avatarGradients)];
                        $quoteCount = (int)$c['quotation_count'];
                        $quoteBadgeText = $quoteCount === 1 ? '1 quote' : ($quoteCount === 0 ? '0 quotes' : "{$quoteCount} quotes");
                        $hasGstin = !empty($c['gstin']);
                    ?>
                        <tr class="customer-row" 
                            data-name="<?= strtolower(e($c['name'])) ?>" 
                            data-company="<?= strtolower(e($c['company_name'])) ?>" 
                            data-email="<?= strtolower(e($c['email'])) ?>" 
                            data-phone="<?= strtolower(e($c['phone'])) ?>" 
                            data-gstin="<?= strtolower(e($c['gstin'])) ?>"
                            data-has-quotes="<?= $quoteCount > 0 ? '1' : '0' ?>"
                            data-has-gst="<?= $hasGstin ? '1' : '0' ?>">
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 36px; height: 36px; border-radius: 10px; background: <?= $bgGrad ?>; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12.5px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.12);">
                                        <?= e($initials) ?>
                                    </div>
                                    <div>
                                        <a href="<?= BASE_URL ?>/customers/view.php?id=<?= (int)$c['id'] ?>" style="font-weight: 700; font-size: 13.5px; color: var(--text-primary); text-decoration: none; display: block;" class="client-title">
                                            <?= e($c['company_name'] ?: $c['name']) ?>
                                        </a>
                                        <?php if (!empty($c['company_name']) && $c['company_name'] !== $c['name']): ?>
                                            <div style="font-size: 11.5px; color: var(--text-muted);"><?= e($c['name']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?= e($c['contact_person'] ?: '—') ?></div>
                            </td>
                            <td>
                                <div><a href="mailto:<?= e($c['email']) ?>" style="color: var(--primary); font-size: 12.5px; text-decoration: none;"><?= e($c['email'] ?: '—') ?></a></div>
                                <?php if (!empty($c['phone'])): ?>
                                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                                        <i class="fas fa-phone-alt" style="font-size: 10px;"></i> <?= e($c['phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($hasGstin): ?>
                                    <span class="badge" style="background: var(--surface-muted); color: var(--text-secondary); font-family: var(--font-mono); font-size: 11px; padding: 2px 8px; cursor: pointer; border: 1px solid var(--border-color);" onclick="copyToClipboard('<?= e($c['gstin']) ?>', 'GSTIN copied!')" title="Click to copy GSTIN">
                                        <i class="far fa-copy" style="font-size: 10px; margin-right: 4px;"></i> <?= e($c['gstin']) ?>
                                    </span>
                                <?php endif; ?>
                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">
                                    <?= e($c['city'] ? $c['city'] . ', ' . $c['state'] : ($c['state'] ?: '—')) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $quoteCount > 0 ? 'badge-sent' : 'badge-draft' ?>">
                                    <?= $quoteBadgeText ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-family: var(--font-mono); font-weight: 600; font-size: 13px; color: var(--text-primary);">
                                    <?= format_currency($c['total_quoted']) ?>
                                </div>
                            </td>
                            <td style="text-align: right;">
                                <div class="row-actions">
                                    <a href="<?= BASE_URL ?>/customers/view.php?id=<?= (int)$c['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="View Client Profile">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/customers/edit.php?id=<?= (int)$c['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit Customer">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <button type="button" class="btn btn-secondary btn-sm btn-icon delete-cust-btn" data-id="<?= (int)$c['id'] ?>" data-name="<?= e($c['company_name'] ?: $c['name']) ?>" style="color: var(--danger);" title="Delete Customer">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- VIEW 2: Modern Card Grid -->
    <div id="customerGridView" style="display: none; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px;">
        <?php foreach ($customers as $idx => $c): 
            $initials = strtoupper(substr(trim($c['company_name'] ?: $c['name']), 0, 2));
            $bgGrad = $avatarGradients[$idx % count($avatarGradients)];
            $quoteCount = (int)$c['quotation_count'];
            $quoteBadgeText = $quoteCount === 1 ? '1 quote' : ($quoteCount === 0 ? '0 quotes' : "{$quoteCount} quotes");
            $hasGstin = !empty($c['gstin']);
        ?>
            <div class="card customer-card" 
                data-name="<?= strtolower(e($c['name'])) ?>" 
                data-company="<?= strtolower(e($c['company_name'])) ?>" 
                data-email="<?= strtolower(e($c['email'])) ?>" 
                data-phone="<?= strtolower(e($c['phone'])) ?>" 
                data-gstin="<?= strtolower(e($c['gstin'])) ?>"
                data-has-quotes="<?= $quoteCount > 0 ? '1' : '0' ?>"
                data-has-gst="<?= $hasGstin ? '1' : '0' ?>"
                style="display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s ease, box-shadow 0.2s ease;">
                <div>
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 42px; height: 42px; border-radius: 12px; background: <?= $bgGrad ?>; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
                                <?= e($initials) ?>
                            </div>
                            <div>
                                <a href="<?= BASE_URL ?>/customers/view.php?id=<?= (int)$c['id'] ?>" style="font-weight: 700; font-size: 15px; color: var(--text-primary); text-decoration: none;">
                                    <?= e($c['company_name'] ?: $c['name']) ?>
                                </a>
                                <div style="font-size: 12px; color: var(--text-muted);"><?= e($c['contact_person'] ?: 'No contact specified') ?></div>
                            </div>
                        </div>
                        <span class="badge <?= $quoteCount > 0 ? 'badge-sent' : 'badge-draft' ?>">
                            <?= $quoteBadgeText ?>
                        </span>
                    </div>

                    <div style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 14px; line-height: 1.6;">
                        <?php if (!empty($c['email'])): ?>
                            <div><i class="fas fa-envelope" style="width: 16px; color: var(--text-muted);"></i> <a href="mailto:<?= e($c['email']) ?>" style="color: var(--primary); text-decoration: none;"><?= e($c['email']) ?></a></div>
                        <?php endif; ?>
                        <?php if (!empty($c['phone'])): ?>
                            <div><i class="fas fa-phone" style="width: 16px; color: var(--text-muted);"></i> <?= e($c['phone']) ?></div>
                        <?php endif; ?>
                        <div><i class="fas fa-map-marker-alt" style="width: 16px; color: var(--text-muted);"></i> <?= e($c['city'] ? $c['city'] . ', ' . $c['state'] : ($c['state'] ?: 'Location not set')) ?></div>
                    </div>

                    <?php if ($hasGstin): ?>
                        <div style="margin-bottom: 14px;">
                            <span class="badge" style="background: var(--surface-muted); color: var(--text-secondary); font-family: var(--font-mono); font-size: 11px; padding: 3px 9px; cursor: pointer; border: 1px solid var(--border-color);" onclick="copyToClipboard('<?= e($c['gstin']) ?>', 'GSTIN copied!')" title="Click to copy GSTIN">
                                <i class="far fa-copy" style="font-size: 10px; margin-right: 4px;"></i> <?= e($c['gstin']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="border-top: 1px solid var(--border-subtle); padding-top: 14px; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">Quoted Total</div>
                        <div style="font-family: var(--font-mono); font-size: 15px; font-weight: 700; color: var(--text-primary);"><?= format_currency($c['total_quoted']) ?></div>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <a href="<?= BASE_URL ?>/customers/view.php?id=<?= (int)$c['id'] ?>" class="btn btn-secondary btn-sm" title="View Profile">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="<?= BASE_URL ?>/customers/edit.php?id=<?= (int)$c['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                            <i class="fas fa-pen"></i>
                        </a>
                        <button type="button" class="btn btn-secondary btn-sm btn-icon delete-cust-btn" data-id="<?= (int)$c['id'] ?>" data-name="<?= e($c['company_name'] ?: $c['name']) ?>" style="color: var(--danger);" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Hidden POST form for Delete operations -->
    <form id="deleteCustomerForm" method="POST" action="<?= BASE_URL ?>/customers/delete.php" style="display: none;">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="deleteCustomerId" value="">
    </form>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Grid / List View Toggle & Persistence
    const viewListBtn = document.getElementById('viewListBtn');
    const viewGridBtn = document.getElementById('viewGridBtn');
    const tableView = document.getElementById('customerTableView');
    const gridView = document.getElementById('customerGridView');

    function setViewMode(mode) {
        if (mode === 'grid') {
            tableView.style.display = 'none';
            gridView.style.display = 'grid';
            viewGridBtn.style.background = 'var(--surface-card)';
            viewGridBtn.style.boxShadow = 'var(--shadow-xs)';
            viewListBtn.style.background = 'transparent';
            viewListBtn.style.boxShadow = 'none';
        } else {
            tableView.style.display = 'block';
            gridView.style.display = 'none';
            viewListBtn.style.background = 'var(--surface-card)';
            viewListBtn.style.boxShadow = 'var(--shadow-xs)';
            viewGridBtn.style.background = 'transparent';
            viewGridBtn.style.boxShadow = 'none';
        }
        localStorage.setItem('qs_customers_view', mode);
    }

    const savedView = localStorage.getItem('qs_customers_view') || 'list';
    setViewMode(savedView);

    viewListBtn?.addEventListener('click', () => setViewMode('list'));
    viewGridBtn?.addEventListener('click', () => setViewMode('grid'));

    // 2. Instant Debounced Search
    const searchInput = document.getElementById('customerLiveSearch');
    const clearBtn = document.getElementById('clearSearchBtn');
    let currentFilter = 'all';

    function applyFilterAndSearch() {
        const query = searchInput.value.toLowerCase().trim();
        clearBtn.style.display = query ? 'block' : 'none';

        const rows = document.querySelectorAll('.customer-row');
        const cards = document.querySelectorAll('.customer-card');

        const filterItem = (el) => {
            const name = el.getAttribute('data-name') || '';
            const comp = el.getAttribute('data-company') || '';
            const email = el.getAttribute('data-email') || '';
            const phone = el.getAttribute('data-phone') || '';
            const gstin = el.getAttribute('data-gstin') || '';
            const hasQuotes = el.getAttribute('data-has-quotes') === '1';
            const hasGst = el.getAttribute('data-has-gst') === '1';

            const matchesText = !query || name.includes(query) || comp.includes(query) || email.includes(query) || phone.includes(query) || gstin.includes(query);
            
            let matchesChip = true;
            if (currentFilter === 'has-quotes') matchesChip = hasQuotes;
            if (currentFilter === 'has-gst') matchesChip = hasGst;

            return matchesText && matchesChip;
        };

        rows.forEach(r => r.style.display = filterItem(r) ? '' : 'none');
        cards.forEach(c => c.style.display = filterItem(c) ? 'flex' : 'none');
    }

    let debounceTimer;
    searchInput?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyFilterAndSearch, 150);
    });

    clearBtn?.addEventListener('click', () => {
        searchInput.value = '';
        applyFilterAndSearch();
        searchInput.focus();
    });

    // 3. Filter Chips
    const chips = document.querySelectorAll('#filterChips button');
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('active-chip', 'btn-primary'));
            chip.classList.add('active-chip');
            currentFilter = chip.getAttribute('data-filter');
            applyFilterAndSearch();
        });
    });

    // 4. Custom Delete Confirm Modal (replaces browser confirm)
    document.querySelectorAll('.delete-cust-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');

            const confirmed = await UIEngine.confirm(
                'Delete Customer',
                `Are you sure you want to permanently delete "${name}"? This action cannot be undone.`,
                'Delete Customer',
                true
            );

            if (confirmed) {
                const form = document.getElementById('deleteCustomerForm');
                document.getElementById('deleteCustomerId').value = id;
                form.submit();
            }
        });
    });
});
</script>

<style>
.active-chip {
    background: var(--primary) !important;
    color: #ffffff !important;
    border-color: var(--primary) !important;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
