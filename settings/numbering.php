<?php
/**
 * Quotation Studio - Quotation Numbering Settings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();
$biz = current_business();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Security token invalid.';
    } else {
        $prefix = trim($_POST['numbering_prefix'] ?? 'QT');
        $fy = trim($_POST['numbering_fy'] ?? '');
        $code = trim($_POST['numbering_code'] ?? '');
        $seq = max(1, (int)($_POST['numbering_seq'] ?? 1));
        $digits = max(1, min(6, (int)($_POST['numbering_digits'] ?? 3)));

        $stmt = $pdo->prepare("
            UPDATE business_profiles SET
                numbering_prefix = ?, numbering_fy = ?, numbering_code = ?,
                numbering_seq = ?, numbering_digits = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$prefix, $fy, $code, $seq, $digits, $biz['id'], $userId]);

        flash_set('success', 'Quotation numbering scheme saved successfully.');
        header("Location: " . BASE_URL . "/settings/numbering.php");
        exit;
    }
}

$pageTitle = 'Quotation Numbering Scheme';
$activeNav = 'settings_numbering';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Quotation Numbering Settings</h1>
        <div class="page-subtitle">Configure automated quotation numbering patterns, financial years, and sequences.</div>
    </div>
</div>

<div class="card" style="max-width: 780px;">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-hashtag" style="color: var(--primary); margin-right: 8px;"></i> Numbering Pattern Configuration</div>
    </div>
    <form method="POST" action="">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="numbering_prefix">Company / Series Prefix <span class="required">*</span></label>
                    <input type="text" name="numbering_prefix" id="numbering_prefix" class="form-control" value="<?= e($biz['numbering_prefix'] ?? 'PIPL') ?>" required oninput="updateLivePreview()">
                    <span style="font-size: 11px; color: var(--text-muted);">e.g. PIPL, QT, EST, PROP</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="numbering_fy">Financial Year / Date Code</label>
                    <input type="text" name="numbering_fy" id="numbering_fy" class="form-control" value="<?= e($biz['numbering_fy'] ?? '26-27') ?>" placeholder="e.g. 26-27 or 2026" oninput="updateLivePreview()">
                </div>

                <div class="form-group">
                    <label class="form-label" for="numbering_code">Product / Department Code (Optional)</label>
                    <input type="text" name="numbering_code" id="numbering_code" class="form-control" value="<?= e($biz['numbering_code'] ?? 'UiPrime') ?>" placeholder="e.g. UiPrime, IT, ERP" oninput="updateLivePreview()">
                </div>

                <div class="form-group">
                    <label class="form-label" for="numbering_digits">Padding Digits</label>
                    <select name="numbering_digits" id="numbering_digits" class="form-control" onchange="updateLivePreview()">
                        <option value="2" <?= ($biz['numbering_digits'] == 2) ? 'selected' : '' ?>>2 digits (01)</option>
                        <option value="3" <?= ($biz['numbering_digits'] == 3) ? 'selected' : '' ?>>3 digits (001)</option>
                        <option value="4" <?= ($biz['numbering_digits'] == 4) ? 'selected' : '' ?>>4 digits (0001)</option>
                        <option value="5" <?= ($biz['numbering_digits'] == 5) ? 'selected' : '' ?>>5 digits (00001)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="numbering_seq">Next Sequence Number <span class="required">*</span></label>
                    <input type="number" name="numbering_seq" id="numbering_seq" class="form-control" value="<?= e($biz['numbering_seq'] ?? 1) ?>" min="1" required oninput="updateLivePreview()">
                </div>
            </div>

            <!-- Live Sample Preview Box -->
            <div style="background: var(--bg-main); border: 2px dashed var(--primary); border-radius: var(--radius-sm); padding: 18px; margin-top: 14px; text-align: center;">
                <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 600; margin-bottom: 6px;">Next Quotation Number Preview</div>
                <div id="previewNumberDisplay" style="font-family: var(--font-heading); font-size: 24px; font-weight: 700; color: var(--primary); letter-spacing: 0.5px;">
                    <?= e(generate_quotation_number($biz)) ?>
                </div>
            </div>
        </div>
        <div class="card-footer" style="text-align: right;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save Numbering Settings
            </button>
        </div>
    </form>
</div>

<script>
function updateLivePreview() {
    const prefix = document.getElementById('numbering_prefix').value.trim() || 'QT';
    const fy = document.getElementById('numbering_fy').value.trim();
    const code = document.getElementById('numbering_code').value.trim();
    const digits = parseInt(document.getElementById('numbering_digits').value) || 3;
    const seq = parseInt(document.getElementById('numbering_seq').value) || 1;

    const seqStr = String(seq).padStart(digits, '0');
    let preview = '';

    if (code) {
        preview = `${prefix}/${fy ? fy + '/' : ''}${code}/${seqStr}`;
    } else if (fy) {
        preview = `${prefix}-${fy}-${seqStr}`;
    } else {
        preview = `${prefix}-${new Date().getFullYear()}-${seqStr}`;
    }

    document.getElementById('previewNumberDisplay').textContent = preview;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
