<?php
require_once __DIR__.'/config/config.php'; require_once __DIR__.'/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nameInput = $_POST['name'] ?? '';
    $emailInput = $_POST['email'] ?? '';
    $phoneInput = $_POST['phone'] ?? '';
    $passwordInput = $_POST['password'] ?? '';

    $name = is_string($nameInput) ? trim($nameInput) : '';
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
    $phone = is_string($phoneInput) ? trim($phoneInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';

    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors[] = 'Name must contain 2 to 120 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) {
        $errors[] = 'Enter a valid phone number.';
    }

    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Password must contain 8 to 72 characters.';
    }

    if (!$errors) {
        try {
            $s = $pdo->prepare("
                INSERT INTO users
                (name, email, phone, password_hash, role)
                VALUES (?, ?, ?, ?, 'citizen')
            ");

            $s->execute([
                $name,
                $email,
                $phone ?: null,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $id = (int)$pdo->lastInsertId();

            log_audit($pdo, $id, 'REGISTER', 'user', $id);

            set_flash('Account created. Please log in.');
            redirect('login.php');

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'This email address is already registered.';
            } else {
                error_log('Registration database error: ' . $e->getMessage());
                $errors[] = 'Registration could not be completed. Please try again.';
            }
        }
    }
}


$pageTitle = 'Create account';
require __DIR__.'/includes/header.php';
?>
<div class="auth-wrap">
<section class="auth-card">

<span class="eyebrow">JOIN CIVICTRACK</span>

<h1>Create your account</h1>

<p class="muted">Register to submit and follow civic issue reports.</p>

<?php foreach ($errors as $err): ?>
<div class="alert alert-error"><?=e($err)?></div>
<?php endforeach; ?>

<form method="post" class="form-stack">
<?=csrf_field()?>

<label>
Full name
<input name="name" required maxlength="120"
value="<?=e(is_string($_POST['name'] ?? null) ? $_POST['name'] : '')?>">
</label>

<label>
Email address
<input type="email" name="email" required
value="<?=e(is_string($_POST['email'] ?? null) ? $_POST['email'] : '')?>">
</label>

<label>
Phone (optional)
<input name="phone" maxlength="30"
value="<?=e(is_string($_POST['phone'] ?? null) ? $_POST['phone'] : '')?>">
</label>

<label>
Password
<input type="password" name="password" minlength="8"
maxlength="72" required autocomplete="new-password">
<small>Use at least 8 characters.</small>
</label>

<button class="btn btn-block">Create account</button>
</form>

<p class="auth-foot">
Already registered? <a href="login.php">Log in</a>
</p>

</section>
</div>

<?php require __DIR__.'/includes/footer.php'; ?>