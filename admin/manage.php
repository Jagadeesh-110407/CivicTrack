<?php

require_once dirname(__DIR__).'/config/config.php';
require_once dirname(__DIR__).'/includes/functions.php';

require_role(['admin']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $actionInput = $_POST['action'] ?? '';
    $action = is_string($actionInput) ? $actionInput : '';

    $uid = filter_var(
        $_POST['user_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $currentId = (int)current_user()['id'];

    if (!in_array($action, ['user_role', 'user_active'], true)) {

        $errors[] = 'Invalid administrative action.';

    } elseif ($uid === false || $uid === null) {

        $errors[] = 'Invalid user ID.';

    } elseif ($uid === $currentId) {

        $errors[] = 'You cannot modify your own account.';

    } else {

        try {

            $pdo->beginTransaction();

            // Lock active administrator records.
            // This helps prevent concurrent removal
            // of the last active administrator.

            $adminLock = $pdo->query("
                SELECT id
                FROM users
                WHERE role = 'admin'
                AND is_active = 1
                FOR UPDATE
            ");

            $activeAdmins = $adminLock->fetchAll();

            // Lock target user.
            $userQuery = $pdo->prepare("
                SELECT id, role, is_active
                FROM users
                WHERE id = ?
                FOR UPDATE
            ");

            $userQuery->execute([$uid]);

            $target = $userQuery->fetch();

            if (!$target) {

                throw new RuntimeException('User not found.');

            }

            $targetIsActiveAdmin =
                $target['role'] === 'admin' &&
                (int)$target['is_active'] === 1;

            if ($action === 'user_role') {

                $roleInput = $_POST['role'] ?? '';

                $newRole = is_string($roleInput)
                    ? $roleInput
                    : '';

                $allowedRoles = [
                    'citizen',
                    'staff',
                    'admin'
                ];

                if (!in_array($newRole, $allowedRoles, true)) {

                    throw new RuntimeException('Invalid user role.');

                }

                // Protect the last active administrator.
                if (
                    $targetIsActiveAdmin &&
                    $newRole !== 'admin' &&
                    count($activeAdmins) <= 1
                ) {

                    throw new RuntimeException(
                        'Cannot remove the last active administrator.'
                    );

                }

                $update = $pdo->prepare("
                    UPDATE users
                    SET role = ?
                    WHERE id = ?
                ");

                $update->execute([$newRole, $uid]);

                log_audit(
                    $pdo,
                    $currentId,
                    'CHANGE_ROLE',
                    'user',
                    $uid
                );

                $message = 'User role updated successfully.';

            } elseif ($action === 'user_active') {

                $newStatus = (int)$target['is_active'] === 1
                    ? 0
                    : 1;

                // Protect the last active administrator.
                if (
                    $targetIsActiveAdmin &&
                    $newStatus === 0 &&
                    count($activeAdmins) <= 1
                ) {

                    throw new RuntimeException(
                        'Cannot deactivate the last active administrator.'
                    );

                }

                $update = $pdo->prepare("
                    UPDATE users
                    SET is_active = ?
                    WHERE id = ?
                ");

                $update->execute([$newStatus, $uid]);

                log_audit(
                    $pdo,
                    $currentId,
                    'TOGGLE_USER',
                    'user',
                    $uid
                );

                $message = $newStatus
                    ? 'User account activated.'
                    : 'User account deactivated.';
            }

            $pdo->commit();

            set_flash($message);

           redirect('admin/manage.php');

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());

            $errors[] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Unable to update user account. Please try again.';
        }
    }
}

// Load users
$users = $pdo->query("
    SELECT id, name, email, role, is_active, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 300
")->fetchAll();

// Load districts
$districts = $pdo->query("
    SELECT *
    FROM districts
    ORDER BY name
")->fetchAll();

// Load categories
$cats = $pdo->query("
    SELECT *
    FROM categories
    ORDER BY name
")->fetchAll();

$pageTitle = 'Manage users';

require dirname(__DIR__).'/includes/header.php';

?>

<div class="page-heading">

    <div>

        <span class="eyebrow">STATE ADMINISTRATION</span>

        <h1>Manage users & reference data</h1>

        <p class="muted">
            Manage prototype account roles and inspect configured reference data.
        </p>

    </div>

</div>

<?php foreach ($errors as $error): ?>

    <div class="alert alert-error">
        <?=e($error)?>
    </div>

<?php endforeach; ?>


<section class="panel">

    <h2>User accounts</h2>

    <div class="table-wrap">

        <table>

            <thead>

                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Active</th>
                    <th>Change role</th>
                    <th>Account</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($users as $u): ?>

                    <tr>

                        <td><?=e($u['name'])?></td>

                        <td><?=e($u['email'])?></td>

                        <td><?=e($u['role'])?></td>

                        <td>
                            <?=((int)$u['is_active'] === 1) ? 'Yes' : 'No'?>
                        </td>

                        <td>

                            <form method="post" class="inline-admin">

                                <?=csrf_field()?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="user_role"
                                >

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?=(int)$u['id']?>"
                                >

                                <select name="role">

                                    <?php foreach (
                                        ['citizen', 'staff', 'admin'] as $r
                                    ): ?>

                                        <option
                                            value="<?=e($r)?>"
                                            <?=$u['role'] === $r ? 'selected' : ''?>
                                        >
                                            <?=e($r)?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                                <button class="btn btn-tiny">
                                    Save
                                </button>

                            </form>

                        </td>

                        <td>

                            <form method="post">

                                <?=csrf_field()?>

                                <input
                                    type="hidden"
                                    name="action"
                                    value="user_active"
                                >

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?=(int)$u['id']?>"
                                >

                                <button class="btn btn-tiny btn-outline">

                                    <?=(int)$u['is_active'] === 1
                                        ? 'Deactivate'
                                        : 'Activate'?>

                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</section>


<div class="detail-grid">

    <section class="panel">

        <h2>Configured districts</h2>

        <?php foreach ($districts as $d): ?>

            <p><?=e($d['name'])?></p>

        <?php endforeach; ?>

    </section>


    <section class="panel">

        <h2>Issue categories</h2>

        <?php foreach ($cats as $c): ?>

            <p><?=e($c['name'])?></p>

        <?php endforeach; ?>

    </section>

</div>


<div class="alert alert-warning">

    For the prototype, assign staff roles here.
    Municipality staff memberships and area hierarchy
    are managed directly in the database seed/admin workflow
    described in README.

</div>


<?php require dirname(__DIR__).'/includes/footer.php'; ?>