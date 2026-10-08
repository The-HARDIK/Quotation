<?php
/**
 * Quotation Studio - Template: Modern Clean (Tech / SaaS / Agency)
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
    $introSection = json_decode($quotation['intro_section'] ?? '[]', true) ?: [];
    $scopeSection = json_decode($quotation['scope_section'] ?? '[]', true) ?: [];
    $termsConditions = json_decode($quotation['terms_conditions'] ?? '[]', true) ?: [];
    $systemRequirements = json_decode($quotation['system_requirements'] ?? '[]', true) ?: [];

    $pageTitle = 'Template Preview — Modern Clean';
    $activeNav = 'templates';
    include __DIR__ . '/../includes/header.php';
    echo '<div class="page-header"><h1 class="page-title">Modern Clean Proposal</h1><a href="' . BASE_URL . '/templates/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> All Templates</a></div>';
}

$primary = '#2563eb';
$accent = '#06b6d4';
?>

<div class="a4-pages-container" style="display: flex; flex-direction: column; align-items: center; gap: 30px; margin: 20px 0;">
    <!-- Modern Single/Multi Page Container -->
    <div class="a4-sheet" style="width: 210mm; min-height: 297mm; background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 20mm; font-family: 'Inter', sans-serif; font-size: 13px; color: #1e293b; line-height: 1.55; box-sizing: border-box; position: relative;">
        <!-- Top Modern Header Band -->
        <div style="display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #1e40af, #2563eb); color: #ffffff; padding: 24px; border-radius: 8px; margin-bottom: 24px;">
            <div>
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Commercial Proposal</div>
                <h1 style="font-family: Outfit, sans-serif; font-size: 26px; margin: 4px 0 0; font-weight: 700;"><?= e($businessSnapshot['company_name'] ?? 'Company Name') ?></h1>
                <div style="font-size: 12px; opacity: 0.9; margin-top: 4px;"><?= e($businessSnapshot['tagline'] ?? '') ?></div>
            </div>
            <div style="text-align: right; font-size: 12px;">
                <div style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-weight: 600; display: inline-block; margin-bottom: 6px;">
                    <?= e($quotation['quotation_number']) ?>
                </div>
                <div>Date: <strong><?= format_date($quotation['date'] ?? '') ?></strong></div>
                <div>Valid Until: <strong><?= format_date($quotation['valid_until']) ?></strong></div>
            </div>
        </div>

        <!-- Client & Subject Row -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
            <div style="background: #f8fafc; padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 6px;">Proposal Prepared For:</div>
                <div style="font-weight: 700; font-size: 15px; color: #0f172a;"><?= e($customerSnapshot['name'] ?? 'Valued Client') ?></div>
                <div style="font-weight: 600; color: #3b82f6;"><?= e($customerSnapshot['company_name'] ?? '') ?></div>
                <div style="color: #64748b; font-size: 12px; margin-top: 4px;"><?= nl2br(e($customerSnapshot['billing_address'] ?? '')) ?></div>
                <?php if (!empty($customerSnapshot['gstin'])): ?>
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">GSTIN: <?= e($customerSnapshot['gstin']) ?></div>
                <?php endif; ?>
            </div>

            <div style="background: #eff6ff; padding: 16px; border-radius: 6px; border: 1px solid #bfdbfe;">
                <div style="font-size: 11px; text-transform: uppercase; color: #1e40af; font-weight: 700; margin-bottom: 6px;">Engagement Overview:</div>
                <div style="font-weight: 700; color: #1e3a8a; font-size: 14px; margin-bottom: 6px;"><?= e($quotation['subject']) ?></div>
                <div style="font-size: 12px; color: #3b82f6;"><?= e($introSection['greeting'] ?? 'Dear Sir / Madam,') ?></div>
                <div style="font-size: 11.5px; color: #475569; margin-top: 4px; line-height: 1.4;"><?= substr(strip_tags($introSection['company_overview'] ?? ''), 0, 160) ?>...</div>
            </div>
        </div>

        <!-- Modern Commercial Table -->
        <table style="width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px; font-size: 12.5px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;">
            <thead>
                <tr style="background: #f1f5f9; color: #334155;">
                    <th style="padding: 10px 14px; text-align: left;">Item Description</th>
                    <th style="padding: 10px 14px; text-align: center; width: 80px;">Qty</th>
                    <th style="padding: 10px 14px; text-align: right; width: 100px;">Rate (₹)</th>
                    <th style="padding: 10px 14px; text-align: right; width: 110px;">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $it): ?>
                    <tr style="border-top: 1px solid #f1f5f9;">
                        <td style="padding: 10px 14px; border-top: 1px solid #e2e8f0;">
                            <div style="font-weight: 600; color: #0f172a;"><?= e($it['item_name']) ?></div>
                            <?php if (!empty($it['item_description'])): ?>
                                <div style="font-size: 11px; color: #64748b;"><?= nl2br(e($it['item_description'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px 14px; text-align: center; border-top: 1px solid #e2e8f0;"><?= rtrim(rtrim((string)$it['quantity'], '0'), '.') ?></td>
                        <td style="padding: 10px 14px; text-align: right; border-top: 1px solid #e2e8f0;"><?= number_format((float)$it['unit_price'], 2) ?></td>
                        <td style="padding: 10px 14px; text-align: right; font-weight: 600; border-top: 1px solid #e2e8f0;"><?= number_format((float)$it['line_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                    <td colspan="3" style="padding: 8px 14px; text-align: right; font-weight: 600;">Subtotal:</td>
                    <td style="padding: 8px 14px; text-align: right; font-weight: 600;"><?= format_currency($quotation['subtotal']) ?></td>
                </tr>
                <?php if ((float)$quotation['tax_total'] > 0): ?>
                    <tr style="background: #f8fafc;">
                        <td colspan="3" style="padding: 6px 14px; text-align: right; color: #64748b;">Tax (GST):</td>
                        <td style="padding: 6px 14px; text-align: right;"><?= format_currency($quotation['tax_total']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr style="background: #eff6ff;">
                    <td colspan="3" style="padding: 12px 14px; text-align: right; font-weight: 800; font-size: 15px; color: #1e40af;">Total Investment:</td>
                    <td style="padding: 12px 14px; text-align: right; font-weight: 800; font-size: 16px; color: #1e40af;"><?= format_currency($quotation['grand_total']) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Terms & Remittance -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px;">
            <div style="background: #f8fafc; padding: 14px; border-radius: 6px; font-size: 11px;">
                <div style="font-weight: 700; color: #1e40af; margin-bottom: 6px; text-transform: uppercase;">Key Terms:</div>
                <?php foreach (array_slice($termsConditions, 0, 4) as $t): ?>
                    <div style="margin-bottom: 4px;">&bull; <?= e($t) ?></div>
                <?php endforeach; ?>
            </div>
            <div style="background: #f8fafc; padding: 14px; border-radius: 6px; font-size: 11px;">
                <div style="font-weight: 700; color: #1e40af; margin-bottom: 6px; text-transform: uppercase;">Bank Details:</div>
                <div><strong>Bank:</strong> <?= e($businessSnapshot['bank_name'] ?? '—') ?></div>
                <div><strong>A/C:</strong> <?= e($businessSnapshot['account_number'] ?? '—') ?></div>
                <div><strong>IFSC:</strong> <?= e($businessSnapshot['ifsc_code'] ?? '—') ?></div>
                <div><strong>A/C Name:</strong> <?= e($businessSnapshot['account_name'] ?? '—') ?></div>
            </div>
        </div>

        <!-- Footer Sign-off -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 36px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
            <div style="font-size: 11px; color: #94a3b8;">
                Prepared with Quotation Studio &bull; <?= e($businessSnapshot['website'] ?? '') ?>
            </div>
            <div style="text-align: right;">
                <div style="font-weight: 700; color: #0f172a;"><?= e($businessSnapshot['authorized_signatory'] ?? 'Authorized Signatory') ?></div>
                <div style="font-size: 11px; color: #64748b;"><?= e($businessSnapshot['company_name'] ?? '') ?></div>
            </div>
        </div>
    </div>
</div>

<?php
if (!isset($quotation)) {
    include __DIR__ . '/../includes/footer.php';
}
?>
