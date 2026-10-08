<?php
/**
 * Quotation Studio - Terms & Conditions Presets Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_auth();

$pdo = get_db();
$userId = current_user_id();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash_set('error', 'Invalid token.');
    } else {
        $action = $_POST['action'] ?? 'add';

        if ($action === 'add' || $action === 'edit') {
            $title = trim($_POST['title'] ?? '');
            $category = trim($_POST['category'] ?? 'General');
            $clausesRaw = trim($_POST['clauses'] ?? '');
            $isDefault = !empty($_POST['is_default']) ? 1 : 0;

            // Split clauses by newline
            $clauses = array_values(array_filter(array_map('trim', explode("\n", $clausesRaw))));

            if (empty($title) || empty($clauses)) {
                flash_set('error', 'Title and at least one clause are required.');
            } else {
                if ($isDefault) {
                    $pdo->prepare("UPDATE terms_presets SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
                }

                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO terms_presets (user_id, title, category, clauses, is_default)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$userId, $title, $category, json_encode($clauses), $isDefault]);
                    flash_set('success', 'Terms preset created successfully.');
                } else {
                    $id = (int)$_POST['id'];
                    $stmt = $pdo->prepare("
                        UPDATE terms_presets SET title = ?, category = ?, clauses = ?, is_default = ?
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([$title, $category, json_encode($clauses), $isDefault, $id, $userId]);
                    flash_set('success', 'Terms preset updated successfully.');
                }
                header("Location: " . BASE_URL . "/settings/terms.php");
                exit;
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM terms_presets WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
            flash_set('success', 'Terms preset deleted.');
            header("Location: " . BASE_URL . "/settings/terms.php");
            exit;
        }
    }
}

// Fetch all presets
$stmt = $pdo->prepare("SELECT * FROM terms_presets WHERE user_id = ? ORDER BY is_default DESC, id ASC");
$stmt->execute([$userId]);
$presets = $stmt->fetchAll();

$pageTitle = 'Terms & Conditions Presets';
$activeNav = 'settings_terms';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Terms & Conditions Presets</h1>
        <div class="page-subtitle">Create reusable legal clause sets that can be instantly attached to quotations.</div>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary" onclick="openNewModal()">
            <i class="fas fa-plus"></i> New Preset
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Preset Title</th>
                    <th>Category</th>
                    <th>Clauses Count</th>
                    <th>Default</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($presets)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            No presets created yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($presets as $p):
                        $clauses = json_decode($p['clauses'] ?? '[]', true) ?: [];
                    ?>
                        <tr>
                            <td>
                                <strong><?= e($p['title']) ?></strong>
                            </td>
                            <td><span class="badge badge-draft"><?= e($p['category']) ?></span></td>
                            <td><?= count($clauses) ?> clauses</td>
                            <td>
                                <?php if ($p['is_default']): ?>
                                    <span class="badge badge-accepted"><i class="fas fa-check"></i> Default</span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(<?= json_encode($p) ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" action="" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this preset?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--danger);">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Preset Modal Dialog -->
<div id="presetModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; width: 100%; max-width: 600px; border-radius: var(--radius-md); box-shadow: var(--shadow-lg); overflow: hidden;">
        <div class="card-header">
            <div class="card-title" id="modalTitle">Add Terms Preset</div>
            <button type="button" class="alert-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="id" id="modalId" value="">
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Preset Title <span class="required">*</span></label>
                    <input type="text" name="title" id="modalInputTitle" class="form-control" placeholder="e.g. Software BOT Automation Terms" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" id="modalInputCategory" class="form-control" placeholder="e.g. Automation, SaaS, AMC" value="General">
                </div>
                <div class="form-group">
                    <label class="form-label">Clauses (Enter one clause per line) <span class="required">*</span></label>
                    <textarea name="clauses" id="modalInputClauses" class="form-control" rows="8" placeholder="1. Payment terms are 100% in advance...&#10;2. Taxes shall be charged extra as applicable...&#10;3. Proposal valid for 15 days..." required></textarea>
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="is_default" id="modalInputDefault" value="1">
                    <label for="modalInputDefault" style="margin: 0; cursor: pointer; font-size: 13px;">Set as default preset for new quotations</label>
                </div>
            </div>
            <div class="card-footer" style="text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Preset</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNewModal() {
    document.getElementById('modalTitle').textContent = 'Add Terms Preset';
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalId').value = '';
    document.getElementById('modalInputTitle').value = '';
    document.getElementById('modalInputCategory').value = 'General';
    document.getElementById('modalInputClauses').value = '';
    document.getElementById('modalInputDefault').checked = false;
    document.getElementById('presetModal').style.display = 'flex';
}

function openEditModal(preset) {
    document.getElementById('modalTitle').textContent = 'Edit Terms Preset';
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalId').value = preset.id;
    document.getElementById('modalInputTitle').value = preset.title;
    document.getElementById('modalInputCategory').value = preset.category || 'General';

    let clauses = [];
    try {
        clauses = JSON.parse(preset.clauses);
    } catch(e) {}
    document.getElementById('modalInputClauses').value = clauses.join('\n');
    document.getElementById('modalInputDefault').checked = Boolean(parseInt(preset.is_default));
    document.getElementById('presetModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('presetModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
