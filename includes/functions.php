<?php
declare(strict_types=1);

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function redirect(string $path): never {
    $base = '/CivicTrack/';

    // Allow only local application paths
    if (
        preg_match('/[\r\n]/', $path) ||
        str_starts_with($path, '//') ||
        preg_match('/^[a-z][a-z0-9+.-]*:/i', $path)
    ) {
        $path = 'index.php';
    }

    $path = ltrim($path, '/');

    header('Location: ' . $base . $path);
    exit;
}
function is_logged_in(): bool { return isset($_SESSION['user']['id']); }
function current_user(): array { return $_SESSION['user'] ?? []; }
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('Please log in to continue.', 'warning');
        redirect('login.php');
    }
}
function require_role(array $roles): void {
    require_login();
    if (!in_array(current_user()['role'], $roles, true)) {
        http_response_code(403);
        exit('Access denied: your account does not have permission for this action.');
    }
}
function set_flash(string $message, string $type='success'): void {
    $_SESSION['flash'] = ['message'=>$message, 'type'=>$type];
}
function get_flash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Security check failed. Refresh the page and try again.');
    }
}
function log_audit(PDO $pdo, ?int $userId, string $action, string $entity, ?int $entityId=null): void {
    $stmt = $pdo->prepare('INSERT INTO audit_logs(user_id, action, entity_type, entity_id, ip_address) VALUES(?,?,?,?,?)');
    $stmt->execute([$userId, $action, $entity, $entityId, $_SERVER['REMOTE_ADDR'] ?? null]);
}
function complaint_code(int $id): string { return 'CT-' . date('Y') . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT); }
function status_label(string $status): string {
    return ucwords(str_replace('_', ' ', $status));
}
function upload_image(array $file): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new RuntimeException('Image upload failed.');
$tmp = $file['tmp_name'] ?? '';

if (!is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
    throw new RuntimeException('Invalid uploaded image.');
}
    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) throw new RuntimeException('Image must be 2 MB or smaller.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Only JPG, PNG and WEBP images are allowed.');
    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false) throw new RuntimeException('Uploaded file is not a valid image.');
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($tmp, UPLOAD_DIR . $name)) {
    throw new RuntimeException('Could not save uploaded image.');
}
    return $name;
}
function user_can_access_issue(PDO $pdo, int $issueId): bool
{
    $user = current_user();

    $stmt = $pdo->prepare(
        'SELECT user_id, municipality_id
         FROM issues
         WHERE id = ?'
    );

    $stmt->execute([$issueId]);
    $issue = $stmt->fetch();

    if (!$issue) {
        return false;
    }

    // Admin can access all complaints
    if ($user['role'] === 'admin') {
        return true;
    }

    // Citizens can access only their own complaints
    if ($user['role'] === 'citizen') {
        return (int)$issue['user_id'] === (int)$user['id'];
    }

    // Staff can access only assigned municipalities
    if ($user['role'] === 'staff') {
        $check = $pdo->prepare(
            'SELECT 1
             FROM staff_memberships
             WHERE user_id = ?
             AND municipality_id = ?
             AND is_active = 1
             LIMIT 1'
        );

        $check->execute([
            (int)$user['id'],
            (int)$issue['municipality_id']
        ]);

        return (bool)$check->fetchColumn();
    }

    return false;
}