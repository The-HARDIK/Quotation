<?php
/**
 * Quotation Studio - Template: Minimalist Clean
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

    $pageTitle = 'Template Preview — Minimalist';
    $activeNav = 'templates';
    include __DIR__ . '/../includes/header.php';
    echo '<div class="page-header"><h1 class="page-title">Minimalist Proposal</h1><a href="' . BASE_URL . '/templates/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> All Templates</a></div>';
}
?>

<div class="a4-pages-container" style="display: flex; flex-direction: column; align-items: center; gap: 30px; margin: 20px 0;">
    <div class="a4-sheet" style="width: 210mm; min-height: 297mm; background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 25mm 22mm; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 13px; color: #18181b; line-height: 1.6; box-sizing: border-box; position: relative;">
        <!-- Minimalist Clean Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px;">
            <div>
                <h1 style="font-size: 26px; font-weight: 300; letter-spacing: -0.5px; margin: 0 0 4px; color: #09090b;"><?= e($businessSnapshot['company_name'] ?? 'Company') ?></h1>
                <div style="font-size: 12px; color: #71717a;"><?= e($businessSnapshot['email'] ?? '') ?> &bull; <?= e($businessSnapshot['phone'] ?? '') ?></div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #a1a1aa; font-weight: 600;">Quotation</div>
                <div style="font-size: 16px; font-weight: 600; color: #18181b; margin-top: 2px;"><?= e($quotation['quotation_number']) ?></div>
                <div style="font-size: 12px; color: #71717a; margin-top: 2px;"><?= format_date($quotation['date'] ?? '') ?></div>
            </div>
        </div>

        <!-- Recipient & Scope Strip -->
        <div style="border-top: 1px solid #f4f4f5; border-bottom: 1px solid #f4f4f5; padding: 20px 0; margin-bottom: 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
                <div style="font-size: 10.5px; text-transform: uppercase; letter-spacing: 1px; color: #a1a1aa; font-weight: 600; margin-bottom: 6px;">Prepared For</div>
                <div style="font-weight: 600; font-size: 14px;"><?= e($customerSnapshot['name'] ?? 'Client') ?></div>
                <div style="color: #71717a; font-size: 12px;"><?= e($customerSnapshot['company_name'] ?? '') ?></div>
                <div style="color: #a1a1aa; font-size: 11.5px;"><?= e($customerSnapshot['email'] ?? '') ?></div>
            </div>
            <div>
                <div style="font-size: 10.5px; text-transform: uppercase; letter-spacing: 1px; color: #a1a1aa; font-weight: 600; margin-bottom: 6px;">Subject</div>
                <div style="font-weight: 500; font-size: 13.5px; color: #27272a;"><?= e($quotation['subject']) ?></div>
                <div style="font-size: 11.5px; color: #71717a; margin-top: 4px;">Valid until: <?= format_date($quotation['valid_until']) ?></div>
            </div>
        </div>

        <!-- Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 12.5px;">
            <thead>
                <tr style="border-bottom: 1px solid #e4e4e7;">
                    <th style="padding: 10px 0; text-align: left; font-weight: 500; color: #71717a; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Description</th>
                    <th style="padding: 10px 0; text-align: center; width: 60px; font-weight: 500; color: #71717a; font-size: 11px; text-transform: uppercase;">Qty</th>
                    <th style="padding: 10px 0; text-align: right; width: 100px; font-weight: 500; color: #71717a; font-size: 11px; text-transform: uppercase;">Rate</th>
                    <th style="padding: 10px 0; text-align: right; width: 110px; font-weight: 500; color: #71717a; font-size: 11px; text-transform: uppercase;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr style="border-bottom: 1px solid #f4f4f5;">
                        <td style="padding: 12px 0;">
                            <div style="font-weight: 500; color: #09090b;"><?= e($it['item_name']) ?></div>
                            <?php if (!empty($it['item_description'])): ?>
                                <div style="font-size: 11px; color: #a1a1aa; margin-top: 2px;"><?= nl2br(e($it['item_description'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 12px 0; text-align: center; color: #71717a;"><?= rtrim(rtrim((string)$it['quantity'], '0'), '.') ?></td>
                        <td style="padding: 12px 0; text-align: right; color: #71717a;"><?= number_format((float)$it['unit_price'], 2) ?></td>
                        <td style="padding: 12px 0; text-align: right; font-weight: 500; color: #18181b;"><?= number_format((float)$it['line_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 40px;">
            <div style="width: 260px;">
                <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 12px; color: #71717a;">
                    <span>Subtotal</span>
                    <span><?= format_currency($quotation['subtotal']) ?></span>
                </div>
                <?php if ((float)$quotation['tax_total'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 12px; color: #71717a;">
                        <span>Tax</span>
                        <span><?= format_currency($quotation['tax_total']) ?></span>
                    </div>
                <?php endif; ?>
                <div style="border-top: 1px solid #18181b; display: flex; justify-content: space-between; padding: 12px 0 0; font-size: 16px; font-weight: 600; color: #09090b; margin-top: 4px;">
                    <span>Total</span>
                    <span><?= format_currency($quotation['grand_total']) ?></span>
                </div>
            </div>
        </div>

        <!-- Minimal Sign-off -->
        <div style="position: absolute; bottom: 25mm; left: 22mm; right: 22mm; display: flex; justify-content: space-between; align-items: flex-end; font-size: 11px; color: #a1a1aa; border-top: 1px solid #f4f4f5; padding-top: 14px;">
            <div><?= e($businessSnapshot['company_name'] ?? '') ?> &bull; Quotation Studio</div>
            <div>Authorized: <strong><?= e($businessSnapshot['authorized_signatory'] ?? 'Director') ?></strong></div>
        </div>
    </div>
</div>

<?php
if (!isset($quotation)) {
    include __DIR__ . '/../includes/footer.php';
}
?>
