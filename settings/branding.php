<?php
/**
 * Quotation Studio - Branding & Color Customization
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$biz = current_business();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash_set('error', 'Invalid token.');
    } else {
        $primary = trim($_POST['primary_color'] ?? '#0d5c75');
        $secondary = trim($_POST['secondary_color'] ?? '#85a438');
        $font = trim($_POST['font_preference'] ?? 'Inter');

        $stmt = $pdo->prepare("
            UPDATE business_profiles SET
                primary_color = ?, secondary_color = ?, font_preference = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$primary, $secondary, $font, $biz['id'], $userId]);

        flash_set('success', 'Branding preferences saved successfully.');
        header("Location: " . BASE_URL . "/settings/branding.php");
        exit;
    }
}

$pageTitle = 'Branding & Theme Settings';
$activeNav = 'settings_branding';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Branding & Color Theme</h1>
        <div class="page-subtitle">Personalize proposal color accents, headers, and corporate identity.</div>
    </div>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-palette" style="color: var(--primary); margin-right: 8px;"></i> Visual Identity</div>
    </div>
    <form method="POST" action="">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="primary_color">Primary Accent Color (Headers & Accents)</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" name="primary_color" id="primary_color" value="<?= e($biz['primary_color'] ?: '#0d5c75') ?>" style="width: 48px; height: 38px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer;" oninput="updateBrandPreview()">
                        <input type="text" id="primary_text" class="form-control" value="<?= e($biz['primary_color'] ?: '#0d5c75') ?>" style="width: 120px;" readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="secondary_color">Secondary Color (Borders, Highlights, Tags)</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" name="secondary_color" id="secondary_color" value="<?= e($biz['secondary_color'] ?: '#85a438') ?>" style="width: 48px; height: 38px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer;" oninput="updateBrandPreview()">
                        <input type="text" id="secondary_text" class="form-control" value="<?= e($biz['secondary_color'] ?: '#85a438') ?>" style="width: 120px;" readonly>
                    </div>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label class="form-label" for="font_preference">Proposal Font Family</label>
                    <select name="font_preference" id="font_preference" class="form-control" onchange="updateBrandPreview()">
                        <option value="Inter" <?= ($biz['font_preference'] === 'Inter') ? 'selected' : '' ?>>Inter (Clean Modern Sans-Serif)</option>
                        <option value="Outfit" <?= ($biz['font_preference'] === 'Outfit') ? 'selected' : '' ?>>Outfit (Contemporary Premium Geometric)</option>
                        <option value="Roboto" <?= ($biz['font_preference'] === 'Roboto') ? 'selected' : '' ?>>Roboto (Corporate High-Legibility)</option>
                        <option value="Merriweather" <?= ($biz['font_preference'] === 'Merriweather') ? 'selected' : '' ?>>Merriweather (Executive Editorial Serif)</option>
                    </select>
                </div>
            </div>

            <!-- Live Card Demonstration -->
            <div id="brandDemoCard" style="border: 2px solid <?= e($biz['primary_color'] ?: '#0d5c75') ?>; border-radius: var(--radius-md); padding: 20px; background: #ffffff; margin-top: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid <?= e($biz['secondary_color'] ?: '#85a438') ?>; padding-bottom: 12px; margin-bottom: 12px;">
                    <span style="font-weight: 700; color: <?= e($biz['primary_color'] ?: '#0d5c75') ?>; font-size: 16px;">Sample Proposal Heading</span>
                    <span style="background: <?= e($biz['secondary_color'] ?: '#85a438') ?>; color: #ffffff; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">ACTIVE BADGE</span>
                </div>
                <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.6;">
                    This is how table headers, proposal accents, highlights, and borders will be rendered in your generated PDF quotations and live previews.
                </p>
            </div>
        </div>
        <div class="card-footer" style="text-align: right;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save Branding Settings
            </button>
        </div>
    </form>
</div>

<script>
function updateBrandPreview() {
    const p = document.getElementById('primary_color').value;
    const s = document.getElementById('secondary_color').value;
    document.getElementById('primary_text').value = p;
    document.getElementById('secondary_text').value = s;

    const card = document.getElementById('brandDemoCard');
    card.style.borderColor = p;
    card.querySelector('span:first-child').style.color = p;
    card.querySelector('div').style.borderBottomColor = s;
    card.querySelector('span:last-child').style.background = s;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
