<?php
/**
 * Quotation Studio - Quotation Templates Gallery
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$templates = $pdo->query("SELECT * FROM quotation_templates")->fetchAll();

$pageTitle = 'Quotation Templates';
$activeNav = 'templates';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quotation Proposal Templates</h1>
        <div class="page-subtitle">Select an enterprise design system for your commercial proposals. Every layout dynamically preserves custom data and calculations.</div>
    </div>
</div>

<div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- 1. Commercial Proposal – Classic -->
    <div class="card" style="display: flex; flex-direction: column;">
        <div style="background: linear-gradient(135deg, #0d5c75, #134e62); padding: 30px 24px; color: #ffffff; border-top-left-radius: var(--radius-md); border-top-right-radius: var(--radius-md);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #ffffff;">Primary Template</span>
                <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">3-Page Formal A4</span>
            </div>
            <h3 style="font-family: Outfit; font-size: 20px; font-weight: 700; margin-bottom: 6px;">Commercial Proposal – Classic</h3>
            <div style="font-size: 12px; opacity: 0.9;">High-fidelity multi-page proposal replicating the official enterprise structure.</div>
        </div>
        <div class="card-body" style="flex: 1; font-size: 13px; color: var(--text-secondary);">
            <p style="margin-bottom: 12px;">
                Includes formal <strong>Cover Letter & 31-year narrative (Page 1)</strong>, structured <strong>Commercial Particulars & Inclusions Table (Page 2)</strong>, <strong>Change Request Workflow & Hardware Specs</strong>, and <strong>13 Legal Terms & Banking Authorization (Page 3)</strong>.
            </p>
            <div style="font-size: 11.5px; color: var(--text-muted); background: var(--bg-main); padding: 8px 12px; border-radius: 4px;">
                <i class="fas fa-check-circle" style="color: var(--secondary);"></i> 100% dynamic & editable
            </div>
        </div>
        <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="<?= BASE_URL ?>/templates/classic.php" class="btn btn-secondary btn-sm" target="_blank">
                <i class="fas fa-eye"></i> Preview
            </a>
            <a href="<?= BASE_URL ?>/quotations/create.php?template=classic" class="btn btn-primary btn-sm">
                <i class="fas fa-magic"></i> Use Classic
            </a>
        </div>
    </div>

    <!-- 2. Modern Clean -->
    <div class="card" style="display: flex; flex-direction: column;">
        <div style="background: linear-gradient(135deg, #0284c7, #0369a1); padding: 30px 24px; color: #ffffff; border-top-left-radius: var(--radius-md); border-top-right-radius: var(--radius-md);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #ffffff;">Tech & SaaS</span>
                <span style="font-size: 11px; text-transform: uppercase;">A4 Multi-page</span>
            </div>
            <h3 style="font-family: Outfit; font-size: 20px; font-weight: 700; margin-bottom: 6px;">Modern Clean</h3>
            <div style="font-size: 12px; opacity: 0.9;">Contemporary tech design with geometric typography.</div>
        </div>
        <div class="card-body" style="flex: 1; font-size: 13px; color: var(--text-secondary);">
            <p style="margin-bottom: 12px;">
                Crisp cyan/azure headers, rounded badge tags, and streamlined commercial cards. Recommended for cloud, software, and digital agency bids.
            </p>
            <div style="font-size: 11.5px; color: var(--text-muted); background: var(--bg-main); padding: 8px 12px; border-radius: 4px;">
                <i class="fas fa-check-circle" style="color: var(--secondary);"></i> Modern spacing & clean fonts
            </div>
        </div>
        <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="<?= BASE_URL ?>/templates/modern.php" class="btn btn-secondary btn-sm" target="_blank">
                <i class="fas fa-eye"></i> Preview
            </a>
            <a href="<?= BASE_URL ?>/quotations/create.php?template=modern" class="btn btn-primary btn-sm">
                <i class="fas fa-magic"></i> Use Modern
            </a>
        </div>
    </div>

    <!-- 3. Executive Corporate -->
    <div class="card" style="display: flex; flex-direction: column;">
        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); padding: 30px 24px; color: #ffffff; border-top-left-radius: var(--radius-md); border-top-right-radius: var(--radius-md);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #ffffff;">Executive</span>
                <span style="font-size: 11px; text-transform: uppercase;">Corporate</span>
            </div>
            <h3 style="font-family: Outfit; font-size: 20px; font-weight: 700; margin-bottom: 6px;">Executive Corporate</h3>
            <div style="font-size: 12px; opacity: 0.9;">Traditional formal enterprise proposal layout.</div>
        </div>
        <div class="card-body" style="flex: 1; font-size: 13px; color: var(--text-secondary);">
            <p style="margin-bottom: 12px;">
                Dark slate headers with crisp grid dividers, strict statutory tax breakdown formatting, and formal sign-off hierarchy.
            </p>
            <div style="font-size: 11.5px; color: var(--text-muted); background: var(--bg-main); padding: 8px 12px; border-radius: 4px;">
                <i class="fas fa-check-circle" style="color: var(--secondary);"></i> Enterprise tender ready
            </div>
        </div>
        <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="<?= BASE_URL ?>/templates/corporate.php" class="btn btn-secondary btn-sm" target="_blank">
                <i class="fas fa-eye"></i> Preview
            </a>
            <a href="<?= BASE_URL ?>/quotations/create.php?template=corporate" class="btn btn-primary btn-sm">
                <i class="fas fa-magic"></i> Use Corporate
            </a>
        </div>
    </div>

    <!-- 4. Minimalist Slate -->
    <div class="card" style="display: flex; flex-direction: column;">
        <div style="background: linear-gradient(135deg, #475569, #334155); padding: 30px 24px; color: #ffffff; border-top-left-radius: var(--radius-md); border-top-right-radius: var(--radius-md);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #ffffff;">Minimal</span>
                <span style="font-size: 11px; text-transform: uppercase;">Clean Lines</span>
            </div>
            <h3 style="font-family: Outfit; font-size: 20px; font-weight: 700; margin-bottom: 6px;">Minimalist Slate</h3>
            <div style="font-size: 12px; opacity: 0.9;">Focus on pure clarity and high legibility.</div>
        </div>
        <div class="card-body" style="flex: 1; font-size: 13px; color: var(--text-secondary);">
            <p style="margin-bottom: 12px;">
                Reduced ink footprint with razor-thin hairline borders, subtle typography contrast, and high-density line items.
            </p>
            <div style="font-size: 11.5px; color: var(--text-muted); background: var(--bg-main); padding: 8px 12px; border-radius: 4px;">
                <i class="fas fa-check-circle" style="color: var(--secondary);"></i> Minimalist black & white
            </div>
        </div>
        <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="<?= BASE_URL ?>/templates/minimal.php" class="btn btn-secondary btn-sm" target="_blank">
                <i class="fas fa-eye"></i> Preview
            </a>
            <a href="<?= BASE_URL ?>/quotations/create.php?template=minimal" class="btn btn-primary btn-sm">
                <i class="fas fa-magic"></i> Use Minimal
            </a>
        </div>
    </div>

    <!-- 5. Boutique Elegant -->
    <div class="card" style="display: flex; flex-direction: column;">
        <div style="background: linear-gradient(135deg, #85a438, #556b2f); padding: 30px 24px; color: #ffffff; border-top-left-radius: var(--radius-md); border-top-right-radius: var(--radius-md);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <span class="badge" style="background: rgba(255,255,255,0.2); color: #ffffff;">Boutique</span>
                <span style="font-size: 11px; text-transform: uppercase;">Warm Accent</span>
            </div>
            <h3 style="font-family: Outfit; font-size: 20px; font-weight: 700; margin-bottom: 6px;">Boutique Elegant</h3>
            <div style="font-size: 12px; opacity: 0.9;">Warm olive and gold accents for premium consulting.</div>
        </div>
        <div class="card-body" style="flex: 1; font-size: 13px; color: var(--text-secondary);">
            <p style="margin-bottom: 12px;">
                Designed for high-touch advisory proposals, architecture, and luxury consultancies. Rich warm tones and stylish headings.
            </p>
            <div style="font-size: 11.5px; color: var(--text-muted); background: var(--bg-main); padding: 8px 12px; border-radius: 4px;">
                <i class="fas fa-check-circle" style="color: var(--secondary);"></i> Refined jewel aesthetic
            </div>
        </div>
        <div class="card-footer" style="display: flex; justify-content: space-between; align-items: center;">
            <a href="<?= BASE_URL ?>/templates/elegant.php" class="btn btn-secondary btn-sm" target="_blank">
                <i class="fas fa-eye"></i> Preview
            </a>
            <a href="<?= BASE_URL ?>/quotations/create.php?template=elegant" class="btn btn-primary btn-sm">
                <i class="fas fa-magic"></i> Use Elegant
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
