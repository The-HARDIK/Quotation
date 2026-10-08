<?php
/**
 * Quotation Studio - Two-Panel Quotation Builder (Edit Existing)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$biz = current_business();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$quotation = $stmt->fetch();

if (!$quotation) {
    flash_set('error', 'Quotation not found.');
    header("Location: " . BASE_URL . "/quotations/index.php");
    exit;
}

// Fetch Quotation Items
$itemsStmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ? ORDER BY item_order ASC, id ASC");
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

// Fetch Customers & Products & Terms Presets
$custStmt = $pdo->prepare("SELECT id, name, company_name, contact_person, email, phone, billing_address, city, state, gstin, pan FROM customers WHERE user_id = ? ORDER BY name ASC");
$custStmt->execute([$userId]);
$customers = $custStmt->fetchAll();

$prodStmt = $pdo->prepare("SELECT id, name, sku, hsn_sac, unit, unit_price, default_tax_rate, default_discount, description FROM products_services WHERE user_id = ? ORDER BY name ASC");
$prodStmt->execute([$userId]);
$products = $prodStmt->fetchAll();

$termsStmt = $pdo->prepare("SELECT id, title, category, clauses, is_default FROM terms_presets WHERE user_id = ? ORDER BY is_default DESC, title ASC");
$termsStmt->execute([$userId]);
$termsPresets = $termsStmt->fetchAll();

// Unpack JSON structures
$customerSnapshot = json_decode($quotation['customer_snapshot'] ?? '[]', true) ?: [];
$businessSnapshot = json_decode($quotation['business_snapshot'] ?? '[]', true) ?: $biz;
$introSection = json_decode($quotation['intro_section'] ?? '[]', true) ?: [];
$scopeSection = json_decode($quotation['scope_section'] ?? '[]', true) ?: [];
$customizationSection = json_decode($quotation['customization_section'] ?? '[]', true) ?: [];
$systemRequirements = json_decode($quotation['system_requirements'] ?? '[]', true) ?: [];
$termsConditions = json_decode($quotation['terms_conditions'] ?? '[]', true) ?: [];
$notesSection = json_decode($quotation['notes_section'] ?? '[]', true) ?: [];

$pageTitle = 'Edit ' . $quotation['quotation_number'];
$activeNav = 'quotations';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="margin-bottom: 14px;">
    <div>
        <h1 class="page-title">Edit Quotation</h1>
        <div class="page-subtitle"><strong style="color: var(--primary);"><?= e($quotation['quotation_number']) ?></strong> &bull; <?= status_badge($quotation['status']) ?></div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/quotations/view.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-eye"></i> View Proposal
        </a>
        <a href="<?= BASE_URL ?>/quotations/download.php?id=<?= $id ?>" class="btn btn-secondary btn-sm" title="Download PDF">
            <i class="fas fa-file-pdf" style="color: var(--danger);"></i> PDF
        </a>
        <button type="button" class="btn btn-primary btn-sm" id="saveQuoteBtn">
            <i class="fas fa-save"></i> Save Changes
        </button>
    </div>
</div>

<div class="builder-layout">
    <!-- LEFT PANEL: Dynamic Form Editor -->
    <div class="builder-editor-panel">
        <div class="builder-panel-header">
            <div style="font-weight: 700; font-size: 13.5px; color: var(--text-primary);">
                <i class="fas fa-sliders-h" style="color: var(--primary); margin-right: 6px;"></i> Edit Proposal Sections
            </div>
            <div id="autosaveBadge" class="autosave-status saved">
                <i class="fas fa-check-circle"></i> Saved
            </div>
        </div>

        <!-- Section Navigation Tabs -->
        <div class="builder-tabs">
            <button type="button" class="builder-tab-btn active" data-target="tab-general">
                <i class="fas fa-info-circle"></i> Info
            </button>
            <button type="button" class="builder-tab-btn" data-target="tab-customer">
                <i class="fas fa-user"></i> Recipient
            </button>
            <button type="button" class="builder-tab-btn" data-target="tab-intro">
                <i class="fas fa-envelope-open-text"></i> Cover Letter
            </button>
            <button type="button" class="builder-tab-btn" data-target="tab-items">
                <i class="fas fa-table"></i> Commercials
            </button>
            <button type="button" class="builder-tab-btn" data-target="tab-scope">
                <i class="fas fa-tasks"></i> Scope & Specs
            </button>
            <button type="button" class="builder-tab-btn" data-target="tab-terms">
                <i class="fas fa-gavel"></i> Terms & Bank
            </button>
            <button type="button" class="builder-tab-btn" data-target="tab-template">
                <i class="fas fa-layer-group"></i> Template
            </button>
        </div>

        <form id="builderForm" class="builder-editor-body" onsubmit="return false;">
            <!-- TAB 1: General & Metadata -->
            <div class="tab-pane active" id="tab-general">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Quotation Number <span class="required">*</span></label>
                        <input type="text" id="field_quote_number" class="form-control" value="<?= e($quotation['quotation_number']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reference Number</label>
                        <input type="text" id="field_ref_number" class="form-control" value="<?= e($quotation['ref_number'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proposal Date <span class="required">*</span></label>
                        <input type="date" id="field_date" class="form-control" value="<?= e($quotation['date']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Valid Until</label>
                        <input type="date" id="field_valid_until" class="form-control" value="<?= e($quotation['valid_until'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Subject / Purpose Line <span class="required">*</span></label>
                        <input type="text" id="field_subject" class="form-control" value="<?= e($quotation['subject'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Prepared By</label>
                        <input type="text" id="field_prepared_by" class="form-control" value="<?= e($quotation['prepared_by'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proposal Status</label>
                        <select id="field_status" class="form-control">
                            <option value="Draft" <?= ($quotation['status'] === 'Draft') ? 'selected' : '' ?>>Draft</option>
                            <option value="Sent" <?= ($quotation['status'] === 'Sent') ? 'selected' : '' ?>>Sent to Client</option>
                            <option value="Viewed" <?= ($quotation['status'] === 'Viewed') ? 'selected' : '' ?>>Viewed by Client</option>
                            <option value="Accepted" <?= ($quotation['status'] === 'Accepted') ? 'selected' : '' ?>>Accepted</option>
                            <option value="Rejected" <?= ($quotation['status'] === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                            <option value="Invoiced" <?= ($quotation['status'] === 'Invoiced') ? 'selected' : '' ?>>Converted to Invoice</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Customer / Recipient -->
            <div class="tab-pane" id="tab-customer">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Quick Select Customer</label>
                    <select id="customerSelect" class="form-control">
                        <option value="">-- Choose existing customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($quotation['customer_id'] == $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['company_name'] ?: $c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Recipient Company Name <span class="required">*</span></label>
                        <input type="text" id="cust_company" class="form-control" value="<?= e($customerSnapshot['company_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Person Name</label>
                        <input type="text" id="cust_name" class="form-control" value="<?= e($customerSnapshot['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Designation / Title</label>
                        <input type="text" id="cust_contact" class="form-control" value="<?= e($customerSnapshot['contact_person'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Billing Street Address</label>
                        <textarea id="cust_address" class="form-control" rows="2"><?= e($customerSnapshot['billing_address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Client GSTIN</label>
                        <input type="text" id="cust_gstin" class="form-control" value="<?= e($customerSnapshot['gstin'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Client Phone</label>
                        <input type="text" id="cust_phone" class="form-control" value="<?= e($customerSnapshot['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Client Email</label>
                        <input type="email" id="cust_email" class="form-control" value="<?= e($customerSnapshot['email'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- TAB 3: Cover Letter -->
            <div class="tab-pane" id="tab-intro">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Salutation</label>
                        <input type="text" id="intro_salutation" class="form-control" value="<?= e($introSection['salutation'] ?? 'Dear Sir,') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Greeting</label>
                        <input type="text" id="intro_greeting" class="form-control" value="<?= e($introSection['greeting'] ?? 'Greetings from Priyam!!') ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Company Track Record Paragraph</label>
                        <textarea id="intro_p1" class="form-control" rows="3"><?= e($introSection['paragraph1'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Enterprise Capabilities Paragraph</label>
                        <textarea id="intro_p2" class="form-control" rows="3"><?= e($introSection['paragraph2'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Capabilities Heading</label>
                        <input type="text" id="intro_rel_heading" class="form-control" value="<?= e($introSection['relationship_heading'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Competencies List (One per line)</label>
                        <textarea id="intro_capabilities" class="form-control" rows="6"><?= e(implode("\n", $introSection['capabilities'] ?? [])) ?></textarea>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Transition Statement</label>
                        <input type="text" id="intro_transition" class="form-control" value="<?= e($introSection['transition'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- TAB 4: Commercial Items -->
            <div class="tab-pane" id="tab-items">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-weight: 700; font-size: 13px;">Commercial Line Items</span>
                    <button type="button" class="btn btn-secondary btn-sm" id="addItemBtn">
                        <i class="fas fa-plus"></i> Add Line Item
                    </button>
                </div>

                <div id="itemsContainer"></div>

                <!-- Pricing Adjustments -->
                <div class="card" style="margin-top: 16px; background: #f8fafc;">
                    <div class="card-header" style="background: transparent;">
                        <div class="card-title" style="font-size: 14px;"><i class="fas fa-calculator" style="margin-right: 6px;"></i> Taxes, Discounts & Totals</div>
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">GST Tax Mode</label>
                                <select id="field_tax_type" class="form-control">
                                    <option value="GST" <?= ($quotation['tax_type'] === 'GST') ? 'selected' : '' ?>>GST (CGST + SGST Split)</option>
                                    <option value="IGST" <?= ($quotation['tax_type'] === 'IGST') ? 'selected' : '' ?>>IGST (Inter-State Single Tax)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">GST Tax Rate (%)</label>
                                <input type="number" step="0.5" id="field_gst_rate" class="form-control" value="<?= (float)$quotation['gst_rate'] ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Overall Discount (₹)</label>
                                <input type="number" step="0.01" id="field_overall_discount" class="form-control" value="<?= (float)$quotation['total_discount'] ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Additional Charges (₹)</label>
                                <input type="number" step="0.01" id="field_additional_charges" class="form-control" value="<?= (float)$quotation['additional_charges'] ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Advance Payment (₹)</label>
                                <input type="number" step="0.01" id="field_advance_amount" class="form-control" value="<?= (float)$quotation['advance_amount'] ?>">
                            </div>

                            <div class="form-group" style="display: flex; align-items: center; gap: 8px; margin-top: 24px;">
                                <input type="checkbox" id="field_tax_inclusive" value="1" <?= $quotation['tax_inclusive'] ? 'checked' : '' ?>>
                                <label for="field_tax_inclusive" style="font-size: 12.5px; cursor: pointer; margin: 0;">Prices are Tax-Inclusive</label>
                            </div>
                        </div>

                        <!-- Summary -->
                        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; margin-top: 14px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Gross Subtotal:</span>
                                <strong id="sum_subtotal"><?= format_currency($quotation['subtotal']) ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: var(--danger);">
                                <span>Total Discounts:</span>
                                <span id="sum_discounts"><?= format_currency($quotation['total_discount']) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Taxable Amount:</span>
                                <strong id="sum_taxable"><?= format_currency($quotation['taxable_amount']) ?></strong>
                            </div>
                            <div id="sum_tax_breakdown" style="padding: 6px 0; border-top: 1px dashed var(--border-color); border-bottom: 1px dashed var(--border-color); margin-bottom: 6px;"></div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Round Off:</span>
                                <span id="sum_round_off"><?= format_currency($quotation['round_off']) ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; color: var(--primary);">
                                <span>Grand Total:</span>
                                <span id="sum_grand_total"><?= format_currency($quotation['grand_total']) ?></span>
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; font-style: italic;" id="sum_words"></div>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="form-group" style="margin-top: 14px;">
                    <label class="form-label">Operational Notes (One per line)</label>
                    <textarea id="notes_textarea" class="form-control" rows="3"><?= e(implode("\n", $notesSection)) ?></textarea>
                </div>
            </div>

            <!-- TAB 5: Scope & Specs -->
            <div class="tab-pane" id="tab-scope">
                <div class="form-group">
                    <label class="form-label">Scope Heading</label>
                    <input type="text" id="scope_heading" class="form-control" value="<?= e($scopeSection['heading'] ?? 'Subscription Includes:') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Scope Inclusions (One per line)</label>
                    <textarea id="scope_items" class="form-control" rows="6"><?= e(implode("\n", $scopeSection['items'] ?? [])) ?></textarea>
                </div>

                <div style="border-top: 1px solid var(--border-color); margin: 16px 0;"></div>

                <div class="form-group">
                    <label class="form-label">Change Request Heading</label>
                    <input type="text" id="custom_heading" class="form-control" value="<?= e($customizationSection['heading'] ?? 'Additional Customization request:') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Change Request Description</label>
                    <input type="text" id="custom_desc" class="form-control" value="<?= e($customizationSection['description'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Workflow Steps (One per line)</label>
                    <textarea id="custom_steps" class="form-control" rows="6"><?= e(implode("\n", $customizationSection['steps'] ?? [])) ?></textarea>
                </div>

                <div style="border-top: 1px solid var(--border-color); margin: 16px 0;"></div>

                <div class="form-group">
                    <label class="form-label">System Requirements Heading</label>
                    <input type="text" id="sysreq_heading" class="form-control" value="<?= e($systemRequirements['heading'] ?? 'System Requirements for UiPrime:') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Hardware & Environment Specs (Key: Value)</label>
                    <?php
                        $specLines = [];
                        foreach ($systemRequirements['specs'] ?? [] as $sp) {
                            $specLines[] = ($sp['key'] ?? '') . ': ' . ($sp['value'] ?? '');
                        }
                    ?>
                    <textarea id="sysreq_text" class="form-control" rows="4"><?= e(implode("\n", $specLines)) ?></textarea>
                </div>
            </div>

            <!-- TAB 6: Terms & Banking -->
            <div class="tab-pane" id="tab-terms">
                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label">Load from Terms Presets</label>
                    <select id="termsPresetSelect" class="form-control">
                        <option value="">-- Choose preset clauses --</option>
                        <?php foreach ($termsPresets as $tp): ?>
                            <option value="<?= $tp['id'] ?>"><?= e($tp['title']) ?> (<?= e($tp['category']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Terms & Conditions Clauses (One per line) <span class="required">*</span></label>
                    <textarea id="termsTextarea" class="form-control" rows="12"><?= e(implode("\n", $termsConditions)) ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Terms Policy</label>
                    <input type="text" id="field_payment_terms" class="form-control" value="<?= e($quotation['payment_terms_text'] ?? '') ?>">
                </div>
            </div>

            <!-- TAB 7: Template Switcher -->
            <div class="tab-pane" id="tab-template">
                <div style="font-weight: 700; margin-bottom: 12px;">Select Template Style</div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php
                    $templateOptions = [
                        'classic' => ['Commercial Proposal – Classic (Official 3-Page Format)', 'Exact reproduction of the uploaded 3-page proposal layout with formal cover letter, scope table, change request steps, hardware specs, and legal clauses.'],
                        'modern' => ['Modern Clean', 'Contemporary SaaS theme with rounded badge borders and minimal dividers.'],
                        'corporate' => ['Executive Corporate', 'High-formality enterprise template featuring dark corporate border grids.'],
                        'minimal' => ['Minimalist Slate', 'Ultra-clean typography focused on high readability and compact space.'],
                        'elegant' => ['Boutique Elegant', 'Sophisticated serif titles and luxury warm gold/olive jewel accents.']
                    ];
                    foreach ($templateOptions as $tid => $tinfo):
                        $checked = ($quotation['template_id'] === $tid) ? 'checked' : '';
                    ?>
                        <label style="display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer;">
                            <input type="radio" name="template_id_radio" value="<?= $tid ?>" <?= $checked ?> style="margin-top: 4px;">
                            <div>
                                <strong><?= e($tinfo[0]) ?></strong>
                                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><?= e($tinfo[1]) ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>

        <div class="builder-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button type="button" class="btn btn-primary" onclick="builder.saveQuotation(false)">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </div>

    <!-- RIGHT PANEL: Live A4 Preview Viewport -->
    <div class="builder-preview-panel">
        <div class="preview-toolbar">
            <div class="preview-toolbar-group">
                <span style="font-size: 12px; font-weight: 600; color: #94a3b8;"><i class="fas fa-eye"></i> Live A4 Canvas</span>
                <span id="zoomLabel" style="font-size: 11px; background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;">100%</span>
            </div>

            <div class="preview-toolbar-group">
                <button type="button" class="toolbar-btn" id="zoomOutBtn" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
                <button type="button" class="toolbar-btn" id="zoomResetBtn" title="Reset Zoom">100%</button>
                <button type="button" class="toolbar-btn" id="zoomInBtn" title="Zoom In"><i class="fas fa-search-plus"></i></button>
                <button type="button" class="toolbar-btn" id="zoomFitBtn" title="Fit to Width"><i class="fas fa-expand-arrows-alt"></i></button>

                <select id="pageNavSelect" style="background: rgba(255,255,255,0.1); color: #ffffff; border: none; font-size: 11.5px; padding: 4px 8px; border-radius: 4px; outline: none; margin-left: 8px;">
                    <option value="page-1">Page 1 (Intro)</option>
                    <option value="page-2">Page 2 (Commercial)</option>
                    <option value="page-3">Page 3 (Terms & Bank)</option>
                </select>
            </div>
        </div>

        <div class="preview-viewport">
            <div class="preview-scale-wrapper" id="a4ScaleWrapper">
                <div class="a4-paper-container" id="a4PaperContainer">
                    <!-- Dynamically populated 3 pages via quotation-builder.js -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/public/js/quotation-builder.js?v=<?= APP_VERSION ?>"></script>
<script>
window.BASE_URL = '<?= BASE_URL ?>';
const initialData = {
    id: <?= (int)$quotation['id'] ?>,
    quotation_number: <?= json_encode($quotation['quotation_number']) ?>,
    ref_number: <?= json_encode($quotation['ref_number'] ?? '') ?>,
    date: <?= json_encode($quotation['date']) ?>,
    valid_until: <?= json_encode($quotation['valid_until'] ?? '') ?>,
    subject: <?= json_encode($quotation['subject'] ?? '') ?>,
    currency: <?= json_encode($quotation['currency'] ?? 'INR') ?>,
    status: <?= json_encode($quotation['status']) ?>,
    template_id: <?= json_encode($quotation['template_id'] ?? 'classic') ?>,
    prepared_by: <?= json_encode($quotation['prepared_by'] ?? '') ?>,
    sales_person: <?= json_encode($quotation['sales_person'] ?? '') ?>,

    customer_id: <?= json_encode($quotation['customer_id']) ?>,
    customer_snapshot: <?= json_encode($customerSnapshot) ?>,
    business_snapshot: <?= json_encode($businessSnapshot) ?>,

    items: <?= json_encode($items) ?>,
    tax_type: <?= json_encode($quotation['tax_type'] ?? 'GST') ?>,
    tax_inclusive: <?= json_encode((bool)$quotation['tax_inclusive']) ?>,
    gst_rate: <?= (float)$quotation['gst_rate'] ?>,
    overall_discount: <?= (float)$quotation['total_discount'] ?>,
    additional_charges: <?= (float)$quotation['additional_charges'] ?>,
    advance_amount: <?= (float)$quotation['advance_amount'] ?>,

    intro_section: <?= json_encode($introSection) ?>,
    scope_section: <?= json_encode($scopeSection) ?>,
    customization_section: <?= json_encode($customizationSection) ?>,
    system_requirements: <?= json_encode($systemRequirements) ?>,
    terms_conditions: <?= json_encode($termsConditions) ?>,
    payment_terms_text: <?= json_encode($quotation['payment_terms_text'] ?? '') ?>,
    notes_section: <?= json_encode($notesSection) ?>
};

const builderConfig = {
    business: <?= json_encode($biz) ?>,
    customers: <?= json_encode($customers) ?>,
    products: <?= json_encode($products) ?>,
    terms_presets: <?= json_encode($termsPresets) ?>
};

const builder = new QuotationBuilder(initialData, builderConfig);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
