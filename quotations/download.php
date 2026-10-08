<?php
/**
 * Quotation Studio - PDF Generation via Dompdf
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$id = (int)($_GET['id'] ?? 0);
$userId = current_user_id();
$pdo = get_db();

$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$quotation = $stmt->fetch();

if (!$quotation) {
    die("Quotation not found.");
}

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

$primary = $businessSnapshot['primary_color'] ?? '#0d5c75';
$secondary = $businessSnapshot['secondary_color'] ?? '#85a438';

// Build Standalone HTML for Dompdf
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?= e($quotation['quotation_number']) ?></title>
<style>
    @page {
        size: A4 portrait;
        margin: 12mm 14mm 14mm 14mm;
    }
    body {
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        font-size: 11px;
        color: #1e293b;
        line-height: 1.45;
        margin: 0;
        padding: 0;
    }
    .page-break {
        page-break-after: always;
    }
    .header-bar {
        border-bottom: 2px solid <?= $primary ?>;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }
    .meta-table {
        width: 100%;
        margin-bottom: 10px;
        font-size: 11px;
    }
    .recipient-box {
        background: #f8fafc;
        border-left: 3px solid <?= $primary ?>;
        padding: 8px 12px;
        margin-bottom: 12px;
        font-size: 11px;
    }
    .subject-line {
        font-weight: bold;
        font-size: 12px;
        margin-bottom: 10px;
        text-decoration: underline;
    }
    .commercial-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        font-size: 10.5px;
    }
    .commercial-table th {
        background: <?= $primary ?>;
        color: #ffffff;
        font-weight: bold;
        padding: 7px 10px;
        border: 1px solid <?= $primary ?>;
    }
    .commercial-table td {
        padding: 7px 10px;
        border: 1px solid #cbd5e1;
        vertical-align: top;
    }
    .scope-box {
        background: #f1f5f9;
        border-left: 3px solid <?= $primary ?>;
        padding: 8px 10px;
        font-size: 10.5px;
    }
    .footer-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        border-top: 1.5px solid <?= $secondary ?>;
        padding-top: 6px;
        font-size: 9px;
        color: #64748b;
    }
    .bank-box {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        padding: 8px 12px;
        margin-bottom: 12px;
        font-size: 10.5px;
    }
</style>
</head>
<body>

<!-- PAGE 1 -->
<div class="header-bar">
    <table style="width: 100%;">
        <tr>
            <td>
                <div style="font-size: 18px; font-weight: bold; color: <?= $primary ?>;"><?= e($businessSnapshot['company_name'] ?? '') ?></div>
                <div style="font-size: 10px; color: #64748b;"><?= e($businessSnapshot['tagline'] ?? '') ?></div>
            </td>
            <td style="text-align: right; font-size: 10px; color: #64748b;">
                <?= e($businessSnapshot['email'] ?? '') ?><br>
                <?= e($businessSnapshot['phone'] ?? '') ?>
            </td>
        </tr>
    </table>
</div>

<table class="meta-table">
    <tr>
        <td><strong>Ref. No.:</strong> <?= e($quotation['quotation_number']) ?></td>
        <td style="text-align: right;"><strong>Date:</strong> <?= format_date($quotation['date']) ?></td>
    </tr>
</table>

<div class="recipient-box">
    <div style="font-weight: bold; color: <?= $primary ?>;">To,</div>
    <div style="font-weight: bold; font-size: 12px;"><?= e($customerSnapshot['company_name'] ?? $customerSnapshot['name'] ?? '') ?></div>
    <?php if (!empty($customerSnapshot['contact_person'])): ?>
        <div>Attn: <?= e($customerSnapshot['contact_person']) ?></div>
    <?php endif; ?>
    <div><?= nl2br(e($customerSnapshot['billing_address'] ?? '')) ?></div>
    <?php if (!empty($customerSnapshot['gstin'])): ?>
        <div><strong>GSTIN:</strong> <?= e($customerSnapshot['gstin']) ?></div>
    <?php endif; ?>
</div>

<div class="subject-line">Subject: - <?= e($quotation['subject']) ?></div>

<div style="margin-bottom: 12px; line-height: 1.5; font-size: 11px;">
    <p><strong><?= e($introSection['salutation'] ?? 'Dear Sir,') ?></strong></p>
    <p style="font-weight: bold; color: <?= $primary ?>;"><?= e($introSection['greeting'] ?? '') ?></p>
    <p><?= nl2br(e($introSection['paragraph1'] ?? '')) ?></p>
    <p><?= nl2br(e($introSection['paragraph2'] ?? '')) ?></p>

    <?php if (!empty($introSection['relationship_heading'])): ?>
        <p style="font-weight: bold; color: <?= $primary ?>; margin-top: 8px;">
            <?= e($introSection['relationship_heading']) ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($introSection['capabilities'])): ?>
        <ul style="margin: 4px 0 8px 18px;">
            <?php foreach ($introSection['capabilities'] as $cap): ?>
                <li><?= e($cap) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (!empty($introSection['transition'])): ?>
        <p style="margin-top: 10px; font-style: italic; color: #475569;">
            <?= e($introSection['transition']) ?>
        </p>
    <?php endif; ?>
</div>

<div class="page-break"></div>

<!-- PAGE 2 -->
<div class="header-bar">
    <table style="width: 100%;">
        <tr>
            <td style="font-weight: bold; color: <?= $primary ?>; font-size: 14px;">Commercial Proposal</td>
            <td style="text-align: right; font-size: 10px; color: #64748b;">Ref: <?= e($quotation['quotation_number']) ?> &bull; Date: <?= format_date($quotation['date']) ?></td>
        </tr>
    </table>
</div>

<table class="commercial-table">
    <thead>
        <tr>
            <th style="text-align: left;">Particular</th>
            <th style="width: 32%; text-align: right;">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td>
                    <strong><?= e($it['description']) ?></strong>
                    <?php if (!empty($it['notes'])): ?>
                        <div style="font-size: 9.5px; color: #64748b;"><?= e($it['notes']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="text-align: right; white-space: nowrap; font-weight: bold;">
                    <?= format_currency($it['line_total']) ?>
                    <?php if (!empty($it['billing_period'])): ?>
                        <br><span style="font-size: 9.5px; color: #64748b; font-weight: normal;"><?= e($it['billing_period']) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (!empty($scopeSection['items'])): ?>
            <tr>
                <td>
                    <div class="scope-box">
                        <strong style="color: <?= $primary ?>;"><?= e($scopeSection['heading'] ?? 'Subscription Includes:') ?></strong>
                        <ul style="margin: 4px 0 0 16px;">
                            <?php foreach ($scopeSection['items'] as $sc): ?>
                                <li><?= e($sc) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </td>
                <td style="text-align: right; vertical-align: middle; font-weight: bold;">
                    Included
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <td><strong><?= ($quotation['tax_type'] === 'GST') ? "GST Extra as Applicable ({$quotation['gst_rate']}%)" : "IGST ({$quotation['gst_rate']}%)" ?></strong></td>
            <td style="text-align: right; font-weight: bold;">
                <?= format_currency((float)$quotation['cgst_amount'] + (float)$quotation['sgst_amount'] + (float)$quotation['igst_amount']) ?>
            </td>
        </tr>
        <tr style="background: #f1f5f9; font-size: 12px; font-weight: bold;">
            <td style="color: <?= $primary ?>;">Grand Total</td>
            <td style="text-align: right; color: <?= $primary ?>;"><?= format_currency($quotation['grand_total']) ?></td>
        </tr>
    </tbody>
</table>

<?php if (!empty($notesSection)): ?>
    <div style="margin-bottom: 10px; font-size: 10.5px;">
        <strong style="color: <?= $primary ?>;">Note: -</strong>
        <ul style="margin: 3px 0 6px 16px;">
            <?php foreach ($notesSection as $n): ?>
                <li><?= e($n) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($customizationSection['steps'])): ?>
    <div style="margin-bottom: 10px; font-size: 10px;">
        <strong style="color: <?= $primary ?>;"><?= e($customizationSection['heading'] ?? 'Additional Customization request:') ?></strong>
        <div><?= e($customizationSection['description'] ?? '') ?></div>
        <ul style="margin: 2px 0 0 16px;">
            <?php foreach ($customizationSection['steps'] as $st): ?>
                <li><?= e($st) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($systemRequirements['specs'])): ?>
    <div style="margin-bottom: 10px; font-size: 10px;">
        <strong style="color: <?= $primary ?>;"><?= e($systemRequirements['heading'] ?? 'System Requirements:') ?></strong>
        <table style="width: 100%; border-collapse: collapse; margin-top: 4px;">
            <?php foreach ($systemRequirements['specs'] as $sp): ?>
                <tr>
                    <td style="width: 25%; font-weight: bold; color: <?= $primary ?>; border: 1px solid #e2e8f0; padding: 3px 6px;"><?= e($sp['key']) ?>:</td>
                    <td style="border: 1px solid #e2e8f0; padding: 3px 6px;"><?= e($sp['value']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<div class="page-break"></div>

<!-- PAGE 3 -->
<div class="header-bar">
    <table style="width: 100%;">
        <tr>
            <td style="font-weight: bold; color: <?= $primary ?>; font-size: 14px;">Terms & Conditions & Banking</td>
            <td style="text-align: right; font-size: 10px; color: #64748b;">Ref: <?= e($quotation['quotation_number']) ?> &bull; Date: <?= format_date($quotation['date']) ?></td>
        </tr>
    </table>
</div>

<?php if (!empty($termsConditions)): ?>
    <ol style="margin-left: 16px; margin-bottom: 12px; font-size: 10px; line-height: 1.5;">
        <?php foreach ($termsConditions as $tm): ?>
            <li style="margin-bottom: 3px;"><?= e($tm) ?></li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<?php if (!empty($quotation['payment_terms_text'])): ?>
    <div style="margin-bottom: 10px; font-size: 10.5px;">
        <strong style="color: <?= $primary ?>;">Payment Terms:</strong> <?= e($quotation['payment_terms_text']) ?>
    </div>
<?php endif; ?>

<div class="bank-box">
    <div style="font-weight: bold; color: <?= $primary ?>; margin-bottom: 4px;">Payment in favor of: <?= e($businessSnapshot['account_name'] ?? $businessSnapshot['company_name'] ?? '') ?></div>
    <table style="width: 100%; font-size: 10px;">
        <tr>
            <td><strong>Bank:</strong> <?= e($businessSnapshot['bank_name'] ?? 'HDFC Bank Ltd.') ?></td>
            <td><strong>Acc No:</strong> <?= e($businessSnapshot['account_number'] ?? '') ?></td>
        </tr>
        <tr>
            <td><strong>IFSC:</strong> <?= e($businessSnapshot['ifsc_code'] ?? '') ?></td>
            <td><strong>Branch:</strong> <?= e($businessSnapshot['branch'] ?? '') ?></td>
        </tr>
    </table>
</div>

<div style="margin-top: 14px; font-size: 10.5px;">
    Thanking you & assuring you best of our attention & services at all the times. Waiting for your valued order.
</div>

<div style="margin-top: 16px; text-align: right;">
    <div style="display: inline-block; text-align: left; font-size: 10.5px;">
        <div>Sincerely Yours,</div>
        <div style="font-weight: bold; color: <?= $primary ?>;">For <?= e($businessSnapshot['company_name'] ?? '') ?></div>
        <div style="margin-top: 25px; font-weight: bold;"><?= e($businessSnapshot['signatory_name'] ?? '') ?></div>
        <div style="color: #64748b;"><?= e($businessSnapshot['signatory_designation'] ?? '') ?></div>
    </div>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// Check if Dompdf is loaded
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
    if (class_exists('Dompdf\Dompdf')) {
        $dompdf = new Dompdf\Dompdf([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Quotation_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $quotation['quotation_number']) . '.pdf';
        $dompdf->stream($filename, ['Attachment' => 1]);
        exit;
    }
}

// Fallback: output clean HTML ready for browser print/PDF
echo $html;
echo '<script>window.onload = function() { window.print(); }</script>';
exit;
