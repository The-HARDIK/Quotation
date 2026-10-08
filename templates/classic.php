<?php
/**
 * Quotation Studio - Template: Commercial Proposal – Classic (3-Page Enterprise)
 * Recreates the exact structure, layout, and visual hierarchy of the reference proposal.
 */

// If accessed directly, load sample preview
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
        die('Please create at least one quotation first to preview templates.');
    }

    $id = (int)$quotation['id'];
    $itemsStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY item_order ASC");
    $itemsStmt->execute([$id]);
    $items = $itemsStmt->fetchAll();

    $customerSnapshot = json_decode($quotation['customer_snapshot'] ?? '[]', true) ?: [];
    $businessSnapshot = json_decode($quotation['business_snapshot'] ?? '[]', true) ?: [];
    $introSection = json_decode($quotation['intro_section'] ?? '[]', true) ?: [];
    $scopeSection = json_decode($quotation['scope_section'] ?? '[]', true) ?: [];
    $customizationSection = json_decode($quotation['customization_section'] ?? '[]', true) ?: [];
    $systemRequirements = json_decode($quotation['system_requirements'] ?? '[]', true) ?: [];
    $termsConditions = json_decode($quotation['terms_conditions'] ?? '[]', true) ?: [];
    $notesSection = json_decode($quotation['notes_section'] ?? '[]', true) ?: [];

    $pageTitle = 'Template Preview — Commercial Proposal Classic';
    $activeNav = 'templates';
    include __DIR__ . '/../includes/header.php';
    echo '<div class="page-header"><h1 class="page-title">Commercial Proposal – Classic (3-Page Replica)</h1><a href="' . BASE_URL . '/templates/index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> All Templates</a></div>';
}

$primary = $businessSnapshot['primary_color'] ?? '#0d5c75';
$secondary = $businessSnapshot['secondary_color'] ?? '#85a438';
?>

