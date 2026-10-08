<?php
/**
 * Quotation Studio - Two-Panel Quotation Builder (Create New)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$biz = current_business();

// Fetch Customers for quick dropdown
$custStmt = $pdo->prepare("SELECT id, name, company_name, contact_person, email, phone, billing_address, city, state, gstin, pan FROM customers WHERE user_id = ? ORDER BY name ASC");
$custStmt->execute([$userId]);
$customers = $custStmt->fetchAll();

// Fetch Products for quick dropdown
$prodStmt = $pdo->prepare("SELECT id, name, sku, hsn_sac, unit, unit_price, default_tax_rate, default_discount, description FROM products_services WHERE user_id = ? ORDER BY name ASC");
$prodStmt->execute([$userId]);
$products = $prodStmt->fetchAll();

// Fetch Terms Presets
$termsStmt = $pdo->prepare("SELECT id, title, category, clauses, is_default FROM terms_presets WHERE user_id = ? ORDER BY is_default DESC, title ASC");
$termsStmt->execute([$userId]);
$termsPresets = $termsStmt->fetchAll();

// Pre-selected customer if passed via query param
$preselectedCustId = (int)($_GET['customer_id'] ?? 0);
$preselectedCustomer = null;
if ($preselectedCustId) {
    foreach ($customers as $c) {
        if ($c['id'] == $preselectedCustId) {
            $preselectedCustomer = $c;
            break;
        }
    }
}

// Generate new quotation number
$defaultQuoteNumber = generate_quotation_number($biz);

$pageTitle = 'Quotation Builder';
$activeNav = 'quotations';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header" style="margin-bottom: 14px;">
    <div>
        <h1 class="page-title">Quotation Builder</h1>
        <div class="page-subtitle">Drafting proposal <strong style="color: var(--primary);"><?= e($defaultQuoteNumber) ?></strong></div>
    </div>
    <div class="page-actions">
        <a href="<?= BASE_URL ?>/quotations/index.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Exit Builder
        </a>
        <button type="button" class="btn btn-primary btn-sm" id="saveQuoteBtn">
            <i class="fas fa-save"></i> Save Quotation
        </button>
    </div>
</div>

<div class="builder-layout">
    <!-- LEFT PANEL: Dynamic Form Editor -->
    <div class="builder-editor-panel">
        <div class="builder-panel-header">
            <div style="font-weight: 700; font-size: 13.5px; color: var(--text-primary);">
                <i class="fas fa-sliders-h" style="color: var(--primary); margin-right: 6px;"></i> Proposal Sections
            </div>
            <div id="autosaveBadge" class="autosave-status saved">
                <i class="fas fa-check-circle"></i> Ready
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
                        <input type="text" id="field_quote_number" class="form-control" value="<?= e($defaultQuoteNumber) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reference Number</label>
                        <input type="text" id="field_ref_number" class="form-control" value="REF-<?= date('ymd') ?>" placeholder="e.g. PIPL/26-27/UiPrime/REF-101">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proposal Date <span class="required">*</span></label>
                        <input type="date" id="field_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Valid Until</label>
                        <input type="date" id="field_valid_until" class="form-control" value="<?= date('Y-m-d', strtotime('+15 days')) ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Subject / Purpose Line <span class="required">*</span></label>
                        <input type="text" id="field_subject" class="form-control" value="UiPrime Enterprise Automation Edition" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Prepared By</label>
                        <input type="text" id="field_prepared_by" class="form-control" value="<?= e($biz['contact_person'] ?: 'Sales Representative') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proposal Status</label>
                        <select id="field_status" class="form-control">
                            <option value="Draft" selected>Draft</option>
                            <option value="Sent">Sent to Client</option>
                            <option value="Viewed">Viewed by Client</option>
                            <option value="Accepted">Accepted</option>
                            <option value="Rejected">Rejected</option>
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
                            <option value="<?= $c['id'] ?>" <?= ($preselectedCustId == $c['id']) ? 'selected' : '' ?>>
                                <?= e($c['company_name'] ?: $c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid">
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Recipient Company Name <span class="required">*</span></label>
                        <input type="text" id="cust_company" class="form-control" placeholder="Apex Motors Private Limited" value="<?= e($preselectedCustomer['company_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Person Name</label>
                        <input type="text" id="cust_name" class="form-control" placeholder="Mr. Amit Agarwal" value="<?= e($preselectedCustomer['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Designation / Title</label>
                        <input type="text" id="cust_contact" class="form-control" placeholder="IT Head / Director" value="<?= e($preselectedCustomer['contact_person'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Billing Street Address</label>
                        <textarea id="cust_address" class="form-control" rows="2" placeholder="Full billing address"><?= e($preselectedCustomer['billing_address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Client GSTIN</label>
                        <input type="text" id="cust_gstin" class="form-control" placeholder="08AAACA1111A1Z1" value="<?= e($preselectedCustomer['gstin'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Client Phone</label>
                        <input type="text" id="cust_phone" class="form-control" placeholder="+91 98290 12345" value="<?= e($preselectedCustomer['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Client Email</label>
                        <input type="email" id="cust_email" class="form-control" placeholder="client@company.com" value="<?= e($preselectedCustomer['email'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- TAB 3: Cover Letter & Company Narrative -->
            <div class="tab-pane" id="tab-intro">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Salutation</label>
                        <input type="text" id="intro_salutation" class="form-control" value="Dear Sir,">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Greeting</label>
                        <input type="text" id="intro_greeting" class="form-control" value="Greetings from Priyam!!">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Company Track Record / History Paragraph</label>
                        <textarea id="intro_p1" class="form-control" rows="3">We are pleased to introduce ourselves as one of the leading commercial software application vendors in India. We have a rich experience of selling, supporting & implementation of application software for more than 31 years and having a hardcore technically strong team to serve & support our prestigious clientele. We have more than 19,000+ satisfied users of Tally & other solutions in India.</textarea>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Enterprise Capabilities Paragraph</label>
                        <textarea id="intro_p2" class="form-control" rows="3">Apart from being an Authorized "5 Star Certified Partner and GVLA Partner" of Tally Solutions Pvt Ltd, we have expanded our capabilities to deliver comprehensive Tally Applications, Multi-Branch Accounting, ERPs, Cloud Services, RPA Automation, API Integration, and Industry-Specific Solutions.</textarea>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Capabilities Heading</label>
                        <input type="text" id="intro_rel_heading" class="form-control" value="At the same time, we wish to introduce our relationship and core competencies as follows:">
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Competencies List (One per line)</label>
                        <textarea id="intro_capabilities" class="form-control" rows="6">Tally Authorized "5 Star Certified Partner"
RPA Solutions Provider & Process Automation Specialist
Tally on Cloud & Cloud Backup Solutions
Automobile DMS to Tally Integrations through Excel, RPA & API Integration
Tally Integrator & Customization Partner
Centralized Branch Accounting Solutions
Workflow Management Solutions
UiPrime Automation System</textarea>
                    </div>

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Transition Statement</label>
                        <input type="text" id="intro_transition" class="form-control" value="This has reference to our detailed discussion with you regarding UiPrime Enterprise Edition.">
                    </div>
                </div>
            </div>

            <!-- TAB 4: Commercial Items & Pricing Engine -->
            <div class="tab-pane" id="tab-items">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-weight: 700; font-size: 13px;">Commercial Line Items</span>
                    <button type="button" class="btn btn-secondary btn-sm" id="addItemBtn">
                        <i class="fas fa-plus"></i> Add Line Item
                    </button>
                </div>

                <div id="itemsContainer"></div>

                <!-- Pricing Calculation Adjustments -->
                <div class="card" style="margin-top: 16px; background: #f8fafc;">
                    <div class="card-header" style="background: transparent;">
                        <div class="card-title" style="font-size: 14px;"><i class="fas fa-calculator" style="margin-right: 6px;"></i> Taxes, Discounts & Totals</div>
                    </div>
                    <div class="card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">GST Tax Mode</label>
                                <select id="field_tax_type" class="form-control">
                                    <option value="GST" selected>GST (CGST + SGST Split)</option>
                                    <option value="IGST">IGST (Inter-State Single Tax)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">GST Tax Rate (%)</label>
                                <input type="number" step="0.5" id="field_gst_rate" class="form-control" value="18.0">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Overall Discount (₹)</label>
                                <input type="number" step="0.01" id="field_overall_discount" class="form-control" value="0.00">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Additional Charges (₹)</label>
                                <input type="number" step="0.01" id="field_additional_charges" class="form-control" value="0.00" placeholder="Shipping/handling">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Advance Payment (₹)</label>
                                <input type="number" step="0.01" id="field_advance_amount" class="form-control" value="0.00">
                            </div>

                            <div class="form-group" style="display: flex; align-items: center; gap: 8px; margin-top: 24px;">
                                <input type="checkbox" id="field_tax_inclusive" value="1">
                                <label for="field_tax_inclusive" style="font-size: 12.5px; cursor: pointer; margin: 0;">Prices are Tax-Inclusive</label>
                            </div>
                        </div>

                        <!-- Live Summary Box -->
                        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px; margin-top: 14px; font-size: 13px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Gross Subtotal:</span>
                                <strong id="sum_subtotal">₹ 0.00</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px; color: var(--danger);">
                                <span>Total Discounts:</span>
                                <span id="sum_discounts">₹ 0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Taxable Amount:</span>
                                <strong id="sum_taxable">₹ 0.00</strong>
                            </div>
                            <div id="sum_tax_breakdown" style="padding: 6px 0; border-top: 1px dashed var(--border-color); border-bottom: 1px dashed var(--border-color); margin-bottom: 6px;"></div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span>Round Off:</span>
                                <span id="sum_round_off">₹ 0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; color: var(--primary);">
                                <span>Grand Total:</span>
                                <span id="sum_grand_total">₹ 0.00</span>
                            </div>
                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; font-style: italic;" id="sum_words"></div>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="form-group" style="margin-top: 14px;">
                    <label class="form-label">Operational Notes (One per line)</label>
                    <textarea id="notes_textarea" class="form-control" rows="3">UiPrime Workflow Automation is dependent on DMS Portal (If DMS not opening/working data will not download).
UiPrime will generate excel reports as per work flow confirmation.
UiPrime is a Robotic Technology. It requires similar environment to perform.</textarea>
                </div>
            </div>

            <!-- TAB 5: Scope, Inclusions, Hardware Specs -->
            <div class="tab-pane" id="tab-scope">
                <div class="form-group">
                    <label class="form-label">Scope Heading</label>
                    <input type="text" id="scope_heading" class="form-control" value="Subscription Includes:">
                </div>

                <div class="form-group">
                    <label class="form-label">Scope Inclusions (One per line)</label>
                    <textarea id="scope_items" class="form-control" rows="6">BOT Installation, Implementation and one-time training to manage logs.
Portal Login Automation: The automation bot will securely log in to DMS portal using provided credentials.
Excel File Retrieval: Bot systematically navigates through designated portals and downloads designated files.
Data formulation & Processing: Formatting, filtering, and merging as per final SRS document.
Online support through chat, Email and Remote Access under subscription period.
Bug Fixing in Bot automation as per final confirmation on email of SRS.</textarea>
                </div>

                <div style="border-top: 1px solid var(--border-color); margin: 16px 0;"></div>

                <div class="form-group">
                    <label class="form-label">Change Request Heading</label>
                    <input type="text" id="custom_heading" class="form-control" value="Additional Customization request:">
                </div>

                <div class="form-group">
                    <label class="form-label">Change Request Description</label>
                    <input type="text" id="custom_desc" class="form-control" value="Various components of the change request are as follows, and the time and effort for all these are chargeable:">
                </div>

                <div class="form-group">
                    <label class="form-label">Workflow Steps (One per line)</label>
                    <textarea id="custom_steps" class="form-control" rows="6">Requirement study
Gap Analysis
Solution design
Approvals & discussions
Development
Testing
Deployment</textarea>
                </div>

                <div style="border-top: 1px solid var(--border-color); margin: 16px 0;"></div>

                <div class="form-group">
                    <label class="form-label">System Requirements Heading</label>
                    <input type="text" id="sysreq_heading" class="form-control" value="System Requirements for UiPrime:">
                </div>

                <div class="form-group">
                    <label class="form-label">Hardware & Environment Specs (Format: Key: Value)</label>
                    <textarea id="sysreq_text" class="form-control" rows="4">OS: Windows 10/11 (64-bit)
RAM: 4 GB (Minimum)
Disk: 5 GB (Minimum free space)
NIC: Stable Internet Connectivity</textarea>
                </div>
            </div>

            <!-- TAB 6: Terms, Payment & Banking -->
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
                    <textarea id="termsTextarea" class="form-control" rows="12">This proposal is to be accepted along with a Mandate / Purchase Order issued in favour of Priyam Infotech Solutions Pvt Ltd, Jaipur.
Correspondence Address: 401-404, Neelkanth-1, Bhawani Singh Road, C-Scheme, Jaipur-302001.
Validity period: This proposal is valid for 15 days.
The payment terms are as follows: 100% in advance along with PO.
Prices mentioned in the proposal are exclusive of taxes. All taxes shall be charged 18% extra as applicable.
Certificate relating to Tax deducted at source, if any from payments made has to be issued before Financial Year end.
Customer shall provide requisite approvals, sign-offs and certificate of deliverables on a timely basis.
All user security settings have to be provided before starting implementation.
Hand holding support applicable only on "UiPrime Application".
The basic server infrastructure like Internet, user security and DMS permissions has to be provided by client.
All Credentials security has to be taken care by your team only.
Any further development and modifications will be charged extra.
Any customization or process change will be treated as new requirement and is chargeable on case-to-case basis.</textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Terms Policy</label>
                    <input type="text" id="field_payment_terms" class="form-control" value="Payment terms: 100% in advance with Order / Mandate.">
                </div>
            </div>

            <!-- TAB 7: Template Layout Switcher -->
            <div class="tab-pane" id="tab-template">
                <div style="font-weight: 700; margin-bottom: 12px;">Select Template Style</div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 2px solid var(--primary); border-radius: 6px; cursor: pointer; background: var(--primary-light);">
                        <input type="radio" name="template_id_radio" value="classic" checked style="margin-top: 4px;">
                        <div>
                            <strong>Commercial Proposal – Classic (Official 3-Page Format)</strong>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                Exact reproduction of the 3-page proposal layout with formal cover letter, scope table, change request steps, hardware specs, and legal clauses.
                            </div>
                        </div>
                    </label>

                    <label style="display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer;">
                        <input type="radio" name="template_id_radio" value="modern" style="margin-top: 4px;">
                        <div>
                            <strong>Modern Clean</strong>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                Contemporary SaaS theme with rounded badge borders and minimal dividers.
                            </div>
                        </div>
                    </label>

                    <label style="display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer;">
                        <input type="radio" name="template_id_radio" value="corporate" style="margin-top: 4px;">
                        <div>
                            <strong>Executive Corporate</strong>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                High-formality enterprise template featuring dark corporate border grids.
                            </div>
                        </div>
                    </label>

                    <label style="display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer;">
                        <input type="radio" name="template_id_radio" value="minimal" style="margin-top: 4px;">
                        <div>
                            <strong>Minimalist Slate</strong>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                Ultra-clean typography focused on high readability and compact space.
                            </div>
                        </div>
                    </label>

                    <label style="display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid var(--border-color); border-radius: 6px; cursor: pointer;">
                        <input type="radio" name="template_id_radio" value="elegant" style="margin-top: 4px;">
                        <div>
                            <strong>Boutique Elegant</strong>
                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                Sophisticated serif titles and luxury warm gold/olive jewel accents.
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </form>

        <div class="builder-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button type="button" class="btn btn-primary" onclick="builder.saveQuotation(false)">
                <i class="fas fa-save"></i> Save & Continue
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
    quotation_number: <?= json_encode($defaultQuoteNumber) ?>,
    business_snapshot: <?= json_encode($biz) ?>,
    customer_id: <?= json_encode($preselectedCustId ?: null) ?>,
    customer_snapshot: <?= json_encode($preselectedCustomer ?: [
        'company_name' => 'Apex Motors Private Limited',
        'name' => 'Mr. Amit Agarwal',
        'contact_person' => 'IT Head',
        'billing_address' => 'Plot No. 12, Transport Nagar, Tonk Road, Jaipur, Rajasthan 302015',
        'gstin' => '08AAACA1111A1Z1'
    ]) ?>
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
