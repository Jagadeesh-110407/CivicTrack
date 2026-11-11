
<?php
require_once dirname(__DIR__).'/config/config.php';
require_once dirname(__DIR__).'/includes/functions.php';

require_role(['admin','staff']);

$role = current_user()['role'];
$where = '';
$params = [];

// Restrict staff to their assigned municipalities
if ($role === 'staff') {
    $s = $pdo->prepare(
        'SELECT municipality_id
         FROM staff_memberships
         WHERE user_id = ? AND is_active = 1'
    );

    $s->execute([(int)current_user()['id']]);

    $mids = array_map(
        'intval',
        array_column($s->fetchAll(), 'municipality_id')
    );

    if (!$mids) {
        $where = ' WHERE 1=0';
    } else {
        $placeholders = implode(',', array_fill(0, count($mids), '?'));
        $where = " WHERE i.municipality_id IN ($placeholders)";
        $params = $mids;
    }
}

// Dashboard statistics (respect staff municipality access)
$statsSql = "
    SELECT
        COUNT(*) AS total,
        SUM(i.status = 'pending') AS pending,
        SUM(i.status = 'in_progress') AS progress,
        SUM(i.status = 'resolved') AS resolved
    FROM issues i
    $where
";

$statsQuery = $pdo->prepare($statsSql);
$statsQuery->execute($params);
$stats = $statsQuery->fetch();

// Fetch complaints with municipality information
$sql = "
    SELECT
        i.*,
        c.name AS category_name,
        u.name AS reporter,
        d.name AS district_name,
        m.name AS municipality_name
    FROM issues i
    JOIN categories c ON c.id = i.category_id
    JOIN users u ON u.id = i.user_id
    LEFT JOIN districts d ON d.id = i.district_id
    LEFT JOIN municipalities m ON m.id = i.municipality_id
    $where
    ORDER BY i.created_at DESC
    LIMIT 200
";

$q = $pdo->prepare($sql);
$q->execute($params);
$issues = $q->fetchAll();

$pageTitle = 'Administration';
require dirname(__DIR__).'/includes/header.php';
?>

<div class="page-heading">
    <div>
        <span class="eyebrow">CONTROL CENTRE</span>

        <h1>
            <?= $role === 'admin'
                ? 'State administration'
                : 'Municipality staff dashboard' ?>
        </h1>

        <p class="muted">
            Review reports, coordinate assignments and record progress.
        </p>
    </div>
</div>

<div class="stats-grid dashboard-stats">

    <div class="stat-card">
        <strong><?= (int)$stats['total'] ?></strong>
        <span>Total reports</span>
    </div>

    <div class="stat-card">
        <strong><?= (int)$stats['pending'] ?></strong>
        <span>Pending</span>
    </div>

    <div class="stat-card">
        <strong><?= (int)$stats['progress'] ?></strong>
        <span>In progress</span>
    </div>

    <div class="stat-card">
        <strong><?= (int)$stats['resolved'] ?></strong>
        <span>Resolved</span>
    </div>

</div>

<section class="panel">

    <div class="panel-heading">
        <div>
            <h2>Manage complaints</h2>
            <p class="muted">
                Open a report to assign it or update its status.
            </p>
        </div>

        <?php if ($role === 'admin'): ?>
            <a href="manage.php">Manage users & areas →</a>
        <?php endif; ?>
    </div>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Complaint ID</th>
                    <th>Title</th>
                    <th>Reporter</th>
                    <th>District</th>
                    <th>Municipality</th>
                    <th>Municipality ID</th>
                    <th>Ward</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($issues as $i): ?>

                    <tr>

                        <td class="mono">
                            <?= e($i['complaint_code']) ?>
                        </td>

                        <td>
                            <?= e($i['title']) ?>
                        </td>

                        <td>
                            <?= e($i['reporter']) ?>
                        </td>

                        <td>
                            <?= e($i['district_name'] ?? '—') ?>
                        </td>

                        <td>
                            <?= e($i['municipality_name'] ?? '—') ?>
                        </td>

                        <td>
                            <?= $i['municipality_id'] !== null
                                ? (int)$i['municipality_id']
                                : '—' ?>
                        </td>

                        <td>
                            <?= e($i['ward_name'] ?? '—') ?>
                        </td>

                        <td>
                            <span class="status status-<?= e($i['status']) ?>">
                                <?= e(status_label($i['status'])) ?>
                            </span>
                        </td>

                        <td>
                            <a href="issue.php?id=<?=(int)$i['id']?>">
                                Manage →
                            </a>
                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$issues): ?>
                    <tr>
                        <td colspan="9">No complaints found.</td>
                    </tr>
                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>

<?php require dirname(__DIR__).'/includes/footer.php'; ?>
