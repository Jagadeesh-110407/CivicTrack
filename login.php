<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/includes/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $emailInput = $_POST['email'] ?? '';
    $passInput = $_POST['password'] ?? '';

    $email = is_string($emailInput)
        ? strtolower(trim($emailInput))
        : '';

    $pass = is_string($passInput) ? $passInput : '';

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Remove old login-attempt records.
    $pdo->exec("
        DELETE FROM login_attempts
        WHERE attempted_at < NOW() - INTERVAL 1 DAY
    ");

    // Count failed attempts in the last 15 minutes.
    $check = $pdo->prepare("
        SELECT COUNT(*)
        FROM login_attempts
        WHERE email = ?
        AND ip_address = ?
        AND attempted_at >= NOW() - INTERVAL 15 MINUTE
    ");

    $check->execute([$email, $ip]);
    $attempts = (int)$check->fetchColumn();

    if ($attempts >= 5) {
        $error = 'Too many failed login attempts. Please wait before trying again.';
    } else {
        $s = $pdo->prepare("
            SELECT id, name, email, password_hash, role, is_active
            FROM users
            WHERE email = ?
        ");

        $s->execute([$email]);
        $u = $s->fetch();

        if (
            $u &&
            $u['is_active'] &&
            password_verify($pass, $u['password_hash'])
        ) {
            // Clear failed attempts after successful login.
            $clear = $pdo->prepare("
                DELETE FROM login_attempts
                WHERE email = ? AND ip_address = ?
            ");

            $clear->execute([$email, $ip]);

            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => (int)$u['id'],
                'name' => $u['name'],
                'email' => $u['email'],
                'role' => $u['role']
            ];

            log_audit(
                $pdo,
                (int)$u['id'],
                'LOGIN',
                'user',
                (int)$u['id']
            );

            redirect('dashboard.php');

        } else {
            // Record failed login attempt.
            $insert = $pdo->prepare("
                INSERT INTO login_attempts
                (email, ip_address)
                VALUES (?, ?)
            ");

            $insert->execute([$email, $ip]);

            $error = 'Invalid login details or inactive account.';
        }
    }
}

$pageTitle = 'Login';
require __DIR__.'/includes/header.php';
?>

<div class="auth-wrap">
<section class="auth-card">

<span class="eyebrow">WELCOME BACK</span>

<h1>Log in to CivicTrack</h1>

<p class="muted">Access your reports and updates.</p>

<?php if ($error): ?>
<div class="alert alert-error"><?=e($error)?></div>
<?php endif; ?>

<form method="post" class="form-stack">

<?=csrf_field()?>

<label>
Email address
<input
    type="email"
    name="email"
    required
    autocomplete="username"
  value="<?=e(is_string($_POST['email'] ?? null) ? $_POST['email'] : '')?>"
>
</label>

<label>
Password
<input
    type="password"
    name="password"
    required
    autocomplete="current-password"
>
</label>

<button class="btn btn-block">Log in</button>

</form>

<p class="auth-foot">
New to CivicTrack?
<a href="register.php">Create an account</a>
</p>

</section>
</div>

<?php require __DIR__.'/includes/footer.php'; ?>