<?php
$pageTitle = $pageTitle ?? 'CivicTrack';
$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> | CivicTrack</title>
<link rel="stylesheet" href="/CivicTrack/css/style.css">
<script defer src="/CivicTrack/js/app.js"></script>
</head>
<body>
<header class="site-header">
  <a class="brand" href="/CivicTrack/index.php"><span class="brand-mark">C</span><span>Civic<span class="brand-light">Track</span><small>Report • Track • Resolve</small></span></a>
  <button class="menu-toggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>
  <nav class="nav-links">
    <a href="/CivicTrack/index.php">Home</a>
    <a href="/CivicTrack/directory.php">Issue Directory</a>

    <?php if (is_logged_in()): ?>

        <a href="/CivicTrack/dashboard.php">Dashboard</a>
        <a href="/CivicTrack/report.php">Report Issue</a>

        <?php if (in_array(current_user()['role'], ['admin','staff'], true)): ?>
            <a href="/CivicTrack/admin/index.php">Administration</a>
        <?php endif; ?>

        <a class="nav-user" href="/CivicTrack/profile.php">
            <?= e(current_user()['name']) ?>
        </a>

        <form class="inline-form" method="post" action="/CivicTrack/logout.php">
            <?= csrf_field() ?>
            <button class="nav-logout">Logout</button>
        </form>

    <?php else: ?>

        <a href="/CivicTrack/login.php">Login</a>
        <a class="btn btn-small" href="/CivicTrack/register.php">Create account</a>

    <?php endif; ?>
</nav></header>
<main class="container">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
