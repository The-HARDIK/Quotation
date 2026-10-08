<?php
/**
 * Quotation Studio - Template: Executive Corporate (Formal Enterprise / Tenders)
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

    $pageTitle = 'Template Preview — Executive Corporate';
    $activeNav = 'templates';
    include __DIR__ . '/../includes/header.php';
    echo '<div class="page-header"><h1 class="page-title">Executive Corporate Proposal</h1><a href="' . BASE_URL . '/templates/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> All Templates</a></div>';
}
?>

<div class="a4-pages-container" style="display: flex; flex-direction: column; align-items: center; gap: 30px; margin: 20px 0;">
    <div class="a4-sheet" style="width: 210mm; min-height: 297mm; background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 22mm; font-family: 'Times New Roman', Times, serif; font-size: 13px; color: #0f172a; line-height: 1.6; box-sizing: border-box; position: relative;">
        <!-- Corporate Border -->
        <div style="border-top: 4px solid #1e293b; border-bottom: 1px solid #1e293b; padding: 16px 0; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 22px; font-weight: bold; margin: 0; text-transform: uppercase; letter-spacing: 1px; color: #0f172a;"><?= e($businessSnapshot['company_name'] ?? 'COMPANY') ?></h1>
                <div style="font-family: sans-serif; font-size: 11px; color: #475569; margin-top: 4px;">CIN / GSTIN: <?= e($businessSnapshot['gstin'] ?? '—') ?> | PAN: <?= e($businessSnapshot['pan'] ?? '—') ?></div>
            </div>
            <div style="text-align: right; font-family: sans-serif; font-size: 11px; color: #334155;">
                <div style="font-weight: bold; font-size: 13px;">COMMERCIAL TENDER PROPOSAL</div>
                <div>Bid Ref: <strong><?= e($quotation['quotation_number']) ?></strong></div>
                <div>Issue Date: <?= format_date($quotation['date'] ?? '') ?></div>
            </div>
        </div>

        <div style="font-family: sans-serif; font-size: 12px; margin-bottom: 20px; display: flex; justify-content: space-between;">
            <div>
                <strong>TO:</strong><br>
                <?= e($customerSnapshot['company_name'] ?? $customerSnapshot['name']) ?><br>
                <?= nl2br(e($customerSnapshot['billing_address'] ?? '')) ?><br>
                Attn: <?= e($customerSnapshot['name'] ?? 'Authorized Signatory') ?>
            </div>
            <div style="text-align: right;">
                <strong>VALIDITY:</strong> <?= format_date($quotation['valid_until']) ?><br>
                <strong>CURRENCY:</strong> INR (₹)<br>
                <strong>PAYMENT:</strong> <?= e($quotation['payment_terms'] ?? 'Standard Terms') ?>
            </div>
        </div>

        <div style="font-weight: bold; margin-bottom: 16px; font-size: 14px; text-decoration: underline;">
            SUBJECT: <?= strtoupper(e($quotation['subject'])) ?>
        </div>

        <p style="text-align: justify; margin-bottom: 16px;">
            <?= nl2br(e($introSection['company_overview'] ?? '')) ?>
        </p>

        <!-- Formal Corporate Grid Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-family: sans-serif; font-size: 11.5px; border: 1.5px solid #1e293b;">
            <thead>
                <tr style="background: #1e293b; color: #ffffff;">
                    <th style="border: 1px solid #1e293b; padding: 8px 10px; text-align: center; width: 40px;">SR.</th>
                    <th style="border: 1px solid #1e293b; padding: 8px 10px; text-align: left;">SCHEDULE OF REQUIREMENTS</th>
                    <th style="border: 1px solid #1e293b; padding: 8px 10px; text-align: center; width: 60px;">QTY</th>
                    <th style="border: 1px solid #1e293b; padding: 8px 10px; text-align: right; width: 90px;">UNIT RATE (₹)</th>
                    <th style="border: 1px solid #1e293b; padding: 8px 10px; text-align: right; width: 100px;">TOTAL (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $it): ?>
                    <tr>
                        <td style="border: 1px solid #cbd5e1; padding: 8px 10px; text-align: center;"><?= $idx + 1 ?></td>
                        <td style="border: 1px solid #cbd5e1; padding: 8px 10px;">
                            <strong><?= e($it['item_name']) ?></strong>
                            <?php if (!empty($it['item_description'])): ?>
                                <div style="font-size: 10.5px; color: #475569;"><?= nl2br(e($it['item_description'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="border: 1px solid #cbd5e1; padding: 8px 10px; text-align: center;"><?= rtrim(rtrim((string)$it['quantity'], '0'), '.') ?></td>
                        <td style="border: 1px solid #cbd5e1; padding: 8px 10px; text-align: right;"><?= number_format((float)$it['unit_price'], 2) ?></td>
                        <td style="border: 1px solid #cbd5e1; padding: 8px 10px; text-align: right; font-weight: bold;"><?= number_format((float)$it['line_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right; font-weight: bold;">SUBTOTAL:</td>
                    <td style="border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right; font-weight: bold;"><?= format_currency($quotation['subtotal']) ?></td>
                </tr>
                <?php if ((float)$quotation['tax_total'] > 0): ?>
                    <tr>
                        <td colspan="4" style="border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right;">STATUTORY TAX (GST):</td>
                        <td style="border: 1px solid #cbd5e1; padding: 6px 10px; text-align: right;"><?= format_currency($quotation['tax_total']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr style="background: #f1f5f9;">
                    <td colspan="4" style="border: 1.5px solid #1e293b; padding: 8px 10px; text-align: right; font-weight: bold; font-size: 13px;">GRAND CONTRACT TOTAL:</td>
                    <td style="border: 1.5px solid #1e293b; padding: 8px 10px; text-align: right; font-weight: bold; font-size: 13px;"><?= format_currency($quotation['grand_total']) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Amount In Words -->
        <div style="font-family: sans-serif; font-size: 11px; margin-bottom: 24px; padding: 6px 10px; background: #f8fafc; border: 1px solid #e2e8f0;">
            <strong>Amount Chargeable (in words):</strong> <?= e($quotation['amount_in_words'] ?? '') ?>
        </div>

        <!-- Authorized Sign-off Box -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; font-family: sans-serif;">
            <div>
                <div style="font-size: 11px; color: #64748b;">Accepted & Confirmed:</div>
                <div style="border-bottom: 1px solid #000; width: 180px; margin-top: 40px;"></div>
                <div style="font-size: 10px; color: #475569; margin-top: 4px;">Client Authorized Representative</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; color: #64748b;">For and on behalf of:</div>
                <div style="font-weight: bold;"><?= e($businessSnapshot['company_name'] ?? '') ?></div>
                <div style="height: 40px;"></div>
                <div style="font-weight: bold;"><?= e($businessSnapshot['authorized_signatory'] ?? 'Authorized Signatory') ?></div>
                <div style="font-size: 11px; color: #475569;"><?= e($businessSnapshot['designation'] ?? 'Director') ?></div>
            </div>
        </div>
    </div>
</div>

<?php
if (!isset($quotation)) {
    include __DIR__ . '/../includes/footer.php';
}
?>