<div class="a4-pages-container" style="display: flex; flex-direction: column; align-items: center; gap: 30px; margin: 20px 0;">
    <!-- ================= PAGE 1 ================= -->
    <div class="a4-sheet" style="width: 210mm; min-height: 297mm; background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 18mm 20mm; font-family: 'Inter', sans-serif; font-size: 13px; color: #1e293b; line-height: 1.55; box-sizing: border-box; position: relative;">
        <!-- Header Branding Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid <?= e($primary) ?>; padding-bottom: 14px; margin-bottom: 22px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <?php if (!empty($businessSnapshot['logo'])): ?>
                    <img src="<?= BASE_URL ?>/<?= e($businessSnapshot['logo']) ?>" alt="Logo" style="max-height: 48px; max-width: 160px; object-fit: contain;">
                <?php else: ?>
                    <div style="font-family: Outfit, sans-serif; font-weight: 800; font-size: 22px; color: <?= e($primary) ?>;"><?= e($businessSnapshot['company_name'] ?? 'COMPANY') ?></div>
                <?php endif; ?>
            </div>
            <div style="text-align: right; font-size: 11.5px; color: #64748b; line-height: 1.4;">
                <div style="font-weight: 700; color: <?= e($primary) ?>; font-size: 13px;"><?= e($businessSnapshot['company_name'] ?? '') ?></div>
                <div><?= e($businessSnapshot['phone'] ?? '') ?> | <?= e($businessSnapshot['email'] ?? '') ?></div>
                <div><?= e($businessSnapshot['website'] ?? '') ?></div>
            </div>
        </div>

        <!-- Reference & Date Grid -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 18px; font-size: 12.5px;">
            <div><strong>Ref:</strong> <?= e($quotation['quotation_number']) ?></div>
            <div><strong>Date:</strong> <?= format_date($quotation['date'] ?? '') ?></div>
        </div>

        <!-- Recipient Block -->
        <div style="margin-bottom: 18px; background: #f8fafc; padding: 12px 16px; border-left: 3px solid <?= e($primary) ?>; border-radius: 4px;">
            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; margin-bottom: 4px;">To,</div>
            <div style="font-weight: 700; font-size: 14px; color: #0f172a;"><?= e($customerSnapshot['name'] ?? 'Client Contact') ?></div>
            <div style="font-weight: 600; color: #334155;"><?= e($customerSnapshot['company_name'] ?? '') ?></div>
            <div style="color: #64748b; font-size: 12px;"><?= nl2br(e($customerSnapshot['billing_address'] ?? '')) ?></div>
            <?php if (!empty($customerSnapshot['gstin'])): ?>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;"><strong>GSTIN:</strong> <?= e($customerSnapshot['gstin']) ?></div>
            <?php endif; ?>
        </div>

        <!-- Subject -->
        <div style="margin-bottom: 16px; font-weight: 700; color: <?= e($primary) ?>; font-size: 13.5px;">
            <u>Subject: <?= e($quotation['subject']) ?></u>
        </div>

        <!-- Greeting -->
        <div style="margin-bottom: 12px; font-weight: 600;">
            <?= e($introSection['greeting'] ?? 'Dear Sir / Madam,') ?>
        </div>

        <!-- Narrative -->
        <div style="margin-bottom: 16px; text-align: justify;">
            <?= nl2br(e($introSection['company_overview'] ?? '')) ?>
        </div>

        <!-- Capabilities Bullet Points -->
        <?php if (!empty($introSection['capabilities'])): ?>
            <div style="font-weight: 700; color: #0f172a; margin-bottom: 8px;">Key Capabilities & Core Competencies:</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; margin-bottom: 18px;">
                <?php foreach ($introSection['capabilities'] as $cap): ?>
                    <div style="display: flex; align-items: flex-start; gap: 6px; font-size: 12px;">
                        <span style="color: <?= e($secondary) ?>; font-weight: 800;">&#10003;</span>
                        <span><?= e($cap) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Closing Paragraph Page 1 -->
        <div style="text-align: justify; margin-bottom: 20px;">
            <?= nl2br(e($introSection['closing_paragraph'] ?? '')) ?>
        </div>

        <!-- Page 1 Bottom Indicator -->
        <div style="position: absolute; bottom: 12mm; left: 20mm; right: 20mm; display: flex; justify-content: space-between; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px;">
            <span><?= e($businessSnapshot['company_name'] ?? '') ?> — Commercial Proposal</span>
            <span>Page 1 of 3</span>
        </div>
    </div>

    <!-- ================= PAGE 2 ================= -->
    <div class="a4-sheet" style="width: 210mm; min-height: 297mm; background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 18mm 20mm; font-family: 'Inter', sans-serif; font-size: 13px; color: #1e293b; line-height: 1.55; box-sizing: border-box; position: relative;">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; font-size: 11px; color: #64748b;">
            <span>Ref: <strong><?= e($quotation['quotation_number']) ?></strong></span>
            <span style="color: <?= e($primary) ?>; font-weight: 700;">COMMERCIAL PROPOSAL</span>
            <span>Date: <?= format_date($quotation['date'] ?? '') ?></span>
        </div>

        <h3 style="font-family: Outfit, sans-serif; font-size: 16px; color: <?= e($primary) ?>; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
            Commercial Proposal & Investment Details
        </h3>

        <!-- Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 12px;">
            <thead>
                <tr style="background: <?= e($primary) ?>; color: #ffffff;">
                    <th style="padding: 9px 12px; text-align: left; width: 40px;">#</th>
                    <th style="padding: 9px 12px; text-align: left;">Particulars / Description</th>
                    <th style="padding: 9px 12px; text-align: center; width: 60px;">Qty</th>
                    <th style="padding: 9px 12px; text-align: right; width: 90px;">Rate (₹)</th>
                    <th style="padding: 9px 12px; text-align: right; width: 100px;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $it): ?>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 8px 12px;"><?= $idx + 1 ?></td>
                        <td style="padding: 8px 12px;">
                            <div style="font-weight: 600; color: #0f172a;"><?= e($it['item_name']) ?></div>
                            <?php if (!empty($it['item_description'])): ?>
                                <div style="font-size: 11px; color: #64748b;"><?= nl2br(e($it['item_description'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 8px 12px; text-align: center;"><?= rtrim(rtrim((string)$it['quantity'], '0'), '.') ?> <?= e($it['unit']) ?></td>
                        <td style="padding: 8px 12px; text-align: right;"><?= number_format((float)$it['unit_price'], 2) ?></td>
                        <td style="padding: 8px 12px; text-align: right; font-weight: 600;"><?= number_format((float)$it['line_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="border-top: 1.5px solid #cbd5e1;">
                    <td colspan="4" style="padding: 7px 12px; text-align: right; font-weight: 600;">Subtotal:</td>
                    <td style="padding: 7px 12px; text-align: right; font-weight: 600;"><?= format_currency($quotation['subtotal']) ?></td>
                </tr>
                <?php if ((float)$quotation['discount_total'] > 0): ?>
                    <tr>
                        <td colspan="4" style="padding: 5px 12px; text-align: right; color: #dc2626;">Discount:</td>
                        <td style="padding: 5px 12px; text-align: right; color: #dc2626;">-<?= format_currency($quotation['discount_total']) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ((float)$quotation['tax_total'] > 0): ?>
                    <tr>
                        <td colspan="4" style="padding: 5px 12px; text-align: right; color: #475569;">
                            Tax (<?= $quotation['tax_type'] ?>):
                        </td>
                        <td style="padding: 5px 12px; text-align: right;"><?= format_currency($quotation['tax_total']) ?></td>
                    </tr>
                <?php endif; ?>
                <tr style="background: #f1f5f9;">
                    <td colspan="4" style="padding: 9px 12px; text-align: right; font-weight: 800; font-size: 13px; color: <?= e($primary) ?>;">Grand Total:</td>
                    <td style="padding: 9px 12px; text-align: right; font-weight: 800; font-size: 14px; color: <?= e($primary) ?>;"><?= format_currency($quotation['grand_total']) ?></td>
                </tr>
            </tfoot>
        </table>

        <!-- Amount in Words -->
        <div style="font-size: 11.5px; color: #475569; margin-bottom: 18px; background: #f8fafc; padding: 6px 12px; border-radius: 4px;">
            <strong>Amount in Words:</strong> <?= e($quotation['amount_in_words'] ?? '') ?>
        </div>

        <!-- Scope & Inclusions -->
        <?php if (!empty($scopeSection)): ?>
            <div style="margin-bottom: 16px;">
                <div style="font-weight: 700; color: <?= e($primary) ?>; font-size: 12px; text-transform: uppercase; margin-bottom: 6px;">Subscription / Proposal Inclusions:</div>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px 14px; font-size: 11.5px;">
                    <?php foreach ($scopeSection as $sc): ?>
                        <div style="margin-bottom: 4px;">&bull; <?= e($sc) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Hardware Specs -->
        <?php if (!empty($systemRequirements)): ?>
            <div style="margin-bottom: 16px;">
                <div style="font-weight: 700; color: <?= e($primary) ?>; font-size: 12px; text-transform: uppercase; margin-bottom: 6px;">System / Hardware Prerequisites:</div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px; font-size: 11.5px;">
                    <?php foreach ($systemRequirements as $k => $v): ?>
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 10px; border-radius: 4px;">
                            <strong style="color: #64748b;"><?= e($k) ?>:</strong> <?= e($v) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page 2 Bottom Indicator -->
        <div style="position: absolute; bottom: 12mm; left: 20mm; right: 20mm; display: flex; justify-content: space-between; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px;">
            <span><?= e($businessSnapshot['company_name'] ?? '') ?> — Commercial Proposal</span>
            <span>Page 2 of 3</span>
        </div>
    </div>

    <!-- ================= PAGE 3 ================= -->
    <div class="a4-sheet" style="width: 210mm; min-height: 297mm; background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.1); padding: 18mm 20mm; font-family: 'Inter', sans-serif; font-size: 13px; color: #1e293b; line-height: 1.55; box-sizing: border-box; position: relative;">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 20px; font-size: 11px; color: #64748b;">
            <span>Ref: <strong><?= e($quotation['quotation_number']) ?></strong></span>
            <span style="color: <?= e($primary) ?>; font-weight: 700;">TERMS & CONDITIONS</span>
            <span>Date: <?= format_date($quotation['date'] ?? '') ?></span>
        </div>

        <h3 style="font-family: Outfit, sans-serif; font-size: 16px; color: <?= e($primary) ?>; margin-bottom: 12px; text-transform: uppercase;">
            Terms & Conditions of Proposal
        </h3>

        <!-- Terms Clauses -->
        <div style="margin-bottom: 20px; font-size: 11.5px; line-height: 1.6; color: #334155;">
            <?php foreach ($termsConditions as $tIdx => $tc): ?>
                <div style="margin-bottom: 6px; display: flex; gap: 8px;">
                    <span style="font-weight: 700; color: <?= e($primary) ?>; min-width: 20px;"><?= $tIdx + 1 ?>.</span>
                    <div><?= nl2br(e($tc)) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bank Details Box -->
        <div style="border: 1.5px dashed <?= e($primary) ?>; border-radius: 6px; padding: 14px 18px; margin-bottom: 24px; background: #f8fafc;">
            <div style="font-weight: 700; color: <?= e($primary) ?>; font-size: 12.5px; text-transform: uppercase; margin-bottom: 6px;">Remittance & Bank Settlement Details:</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px 16px; font-size: 11.5px;">
                <div><strong>Bank Name:</strong> <?= e($businessSnapshot['bank_name'] ?? '—') ?></div>
                <div><strong>Account Name:</strong> <?= e($businessSnapshot['account_name'] ?? '—') ?></div>
                <div><strong>Account Number:</strong> <?= e($businessSnapshot['account_number'] ?? '—') ?></div>
                <div><strong>IFSC Code:</strong> <?= e($businessSnapshot['ifsc_code'] ?? '—') ?></div>
                <div><strong>Branch:</strong> <?= e($businessSnapshot['branch'] ?? '—') ?></div>
                <?php if (!empty($businessSnapshot['upi_id'])): ?>
                    <div><strong>UPI ID:</strong> <?= e($businessSnapshot['upi_id']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Authorized Signatory -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 30px; margin-bottom: 30px;">
            <div>
                <div style="font-size: 11px; color: #64748b;">Client Acceptance Signature</div>
                <div style="width: 180px; border-bottom: 1px solid #cbd5e1; margin-top: 40px;"></div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Authorised Signatory & Date</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 11px; color: #64748b;">For <strong><?= e($businessSnapshot['company_name'] ?? '') ?></strong></div>
                <?php if (!empty($businessSnapshot['signature_image'])): ?>
                    <img src="<?= BASE_URL ?>/<?= e($businessSnapshot['signature_image']) ?>" alt="Signature" style="max-height: 44px; margin-top: 8px;">
                <?php else: ?>
                    <div style="height: 40px;"></div>
                <?php endif; ?>
                <div style="font-weight: 700; font-size: 13px; color: #0f172a; margin-top: 4px;"><?= e($businessSnapshot['authorized_signatory'] ?? 'Authorized Person') ?></div>
                <div style="font-size: 11px; color: #64748b;"><?= e($businessSnapshot['designation'] ?? 'Director') ?></div>
            </div>
        </div>

        <!-- Page 3 Bottom Indicator -->
        <div style="position: absolute; bottom: 12mm; left: 20mm; right: 20mm; display: flex; justify-content: space-between; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 6px;">
            <span><?= e($businessSnapshot['company_name'] ?? '') ?> — Commercial Proposal</span>
            <span>Page 3 of 3</span>
        </div>
    </div>
</div>

<?php
if (!isset($quotation)) {
    include __DIR__ . '/../includes/footer.php';
}
?>
