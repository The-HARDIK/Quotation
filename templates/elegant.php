<?php
/**
 * Quotation Studio - Template: Elegant Slate & Gold
 */

if (!isset($quotation)) {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_auth();

    $pdo = get_db();
    $userId = current_user_id();
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    $quotation = $stmt->fetch();

    if (!$quotation) {
        die('Please create at least one quotation first.');
    }

    $id = (int)$quotation['id'];
    $itemsStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY item_order ASC");
    $itemsStmt->execute([$id]);
    $items = $itemsStmt->fetchAll();

    $customerSnapshot = json_decode($quotation['customer_snapshot'] ?? '[]', true) ?: [];
    $businessSnapshot = json_decode($quotation['business_snapshot'] ?? '[]', true) ?: [];
    $termsConditions = json_decode($quotation['terms_conditions'] ?? '[]', true) ?: [];

    $pageTitle = 'Template Preview — Elegant Slate & Gold';
    $activeNav = 'templates';
    include __DIR__ . '/../includes/header.php';
    echo '<div class="page-header"><h1 class="page-title">Elegant Proposal</h1><a href="' . BASE_URL . '/templates/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> All Templates</a></div>';
}
?>

<div class="a4-pages-container" style="display: flex; flex-direction: column; align-items: center; gap: 30px; margin: 20px 0;">
    <div class="a4-sheet" style="width: 210mm; min-height: 297mm; background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 22mm; font-family: 'Georgia', serif; font-size: 13px; color: #1c1917; line-height: 1.6; box-sizing: border-box; position: relative;">
        <!-- Gold Top Bar -->
        <div style="height: 4px; background: linear-gradient(90deg, #d97706, #fbbf24, #d97706); margin: -22mm -22mm 24mm -22mm;"></div>

        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px;">
            <div>
                <h1 style="font-size: 26px; font-weight: normal; margin: 0; color: #1c1917; letter-spacing: 0.5px;"><?= e($businessSnapshot['company_name'] ?? 'Company') ?></h1>
                <div style="font-style: italic; font-size: 12px; color: #b45309; margin-top: 4px;"><?= e($businessSnapshot['tagline'] ?? 'Commercial Proposal & Service Agreement') ?></div>
            </div>
            <div style="text-align: right; font-family: sans-serif; font-size: 11.5px; color: #78716c;">
                <div style="color: #b45309; font-weight: bold; font-size: 13px;"><?= e($quotation['quotation_number']) ?></div>
                <div>Date: <?= format_date($quotation['date'] ?? '') ?></div>
                <div>Valid: <?= format_date($quotation['valid_until']) ?></div>
            </div>
        </div>

        <div style="background: #fafaf9; border-left: 3px solid #d97706; padding: 14px 18px; margin-bottom: 24px; font-family: sans-serif; font-size: 12px; display: flex; justify-content: space-between;">
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #78716c; font-weight: bold;">Presented To:</div>
                <div style="font-weight: bold; font-size: 14px; color: #1c1917;"><?= e($customerSnapshot['name'] ?? 'Client') ?></div>
                <div><?= e($customerSnapshot['company_name'] ?? '') ?></div>
                <div style="color: #78716c;"><?= nl2br(e($customerSnapshot['billing_address'] ?? '')) ?></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #78716c; font-weight: bold;">Engagement:</div>
                <div style="font-weight: bold; color: #1c1917;"><?= e($quotation['subject']) ?></div>
            </div>
        </div>

        <!-- Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 12px;">
            <thead>
                <tr style="border-bottom: 2px solid #b45309; color: #78716c; font-family: sans-serif; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <th style="padding: 10px 8px; text-align: left;">Item & Description</th>
                    <th style="padding: 10px 8px; text-align: center; width: 60px;">Qty</th>
                    <th style="padding: 10px 8px; text-align: right; width: 100px;">Rate (₹)</th>
                    <th style="padding: 10px 8px; text-align: right; width: 110px;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr style="border-bottom: 1px solid #f5f5f4;">
                        <td style="padding: 10px 8px;">
                            <strong><?= e($it['item_name']) ?></strong>
                            <?php if (!empty($it['item_description'])): ?>
                                <div style="font-size: 11px; color: #78716c; font-style: italic;"><?= nl2br(e($it['item_description'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px 8px; text-align: center; font-family: sans-serif;"><?= rtrim(rtrim((string)$it['quantity'], '0'), '.') ?></td>
                        <td style="padding: 10px 8px; text-align: right; font-family: sans-serif;"><?= number_format((float)$it['unit_price'], 2) ?></td>
                        <td style="padding: 10px 8px; text-align: right; font-family: sans-serif; font-weight: bold;"><?= number_format((float)$it['line_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="border-top: 1.5px solid #d6d3d1;">
                    <td colspan="3" style="padding: 8px 8px; text-align: right; font-family: sans-serif; font-size: 11.5px;">Subtotal:</td>
                    <td style="padding: 8px 8px; text-align: right; font-family: sans-serif; font-weight: bold;"><?= format_currency($quotation['subtotal']) ?></td>
                </tr>
                <?php if ((float)$quotation['tax_total'] > 0): ?>
                    <tr>
                        <td colspan="3" style="padding: 6px 8px; text-align: right; font-family: sans-serif; font-size: 11.5px; color: #78716c;">GST Tax:</td>
                        <td style="padding: 6px 8px; text-align: right; font-family: sans-serif;"><?= format_currency($quotation['tax_total']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr style="background: #fef3c7;">
                    <td colspan="3" style="padding: 10px 8px; text-align: right; font-family: sans-serif; font-weight: bold; font-size: 13px; color: #92400e;">Total Value:</td>
                    <td style="padding: 10px 8px; text-align: right; font-family: sans-serif; font-weight: bold; font-size: 14px; color: #92400e;"><?= format_currency($quotation['grand_total']) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Sign-off -->
        <div style="position: absolute; bottom: 22mm; left: 22mm; right: 22mm; display: flex; justify-content: space-between; align-items: flex-end; font-family: sans-serif; font-size: 11px; border-top: 1px solid #e7e5e4; padding-top: 16px;">
            <div style="color: #a8a29e;">
                <?= e($businessSnapshot['company_name'] ?? '') ?> &bull; Bank: <?= e($businessSnapshot['bank_name'] ?? '') ?>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: bold; color: #1c1917;"><?= e($businessSnapshot['authorized_signatory'] ?? 'Authorized Signatory') ?></div>
                <div style="color: #78716c;"><?= e($businessSnapshot['designation'] ?? 'Managing Director') ?></div>
            </div>
        </div>
    </div>
</div>

<?php
if (!isset($quotation)) {
    include __DIR__ . '/../includes/footer.php';
}
?>
