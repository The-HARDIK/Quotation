<?php
/**
 * Quotation Studio - Customer-Facing Public Proposal View
 * Allows client to review proposal, Accept, or Reject with comments.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$shareId = trim($_GET['id'] ?? '');
if (empty($shareId)) {
    die("Invalid proposal link.");
}

$pdo = get_db();
$stmt = $pdo->prepare("SELECT * FROM quotations WHERE public_share_id = ? LIMIT 1");
$stmt->execute([$shareId]);
$quotation = $stmt->fetch();

if (!$quotation) {
    die("Proposal not found or link has expired.");
}

// Mark status as 'Viewed' if it was 'Sent'
if ($quotation['status'] === 'Sent') {
    $upStmt = $pdo->prepare("UPDATE quotations SET status = 'Viewed' WHERE id = ?");
    $upStmt->execute([$quotation['id']]);
    $quotation['status'] = 'Viewed';
}

$feedbackMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['client_action'] ?? '';
    $comment = trim($_POST['client_comment'] ?? '');

    if ($action === 'accept') {
        $response = [
            'status' => 'Accepted',
            'responded_at' => date('Y-m-d H:i:s'),
            'comment' => $comment
        ];
        $upStmt = $pdo->prepare("UPDATE quotations SET status = 'Accepted', customer_response = ? WHERE id = ?");
        $upStmt->execute([json_encode($response), $quotation['id']]);
        $quotation['status'] = 'Accepted';
        $quotation['customer_response'] = json_encode($response);
        $feedbackMsg = 'Thank you! You have accepted this commercial proposal. The vendor has been notified to proceed with invoicing & order processing.';
    } elseif ($action === 'reject') {
        $response = [
            'status' => 'Rejected',
            'responded_at' => date('Y-m-d H:i:s'),
            'comment' => $comment
        ];
        $upStmt = $pdo->prepare("UPDATE quotations SET status = 'Rejected', customer_response = ? WHERE id = ?");
        $upStmt->execute([json_encode($response), $quotation['id']]);
        $quotation['status'] = 'Rejected';
        $quotation['customer_response'] = json_encode($response);
        $feedbackMsg = 'Thank you for your response. Your feedback has been forwarded to the account manager.';
    }
}

// Fetch items
$itemsStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY item_order ASC");
$itemsStmt->execute([$quotation['id']]);
$items = $itemsStmt->fetchAll();

$customerSnapshot = json_decode($quotation['customer_snapshot'] ?? '[]', true) ?: [];
$businessSnapshot = json_decode($quotation['business_snapshot'] ?? '[]', true) ?: [];
$introSection = json_decode($quotation['intro_section'] ?? '[]', true) ?: [];
$scopeSection = json_decode($quotation['scope_section'] ?? '[]', true) ?: [];
$customizationSection = json_decode($quotation['customization_section'] ?? '[]', true) ?: [];
$systemRequirements = json_decode($quotation['system_requirements'] ?? '[]', true) ?: [];
$termsConditions = json_decode($quotation['terms_conditions'] ?? '[]', true) ?: [];
$notesSection = json_decode($quotation['notes_section'] ?? '[]', true) ?: [];
$customerResponse = json_decode($quotation['customer_response'] ?? '[]', true) ?: null;

$primary = $businessSnapshot['primary_color'] ?? '#0d5c75';
$secondary = $businessSnapshot['secondary_color'] ?? '#85a438';
$font = $businessSnapshot['font_preference'] ?? 'Inter';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal <?= e($quotation['quotation_number']) ?> — <?= e($businessSnapshot['company_name'] ?? 'Quotation Studio') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/style.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/quotation-builder.css?v=<?= APP_VERSION ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/quotation-print.css?v=<?= APP_VERSION ?>" media="print">
    <style>
        body { background: #475569; padding-bottom: 60px; }
        .public-top-bar {
            position: sticky; top: 0; z-index: 100;
            background: #1e293b; color: #ffffff;
            padding: 14px 28px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);
            flex-wrap: wrap; gap: 12px;
        }
        .public-canvas {
            display: flex; flex-direction: column; align-items: center;
            padding: 30px 16px;
        }
    </style>
</head>
<body>

<div class="public-top-bar no-print">
    <div>
        <div style="font-family: Outfit; font-size: 16px; font-weight: 700;">
            <?= e($quotation['quotation_number']) ?> — <?= e($quotation['subject']) ?>
        </div>
        <div style="font-size: 11.5px; color: #94a3b8;">
            Issued by <?= e($businessSnapshot['company_name'] ?? '') ?> to <?= e($customerSnapshot['company_name'] ?? $customerSnapshot['name'] ?? '') ?>
        </div>
    </div>

    <div style="display: flex; align-items: center; gap: 10px;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
            <i class="fas fa-print"></i> Print Proposal
        </button>

        <?php if ($quotation['status'] === 'Accepted'): ?>
            <span class="badge badge-accepted" style="font-size: 13px; padding: 6px 14px;">
                <i class="fas fa-check-circle"></i> Proposal Accepted
            </span>
        <?php elseif ($quotation['status'] === 'Rejected'): ?>
            <span class="badge badge-rejected" style="font-size: 13px; padding: 6px 14px;">
                <i class="fas fa-times-circle"></i> Proposal Rejected
            </span>
        <?php else: ?>
            <button type="button" class="btn btn-danger btn-sm" onclick="openDecisionModal('reject')">
                <i class="fas fa-times"></i> Reject Proposal
            </button>
            <button type="button" class="btn btn-success btn-sm" onclick="openDecisionModal('accept')">
                <i class="fas fa-check"></i> Accept Proposal
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($feedbackMsg): ?>
    <div style="max-width: 800px; margin: 20px auto 0; padding: 0 16px;">
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= e($feedbackMsg) ?></div>
    </div>
<?php endif; ?>

<div class="public-canvas">
    <div class="a4-paper-container" style="max-width: 210mm; width: 100%;">
        <!-- PAGE 1 -->
        <div class="a4-page page-1" style="font-family: <?= e($font) ?>, sans-serif;">
            <div class="proposal-header" style="border-bottom-color: <?= e($primary) ?>;">
                <div class="proposal-brand-left">
                    <?php if (!empty($businessSnapshot['logo_url'])): ?>
                        <img src="<?= BASE_URL ?>/<?= e($businessSnapshot['logo_url']) ?>" class="proposal-logo-main" alt="Logo">
                    <?php else: ?>
                        <div style="font-family: Outfit; font-size: 20px; font-weight: 700; color: <?= e($primary) ?>;"><?= e($businessSnapshot['company_name'] ?? '') ?></div>
                    <?php endif; ?>
                </div>
                <div class="proposal-brand-badges">
                    <?php if (!empty($businessSnapshot['secondary_logo_url'])): ?>
                        <img src="<?= BASE_URL ?>/<?= e($businessSnapshot['secondary_logo_url']) ?>" class="proposal-partner-logo" alt="Partner">
                    <?php endif; ?>
                </div>
            </div>

            <div class="proposal-meta-grid">
                <div><strong style="color: <?= e($primary) ?>;">Ref. No.:</strong> <?= e($quotation['quotation_number']) ?></div>
                <div><strong style="color: <?= e($primary) ?>;">Date:</strong> <?= format_date($quotation['date']) ?></div>
            </div>

            <div class="proposal-recipient-box" style="border-left-color: <?= e($primary) ?>;">
                <div style="font-weight: 700; color: <?= e($primary) ?>;">To,</div>
                <div style="font-weight: 700; font-size: 13px;"><?= e($customerSnapshot['company_name'] ?? $customerSnapshot['name'] ?? '') ?></div>
                <?php if (!empty($customerSnapshot['contact_person'])): ?>
                    <div>Attn: <?= e($customerSnapshot['contact_person']) ?></div>
                <?php endif; ?>
                <div><?= nl2br(e($customerSnapshot['billing_address'] ?? '')) ?></div>
                <?php if (!empty($customerSnapshot['gstin'])): ?>
                    <div><strong>GSTIN:</strong> <?= e($customerSnapshot['gstin']) ?></div>
                <?php endif; ?>
            </div>

            <div class="proposal-subject-line">Subject: - <?= e($quotation['subject']) ?></div>

            <div class="proposal-intro-letter">
                <p><strong><?= e($introSection['salutation'] ?? 'Dear Sir,') ?></strong></p>
                <p style="font-weight: 600; color: <?= e($primary) ?>;"><?= e($introSection['greeting'] ?? '') ?></p>
                <p><?= nl2br(e($introSection['paragraph1'] ?? '')) ?></p>
                <p><?= nl2br(e($introSection['paragraph2'] ?? '')) ?></p>

                <?php if (!empty($introSection['relationship_heading'])): ?>
                    <div style="margin-top: 10px; font-weight: 600; color: <?= e($primary) ?>;">
                        <?= e($introSection['relationship_heading']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($introSection['capabilities'])): ?>
                    <ul class="capabilities-list" style="color: #334155;">
                        <?php foreach ($introSection['capabilities'] as $cap): ?>
                            <li><?= e($cap) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (!empty($introSection['transition'])): ?>
                    <p style="margin-top: 12px; font-style: italic; color: #475569;">
                        <?= e($introSection['transition']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="proposal-footer-banner" style="border-top-color: <?= e($secondary) ?>;">
                <div><strong><?= e($businessSnapshot['company_name'] ?? '') ?></strong> &bull; <?= e($businessSnapshot['phone'] ?? '') ?> &bull; <?= e($businessSnapshot['email'] ?? '') ?></div>
                <div>Page 1</div>
            </div>
        </div>

        <!-- PAGE 2 -->
        <div class="a4-page page-2" style="font-family: <?= e($font) ?>, sans-serif;">
            <div class="proposal-header" style="border-bottom-color: <?= e($primary) ?>;">
                <div class="proposal-brand-left">
                    <?php if (!empty($businessSnapshot['logo_url'])): ?>
                        <img src="<?= BASE_URL ?>/<?= e($businessSnapshot['logo_url']) ?>" class="proposal-logo-main" alt="Logo">
                    <?php endif; ?>
                </div>
                <div style="font-size: 11px; text-align: right; color: #64748b;">
                    Ref: <?= e($quotation['quotation_number']) ?><br>
                    Date: <?= format_date($quotation['date']) ?>
                </div>
            </div>

            <div style="font-family: Outfit; font-size: 14px; font-weight: 700; color: <?= e($primary) ?>; margin-bottom: 8px;">
                Commercial for <?= e($quotation['subject']) ?>
            </div>

            <table class="commercial-table">
                <thead>
                    <tr>
                        <th style="background: <?= e($primary) ?>; border-color: <?= e($primary) ?>;">Particular</th>
                        <th style="background: <?= e($primary) ?>; border-color: <?= e($primary) ?>; width: 35%; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td style="padding: 8px 10px; border: 1px solid #cbd5e1;">
                                <strong style="color: #0f172a;"><?= e($it['description']) ?></strong>
                                <?php if (!empty($it['notes'])): ?>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;"><?= e($it['notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 8px 10px; border: 1px solid #cbd5e1; text-align: right; white-space: nowrap; font-weight: 600;">
                                <?= format_currency($it['line_total']) ?>
                                <?php if (!empty($it['billing_period'])): ?>
                                    <span style="color:#64748b; font-weight:normal;">(<?= e($it['billing_period']) ?>)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!empty($scopeSection['items'])): ?>
                        <tr>
                            <td style="padding: 8px 10px; border: 1px solid #cbd5e1;">
                                <div class="scope-box" style="border-left: 3px solid <?= e($primary) ?>;">
                                    <h4 style="color: <?= e($primary) ?>;"><?= e($scopeSection['heading'] ?? 'Subscription Includes:') ?></h4>
                                    <ul>
                                        <?php foreach ($scopeSection['items'] as $sc): ?>
                                            <li><?= e($sc) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </td>
                            <td style="padding: 8px 10px; border: 1px solid #cbd5e1; text-align: right; vertical-align: middle; font-weight: 600;">
                                Included
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #cbd5e1; font-weight: 600;">
                            <?= ($quotation['tax_type'] === 'GST') ? "GST Extra as Applicable ({$quotation['gst_rate']}%)" : "IGST ({$quotation['gst_rate']}%)" ?>
                        </td>
                        <td style="padding: 8px 10px; border: 1px solid #cbd5e1; text-align: right; font-weight: 600;">
                            <?= format_currency((float)$quotation['cgst_amount'] + (float)$quotation['sgst_amount'] + (float)$quotation['igst_amount']) ?>
                        </td>
                    </tr>
                    <tr style="background: #f1f5f9; font-weight: 700; font-size: 12.5px;">
                        <td style="padding: 8px 10px; border: 1px solid #cbd5e1; color: <?= e($primary) ?>;">Grand Total</td>
                        <td style="padding: 8px 10px; border: 1px solid #cbd5e1; text-align: right; color: <?= e($primary) ?>;">
                            <?= format_currency($quotation['grand_total']) ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php if (!empty($notesSection)): ?>
                <div style="margin-top: 10px; font-size: 11.5px; color: #334155; line-height: 1.5;">
                    <strong style="color: <?= e($primary) ?>;">Note: -</strong>
                    <ul style="margin: 4px 0 10px 18px; list-style-type: disc;">
                        <?php foreach ($notesSection as $n): ?>
                            <li><?= e($n) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($customizationSection['steps'])): ?>
                <div style="margin-bottom: 14px; font-size: 11.5px;">
                    <strong style="color: <?= e($primary) ?>; font-size: 12px;"><?= e($customizationSection['heading'] ?? 'Additional Customization request:') ?></strong>
                    <div style="margin-top: 3px; color: #475569;"><?= e($customizationSection['description'] ?? '') ?></div>
                    <ul style="margin: 4px 0 0 18px; list-style-type: square; color: #334155;">
                        <?php foreach ($customizationSection['steps'] as $st): ?>
                            <li><?= e($st) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($systemRequirements['specs'])): ?>
                <div style="margin-bottom: 14px;">
                    <strong style="color: <?= e($primary) ?>; font-size: 12px;"><?= e($systemRequirements['heading'] ?? 'System Requirements:') ?></strong>
                    <table class="sys-req-table" style="margin-top: 6px;">
                        <tbody>
                            <?php foreach ($systemRequirements['specs'] as $sp): ?>
                                <tr>
                                    <td class="key" style="color: <?= e($primary) ?>;"><?= e($sp['key']) ?>:</td>
                                    <td><?= e($sp['value']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="proposal-footer-banner" style="border-top-color: <?= e($secondary) ?>;">
                <div><strong><?= e($businessSnapshot['company_name'] ?? '') ?></strong> &bull; <?= e($businessSnapshot['phone'] ?? '') ?> &bull; <?= e($businessSnapshot['email'] ?? '') ?></div>
                <div>Page 2</div>
            </div>
        </div>

        <!-- PAGE 3 -->
        <div class="a4-page page-3" style="font-family: <?= e($font) ?>, sans-serif;">
            <div class="proposal-header" style="border-bottom-color: <?= e($primary) ?>;">
                <div class="proposal-brand-left">
                    <?php if (!empty($businessSnapshot['logo_url'])): ?>
                        <img src="<?= BASE_URL ?>/<?= e($businessSnapshot['logo_url']) ?>" class="proposal-logo-main" alt="Logo">
                    <?php endif; ?>
                </div>
                <div style="font-size: 11px; text-align: right; color: #64748b;">
                    Ref: <?= e($quotation['quotation_number']) ?><br>
                    Date: <?= format_date($quotation['date']) ?>
                </div>
            </div>

            <div style="font-family: Outfit; font-size: 13.5px; font-weight: 700; color: <?= e($primary) ?>; margin-bottom: 8px;">
                Terms and Condition: -
            </div>

            <?php if (!empty($termsConditions)): ?>
                <ol class="terms-ordered-list">
                    <?php foreach ($termsConditions as $tm): ?>
                        <li><?= e($tm) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <?php if (!empty($quotation['payment_terms_text'])): ?>
                <div style="margin-bottom: 12px; font-size: 11.5px;">
                    <strong style="color: <?= e($primary) ?>;">Payment Terms:</strong>
                    <div style="color: #334155; margin-top: 2px;"><?= e($quotation['payment_terms_text']) ?></div>
                </div>
            <?php endif; ?>

            <div class="bank-info-box" style="border-color: <?= e($primary) ?>;">
                <div style="font-weight: 700; color: <?= e($primary) ?>; margin-bottom: 4px;">Payment should be made in favor of:</div>
                <div style="font-size: 12px; font-weight: 600;"><?= e($businessSnapshot['account_name'] ?? $businessSnapshot['company_name'] ?? '') ?></div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; margin-top: 6px; font-size: 11px;">
                    <div><strong>Bank Name:</strong> <?= e($businessSnapshot['bank_name'] ?? 'HDFC Bank Ltd.') ?></div>
                    <div><strong>Acc. No.:</strong> <code><?= e($businessSnapshot['account_number'] ?? '') ?></code></div>
                    <div><strong>IFSC Code:</strong> <code><?= e($businessSnapshot['ifsc_code'] ?? '') ?></code></div>
                    <div><strong>Branch:</strong> <?= e($businessSnapshot['branch'] ?? '') ?></div>
                    <?php if (!empty($businessSnapshot['upi_id'])): ?>
                        <div><strong>UPI VPA:</strong> <?= e($businessSnapshot['upi_id']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top: 14px; font-size: 11.5px; color: #475569;">
                Thanking you & assuring you best of our attention & services at all the times. Waiting for your valued order.
            </div>

            <div class="signature-block">
                <div class="signature-card">
                    <div style="font-size: 11px; color: #64748b;">Sincerely Yours,</div>
                    <div style="font-weight: 700; color: <?= e($primary) ?>; margin-bottom: 6px;">For <?= e($businessSnapshot['company_name'] ?? '') ?></div>
                    <?php if (!empty($businessSnapshot['signature_url'])): ?>
                        <img src="<?= BASE_URL ?>/<?= e($businessSnapshot['signature_url']) ?>" class="signature-image" alt="Signature">
                    <?php endif; ?>
                    <div style="font-weight: 700; font-size: 12px;"><?= e($businessSnapshot['signatory_name'] ?? '') ?></div>
                    <div style="font-size: 11px; color: #64748b;"><?= e($businessSnapshot['signatory_designation'] ?? '') ?></div>
                    <div style="font-size: 11px; color: #64748b;"><?= e($businessSnapshot['phone'] ?? '') ?></div>
                    <div style="font-size: 11px; color: #64748b;"><?= e($businessSnapshot['email'] ?? '') ?></div>
                </div>
            </div>

            <div class="proposal-footer-banner" style="border-top-color: <?= e($secondary) ?>;">
                <div>
                    <div style="font-weight: 700; color: <?= e($primary) ?>;"><?= e($businessSnapshot['company_name'] ?? '') ?></div>
                    <div><?= e($businessSnapshot['address'] ?? '') ?>, <?= e($businessSnapshot['city'] ?? '') ?> - <?= e($businessSnapshot['pincode'] ?? '') ?></div>
                    <div>Phone: <?= e($businessSnapshot['phone'] ?? '') ?> &bull; Email: <?= e($businessSnapshot['email'] ?? '') ?></div>
                </div>
                <div>Page 3</div>
            </div>
        </div>
    </div>
</div>

<!-- Accept / Reject Modal -->
<div id="decisionModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; width: 100%; max-width: 500px; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div class="card-header">
            <div class="card-title" id="decisionTitle">Confirm Proposal Decision</div>
            <button type="button" class="alert-close" onclick="closeDecisionModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="client_action" id="decisionAction" value="accept">
            <div class="card-body">
                <p id="decisionPrompt" style="font-size: 13.5px; margin-bottom: 14px; color: var(--text-secondary);">
                    Are you ready to accept this commercial quotation?
                </p>
                <div class="form-group">
                    <label class="form-label">Feedback / Notes (Optional)</label>
                    <textarea name="client_comment" class="form-control" rows="3" placeholder="Add purchase order details, mandate references, or specific notes..."></textarea>
                </div>
            </div>
            <div class="card-footer" style="text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeDecisionModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="decisionConfirmBtn">Confirm Decision</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDecisionModal(action) {
    document.getElementById('decisionAction').value = action;
    const title = document.getElementById('decisionTitle');
    const prompt = document.getElementById('decisionPrompt');
    const btn = document.getElementById('decisionConfirmBtn');

    if (action === 'accept') {
        title.textContent = 'Accept Commercial Proposal';
        prompt.textContent = 'By accepting, you confirm that you agree to the commercials, scope of deliverables, and terms outlined in this proposal.';
        btn.className = 'btn btn-success';
        btn.innerHTML = '<i class="fas fa-check"></i> Accept Proposal';
    } else {
        title.textContent = 'Decline / Reject Proposal';
        prompt.textContent = 'Please provide an optional reason or comment to help the account manager address any adjustments.';
        btn.className = 'btn btn-danger';
        btn.innerHTML = '<i class="fas fa-times"></i> Reject Proposal';
    }
    document.getElementById('decisionModal').style.display = 'flex';
}

function closeDecisionModal() {
    document.getElementById('decisionModal').style.display = 'none';
}
</script>

</body>
</html>
