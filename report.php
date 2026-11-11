<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/includes/functions.php';

require_login();

$errors = [];

$cats = $pdo->query("
    SELECT id, name
    FROM categories
    WHERE is_active = 1
    ORDER BY name
")->fetchAll();

$districts = $pdo->query("
    SELECT id, name
    FROM districts
    WHERE is_active = 1
    ORDER BY name
")->fetchAll();

$municipalities = $pdo->query("
    SELECT id, district_id, name, type
    FROM municipalities
    WHERE is_active = 1
    ORDER BY name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    // Safely read text inputs.
    $getInput = static function ($key): string {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    };

    $title = $getInput('title');
    $desc = $getInput('description');
    $ward = $getInput('ward_name');
    $location = $getInput('location_text');

    $cat = filter_var(
        $_POST['category_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $district = filter_var(
        $_POST['district_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $municipality = filter_var(
        $_POST['municipality_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    // Title validation.
    if (mb_strlen($title) < 5 || mb_strlen($title) > 180) {
        $errors[] = 'Title must contain 5 to 180 characters.';
    }

    // Description validation.
    if (mb_strlen($desc) < 20 || mb_strlen($desc) > 5000) {
        $errors[] = 'Description must contain 20 to 5000 characters.';
    }

    // Ward validation.
    if ($ward === '') {
        $errors[] = 'Ward number or area is required.';
    } elseif (mb_strlen($ward) > 100) {
        $errors[] = 'Ward number or area must not exceed 100 characters.';
    }

    // Location validation.
    if ($location === '') {
        $errors[] = 'Enter the issue location.';
    } elseif (mb_strlen($location) > 255) {
        $errors[] = 'Location must not exceed 255 characters.';
    }

    // Category validation.
    $validCategoryIds = array_map(
        'intval',
        array_column($cats, 'id')
    );

    if (
        $cat === false ||
        !in_array($cat, $validCategoryIds, true)
    ) {
        $errors[] = 'Choose a valid issue category.';
    }

    // District validation.
    $validDistrictIds = array_map(
        'intval',
        array_column($districts, 'id')
    );

    if (
        $district === false ||
        !in_array($district, $validDistrictIds, true)
    ) {
        $errors[] = 'Choose a valid district.';
    }

    // Municipality must belong to selected district.
    if ($district !== false && $municipality !== false) {

        $checkMunicipality = $pdo->prepare("
            SELECT id
            FROM municipalities
            WHERE id = ?
            AND district_id = ?
            AND is_active = 1
        ");

        $checkMunicipality->execute([
            $municipality,
            $district
        ]);

        if (!$checkMunicipality->fetch()) {
            $errors[] = 'Choose a valid municipality for the selected district.';
        }

    } else {
        $errors[] = 'Choose a valid municipality.';
    }

    // Save complaint only when validation succeeds.
    if (!$errors) {

        $image = null;

        try {

            $image = upload_image($_FILES['photo'] ?? []);

            $pdo->beginTransaction();

            $s = $pdo->prepare("
                INSERT INTO issues (
                    complaint_code,
                    user_id,
                    category_id,
                    district_id,
                    municipality_id,
                    ward_name,
                    title,
                    description,
                    location_text,
                    status
                )
                VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            $s->execute([
                (int)current_user()['id'],
                $cat,
                $district,
                $municipality,
                $ward,
                $title,
                $desc,
                $location
            ]);

            $id = (int)$pdo->lastInsertId();

            $code = complaint_code($id);

            $pdo->prepare("
                UPDATE issues
                SET complaint_code = ?
                WHERE id = ?
            ")->execute([$code, $id]);

            // Save uploaded image information.
            if ($image !== null) {

                $originalName = basename(
                    (string)($_FILES['photo']['name'] ?? 'image')
                );

                $mime = mime_content_type(UPLOAD_DIR . $image);

                $pdo->prepare("
                    INSERT INTO issue_images (
                        issue_id,
                        file_name,
                        original_name,
                        mime_type
                    )
                    VALUES (?, ?, ?, ?)
                ")->execute([
                    $id,
                    $image,
                    $originalName,
                    $mime
                ]);
            }

            // Initial complaint timeline entry.
            $pdo->prepare("
                INSERT INTO issue_updates (
                    issue_id,
                    user_id,
                    old_status,
                    new_status,
                    note
                )
                VALUES (?, ?, NULL, 'pending', ?)
            ")->execute([
                $id,
                (int)current_user()['id'],
                'Report submitted by citizen.'
            ]);

            // Audit log.
            log_audit(
                $pdo,
                (int)current_user()['id'],
                'CREATE',
                'issue',
                $id
            );

            $pdo->commit();

            set_flash("Your report was submitted. Complaint ID: $code");

            redirect('track.php?id=' . $id);

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if (
                $image !== null &&
                is_file(UPLOAD_DIR . $image)
            ) {
                unlink(UPLOAD_DIR . $image);
            }

            $errors[] = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'Could not save report. Please try again.';
        }
    }
}

$pageTitle = 'Report an issue';

require __DIR__.'/includes/header.php';
?>

<div class="form-layout">

    <div class="form-intro">

        <span class="eyebrow">RAISE YOUR VOICE</span>

        <h1>Report a civic issue</h1>

        <p>
            Share clear details so the appropriate team can review
            and coordinate a response.
        </p>

        <div class="info-box">
            <strong>Before you submit</strong>

            <ul>
                <li>Choose the closest issue category.</li>
                <li>Describe what happened and where.</li>
                <li>Enter your ward number or area.</li>
                <li>Do not include private or sensitive information in photos.</li>
            </ul>
        </div>

        <p class="small-note">
            Reports are handled within this academic prototype
            using sample administrative data.
        </p>

    </div>

    <section class="panel form-panel">

        <h2>Issue details</h2>

        <?php foreach ($errors as $er): ?>
            <div class="alert alert-error">
                <?=e($er)?>
            </div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data" class="form-stack">

            <?=csrf_field()?>

            <label>
                Complaint title

                <input
                    name="title"
                    required
                    minlength="5"
                    maxlength="180"
                    placeholder="e.g. Large pothole near community park"
                    value="<?=e($_POST['title'] ?? '')?>"
                >
            </label>

            <label>
                Issue category

                <select name="category_id" required>

                    <option value="">Select a category</option>

                    <?php foreach ($cats as $c): ?>

                        <option
                            value="<?=$c['id']?>"
                            <?=((int)($_POST['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : ''?>
                        >
                            <?=e($c['name'])?>
                        </option>

                    <?php endforeach; ?>

                </select>
            </label>

            <label>
                Description

                <textarea
                    name="description"
                    rows="5"
                    minlength="20"
                    maxlength="5000"
                    required
                    placeholder="Explain the issue, its impact and any useful details."
                ><?=e($_POST['description'] ?? '')?></textarea>
            </label>

            <div class="form-row">

                <label>
                    District

                    <select name="district_id" required>

                        <option value="">Select District</option>

                        <?php foreach ($districts as $d): ?>

                            <option
                                value="<?=$d['id']?>"
                                <?=((int)($_POST['district_id'] ?? 0) === (int)$d['id']) ? 'selected' : ''?>
                            >
                                <?=e($d['name'])?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </label>

                <label>
                    Municipality / Municipal Corporation

                    <select name="municipality_id" required>

                        <option value="">Select Municipality</option>

                        <?php foreach ($municipalities as $m): ?>

                            <option
                                value="<?= (int)$m['id'] ?>"
                                data-district-id="<?= (int)$m['district_id'] ?>"
                                <?=((int)($_POST['municipality_id'] ?? 0) === (int)$m['id']) ? 'selected' : ''?>
                            >
                                <?=e($m['name'])?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </label>

                <label>
                    Ward / Area <span>*</span>

                    <input
                        name="ward_name"
                        maxlength="100"
                        required
                        value="<?=e($_POST['ward_name'] ?? '')?>"
                        placeholder="e.g. Ward 04"
                    >
                </label>

            </div>

            <label>
                Exact location / landmark

                <input
                    name="location_text"
                    required
                    maxlength="255"
                    placeholder="Street, landmark or nearby address"
                    value="<?=e($_POST['location_text'] ?? '')?>"
                >
            </label>

            <label>
                Photo (optional)

                <input
                    type="file"
                    name="photo"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <small>
                    JPG, PNG or WEBP. Maximum 2 MB.
                </small>
            </label>

            <button class="btn btn-block">
                Submit report →
            </button>

        </form>

    </section>

</div>

<script>

const districtSelect = document.querySelector(
    'select[name="district_id"]'
);

const municipalitySelect = document.querySelector(
    'select[name="municipality_id"]'
);

function filterMunicipalities() {

    const selectedDistrict = districtSelect.value;

    Array.from(municipalitySelect.options).forEach(option => {

        if (option.value === "") {
            option.hidden = false;
            return;
        }

        option.hidden =
            option.dataset.districtId !== selectedDistrict;

    });

    if (
        municipalitySelect.selectedOptions.length &&
        municipalitySelect.selectedOptions[0].hidden
    ) {
        municipalitySelect.value = "";
    }
}

districtSelect.addEventListener(
    "change",
    filterMunicipalities
);

filterMunicipalities();

</script>

<?php require __DIR__.'/includes/footer.php'; ?>