<?php
require_once __DIR__.'/config/config.php'; require_once __DIR__.'/includes/functions.php';
$stats = $pdo->query("SELECT COUNT(*) total, SUM(status='resolved') resolved, SUM(status IN ('pending','under_review','assigned','in_progress','reopened')) active FROM issues")->fetch();
$categories = $pdo->query('SELECT name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();
$pageTitle='Home'; require __DIR__.'/includes/header.php';
?>
<section class="hero">
 <div class="hero-copy"><span class="eyebrow">STATEWIDE CIVIC ISSUE REPORTING & RESOLUTION</span>
 <h1>Better neighbourhoods start with <em>being heard.</em></h1>
 <p>Report local infrastructure and sanitation issues, follow every update, and help participating teams coordinate solutions.</p>
 <div class="hero-actions"><a class="btn" href="<?= is_logged_in()?'report.php':'register.php' ?>">Report a problem <span>→</span></a><a class="btn btn-outline" href="directory.php">Explore issue directory</a></div>
 <p class="small-note">Academic prototype using sample administrative data. Not an official government complaint service.</p></div>
 <div class="hero-art"><div class="art-card"><div class="art-top"><span class="dot"></span><span class="dot"></span><span class="dot"></span><b>Issue overview</b></div><div class="art-number">#CT-2026-000128</div><div class="progress-line"><span></span></div><div class="art-status"><span class="status status-in_progress">In Progress</span><span>Road repair • Ward 04</span></div><div class="art-bottom"><span>✓ Report received</span><span>↗ Team assigned</span></div></div><div class="floating-icon">⌖</div><div class="floating-tag">Your voice matters</div></div>
</section>
<section class="stats-grid">
 <div class="stat-card"><span class="stat-icon">▤</span><strong><?= (int)($stats['total']??0) ?></strong><span>Reports submitted</span></div>
 <div class="stat-card"><span class="stat-icon">◷</span><strong><?= (int)($stats['active']??0) ?></strong><span>Active reports</span></div>
 <div class="stat-card"><span class="stat-icon">✓</span><strong><?= (int)($stats['resolved']??0) ?></strong><span>Resolved reports</span></div>
 <div class="stat-card"><span class="stat-icon">⌂</span><strong><?= count($categories) ?></strong><span>Issue categories</span></div>
</section>
<section class="section"><div class="section-heading"><div><span class="eyebrow">HOW IT WORKS</span><h2>From problem to progress</h2></div><p>A simple, trackable process for civic participation.</p></div>
 <div class="steps-grid"><article class="step-card"><span>01</span><h3>Report an issue</h3><p>Share the issue category, location, description and an optional photo.</p></article><article class="step-card"><span>02</span><h3>Track progress</h3><p>Get a unique complaint ID and view status updates as work progresses.</p></article><article class="step-card"><span>03</span><h3>See resolution</h3><p>Authorized staff can assign work, add progress notes and record resolutions.</p></article></div>
</section>
<section class="cta"><div><span class="eyebrow">MAKE YOUR AREA BETTER</span><h2>See something that needs attention?</h2><p>Submit a report and help bring local issues into focus.</p></div><a class="btn btn-white" href="<?= is_logged_in()?'report.php':'register.php' ?>">Get started →</a></section>
<?php require __DIR__.'/includes/footer.php'; ?>
