<?php
/**
 * Quotation Studio - Helper Utilities & Functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function e(?string $string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

function format_currency(float|int|string|null $amount, string $symbol = '₹'): string {
    $val = (float)($amount ?? 0);
    // Indian currency comma separation
    $negative = $val < 0;
    $val = abs($val);
    $parts = explode('.', number_format($val, 2, '.', ''));
    $whole = $parts[0];
    $dec = $parts[1] ?? '00';

    if (strlen($whole) > 3) {
        $lastThree = substr($whole, -3);
        $otherNumbers = substr($whole, 0, -3);
        $otherNumbers = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $otherNumbers);
        $formatted = $otherNumbers . ',' . $lastThree;
    } else {
        $formatted = $whole;
    }

    return ($negative ? '-' : '') . $symbol . ' ' . $formatted . '.' . $dec;
}

function format_date(?string $date, string $format = 'd M Y'): string {
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : $date;
}

function status_badge(string $status): string {
    $map = [
        'Draft' => ['class' => 'badge-draft', 'icon' => 'fa-pencil-alt', 'label' => 'Draft'],
        'Sent' => ['class' => 'badge-sent', 'icon' => 'fa-paper-plane', 'label' => 'Sent'],
        'Viewed' => ['class' => 'badge-viewed', 'icon' => 'fa-eye', 'label' => 'Viewed'],
        'Accepted' => ['class' => 'badge-accepted', 'icon' => 'fa-check-circle', 'label' => 'Accepted'],
        'Rejected' => ['class' => 'badge-rejected', 'icon' => 'fa-times-circle', 'label' => 'Rejected'],
        'Expired' => ['class' => 'badge-expired', 'icon' => 'fa-clock', 'label' => 'Expired'],
        'Invoiced' => ['class' => 'badge-invoiced', 'icon' => 'fa-file-invoice', 'label' => 'Invoiced'],
        'Paid' => ['class' => 'badge-accepted', 'icon' => 'fa-check-double', 'label' => 'Paid'],
        'Partially Paid' => ['class' => 'badge-viewed', 'icon' => 'fa-adjust', 'label' => 'Partially Paid'],
        'Unpaid' => ['class' => 'badge-draft', 'icon' => 'fa-exclamation-circle', 'label' => 'Unpaid'],
        'Overdue' => ['class' => 'badge-rejected', 'icon' => 'fa-calendar-times', 'label' => 'Overdue']
    ];

    $info = $map[$status] ?? ['class' => 'badge-default', 'icon' => 'fa-circle', 'label' => $status];
    return sprintf(
        '<span class="badge %s"><i class="fas %s"></i> %s</span>',
        e($info['class']),
        e($info['icon']),
        e($info['label'])
    );
}

function flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function render_flash(): string {
    $flash = flash_get();
    if (!$flash) return '';

    $type = $flash['type'] === 'error' ? 'danger' : $flash['type'];
    $icon = $type === 'success' ? 'fa-check-circle' : ($type === 'danger' ? 'fa-exclamation-triangle' : 'fa-info-circle');

    return sprintf(
        '<div class="alert alert-%s alert-dismissible animate-fade-in" role="alert">
            <div class="alert-icon"><i class="fas %s"></i></div>
            <div class="alert-content">%s</div>
            <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>',
        e($type),
        e($icon),
        e($flash['message'])
    );
}

function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function generate_quotation_number(array $biz): string {
    $prefix = trim($biz['numbering_prefix'] ?? 'QT');
    $fy = trim($biz['numbering_fy'] ?? date('y') . '-' . (date('y') + 1));
    $code = trim($biz['numbering_code'] ?? '');
    $digits = max(1, (int)($biz['numbering_digits'] ?? 3));
    $seq = max(1, (int)($biz['numbering_seq'] ?? 1));

    $seqStr = str_pad((string)$seq, $digits, '0', STR_PAD_LEFT);

    // If code exists, format as PIPL/26-27/UiPrime/001
    if (!empty($code)) {
        return sprintf("%s/%s/%s/%s", $prefix, $fy, $code, $seqStr);
    }

    // Default format: QT-2026-001
    return sprintf("%s-%s-%s", $prefix, date('Y'), $seqStr);
}

function increment_quotation_sequence(int $bizId): void {
    $pdo = get_db();
    $stmt = $pdo->prepare("UPDATE business_profiles SET numbering_seq = numbering_seq + 1 WHERE id = ?");
    $stmt->execute([$bizId]);
}

function handle_file_upload(array $file, string $subfolder, array $allowedTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml']): ?string {
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Validate size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException("File size exceeds 5MB limit.");
    }

    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowedTypes, true)) {
        throw new RuntimeException("Invalid file type. Allowed formats: PNG, JPG, WebP, SVG.");
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $ext = strtolower($ext) ?: 'png';
    $safeName = bin2hex(random_bytes(10)) . '.' . $ext;

    $targetDir = UPLOADS_PATH . '/' . trim($subfolder, '/');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $destPath = $targetDir . '/' . $safeName;
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return 'public/uploads/' . trim($subfolder, '/') . '/' . $safeName;
    }

    return null;
}
