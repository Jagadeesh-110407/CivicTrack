<?php

require_once dirname(__DIR__).'/config/config.php';
require_once dirname(__DIR__).'/includes/functions.php';

require_role(['admin', 'staff']);

// Validate complaint ID
$id = filter_var(
    $_GET['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($id === false || $id === null) {
    http_response_code(400);
    exit('Invalid complaint ID.');
}

$user = current_user();
$role = $user['role'];
$userId = (int)$user['id'];

// Fetch complaint
$s = $pdo->prepare("
    SELECT
        i.*,
        u.name AS reporter,
        c.name AS category_name
    FROM issues i
    JOIN users u ON u.id = i.user_id
    JOIN categories c ON c.id = i.category_id
    WHERE i.id = ?
");

$s->execute([$id]);
$issue = $s->fetch();

if (!$issue) {
    http_response_code(404);
    exit('Complaint not found.');
}

// Restrict staff to assigned municipalities
if ($role === 'staff') {

    $m = $pdo->prepare("
        SELECT 1
        FROM staff_memberships
        WHERE user_id = ?
        AND municipality_id = ?
        AND is_active = 1
    ");

    $m->execute([
        $userId,
        (int)$issue['municipality_id']
    ]);

    if (!$m->fetch()) {
        http_response_code(404);
        exit('Complaint not found.');
    }
}

$errors = [];

$allowed = [
    'pending',
    'under_review',
    'assigned',
    'in_progress',
    'resolved',
    'duplicate',
    'rejected',
    'reopened'
];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $actionInput = $_POST['action'] ?? 'update';
    $action = is_string($actionInput) ? $actionInput : '';

    // Admin-only deletion
    if ($action === 'delete') {

        if ($role !== 'admin') {
            http_response_code(403);
            exit('Access denied.');
        }

        try {

            $pdo->beginTransaction();

            $delete = $pdo->prepare(
                'DELETE FROM issues WHERE id = ?'
            );

            $delete->execute([$id]);

            log_audit(
                $pdo,
                $userId,
                'DELETE',
                'issue',
                $id
            );

            $pdo->commit();

            set_flash('Complaint deleted.');
            redirect('index.php');

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $errors[] = 'Unable to delete complaint.';
        }
    }

    // Validate status
    $statusInput = $_POST['status'] ?? '';
    $status = is_string($statusInput)
        ? $statusInput
        : '';

    if (!in_array($status, $allowed, true)) {
        $errors[] = 'Select a valid status.';
    }

    // Validate progress note
    $noteInput = $_POST['note'] ?? '';

    if (!is_string($noteInput)) {
        $errors[] = 'Invalid progress note.';
        $note = '';
    } else {
        $note = trim($noteInput);

        if (strlen($note) > 4000) {
            $errors[] = 'Progress note is too long.';
        }
    }

    // Preserve existing assignment
    $assignee = $issue['assigned_to'];

    // Only admins can change assignments
    if ($role === 'admin') {

        $assigneeInput = $_POST['assignee'] ?? '';

        if (!is_string($assigneeInput)) {

            $errors[] = 'Invalid staff assignment.';

        } elseif ($assigneeInput === '') {

            $assignee = null;

        } else {

            $assigneeInput = filter_var(
                $assigneeInput,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($assigneeInput === false) {

                $errors[] = 'Invalid staff assignment.';

            } else {

                $check = $pdo->prepare("
                    SELECT u.id
                    FROM users u
                    JOIN staff_memberships sm
                        ON sm.user_id = u.id
                    WHERE u.id = ?
                    AND u.role = 'staff'
                    AND u.is_active = 1
                    AND sm.municipality_id = ?
                    AND sm.is_active = 1
                ");

                $check->execute([
                    $assigneeInput,
                    (int)$issue['municipality_id']
                ]);

                if (!$check->fetch()) {

                    $errors[] =
                        'Selected staff member is not authorized for this municipality.';

                } else {

                    $assignee = (int)$assigneeInput;
                }
            }
        }
    }

    // Update complaint
    if (!$errors && $action === 'update') {

        try {

            $pdo->beginTransaction();

            $update = $pdo->prepare("
                UPDATE issues
                SET
                    status = ?,
                    assigned_to = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $update->execute([
                $status,
                $assignee,
                $id
            ]);

            // Save complaint history
            $history = $pdo->prepare("
                INSERT INTO issue_updates
                (
                    issue_id,
                    user_id,
                    old_status,
                    new_status,
                    note
                )
                VALUES (?, ?, ?, ?, ?)
            ");

            $history->execute([
                $id,
                $userId,
                $issue['status'],
                $status,
                $note ?: 'Status updated by authorized staff.'
            ]);

            // Audit logging
            log_audit(
                $pdo,
                $userId,
                'UPDATE_STATUS',
                'issue',
                $id
            );

            $pdo->commit();

            set_flash('Complaint updated successfully.');

            redirect('admin/issue.php?id='.$id);

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $errors[] =
                'Unable to update complaint. Please try again.';
        }
    }

    if (!$errors && $action !== 'update' && $action !== 'delete') {
        $errors[] = 'Invalid action.';
    }
}

// Load authorized staff for municipality
$staffQuery = $pdo->prepare("
    SELECT DISTINCT u.id, u.name
    FROM users u
    JOIN staff_memberships sm
        ON sm.user_id = u.id
    WHERE u.role = 'staff'
    AND u.is_active = 1
    AND sm.is_active = 1
    AND sm.municipality_id = ?
    ORDER BY u.name
");

$staffQuery->execute([
    (int)$issue['municipality_id']
]);

$staff = $staffQuery->fetchAll();

// Load complaint images
$imageQuery = $pdo->prepare("
    SELECT *
    FROM issue_images
    WHERE issue_id = ?
");

$imageQuery->execute([$id]);

$images = $imageQuery->fetchAll();

require dirname(__DIR__).'/includes/header.php';

?>

<div class="page-heading">

    <div>

        <span class="eyebrow">COMPLAINT MANAGEMENT</span>

        <h1><?=e($issue['complaint_code'])?></h1>

        <p class="muted"><?=e($issue['title'])?></p>

    </div>

    <a href="index.php">← Back to dashboard</a>

</div>

<div class="detail-grid">

    <section class="panel">

        <span class="status status-<?=e($issue['status'])?>">
            <?=e(status_label($issue['status']))?>
        </span>

        <h2>Report details</h2>

        <p><?=nl2br(e($issue['description']))?></p>

        <p>
            <strong>Reporter:</strong>
            <?=e($issue['reporter'])?>
        </p>

        <p>
            <strong>Location:</strong>
            <?=e($issue['location_text'])?>
        </p>

        <?php foreach ($images as $im): ?>

            <img
                class="issue-photo"
                src="../uploads/<?=e($im['file_name'])?>"
                alt="Issue photo"
            >

        <?php endforeach; ?>

    </section>

    <section class="panel">

        <h2>Update workflow</h2>

        <?php foreach ($errors as $er): ?>

            <div class="alert alert-error">
                <?=e($er)?>
            </div>

        <?php endforeach; ?>

        <form method="post" class="form-stack">

            <?=csrf_field()?>

            <input
                type="hidden"
                name="action"
                value="update"
            >

            <label>
                Status

                <select name="status" required>

                    <?php foreach ($allowed as $st): ?>

                        <option
                            value="<?=$st?>"
                            <?=$issue['status'] === $st ? 'selected' : ''?>
                        >
                            <?=e(status_label($st))?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </label>

            <?php if ($role === 'admin'): ?>

                <label>
                    Assign staff

                    <select name="assignee">

                        <option value="">Unassigned</option>

                        <?php foreach ($staff as $st): ?>

                            <option
                                value="<?=(int)$st['id']?>"
                                <?=((int)$issue['assigned_to'] === (int)$st['id']) ? 'selected' : ''?>
                            >
                                <?=e($st['name'])?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </label>

            <?php else: ?>

                <p class="muted">
                    Only administrators can change staff assignments.
                </p>

            <?php endif; ?>

            <label>
                Progress note

                <textarea
                    name="note"
                    rows="4"
                    maxlength="4000"
                    placeholder="Describe the review, assignment or resolution update."
                ></textarea>

            </label>

            <button class="btn btn-block">
                Save update
            </button>

        </form>

        <?php if ($role === 'admin'): ?>

            <form
                method="post"
                onsubmit="return confirm('Permanently delete this complaint?')"
                class="danger-form"
            >

                <?=csrf_field()?>

                <input
                    type="hidden"
                    name="action"
                    value="delete"
                >

                <button class="btn btn-danger btn-block">
                    Delete complaint
                </button>

            </form>

        <?php endif; ?>

    </section>

</div>

<?php require dirname(__DIR__).'/includes/footer.php'; ?>