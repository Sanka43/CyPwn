<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/functions.php';
require dirname(__DIR__) . '/config/database.php';
require __DIR__ . '/includes/app_handler.php';

require_admin();

$id = (int) ($_GET['id'] ?? 0);
$app = get_app_by_id($pdo, $id);
if (!$app) {
    flash_set('error', 'App not found.');
    header('Location: index.php');
    exit;
}

$pageTitle = 'Edit App';
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    } else {
        $hasIcon = !empty($_FILES['icon']['tmp_name']) && ($_FILES['icon']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
        $removingIcon = !empty($_POST['remove_icon']);
        $hasExistingIcon = !empty($app['icon']) && !$removingIcon;
        $errors = validate_app_post($_POST, true, $hasIcon || $hasExistingIcon);
        if (empty($errors)) {
            $result = save_app_from_post($pdo, $_POST, $_FILES, $app);
            if ($result['ok']) {
                flash_set('success', 'App updated successfully.');
                header('Location: index.php');
                exit;
            }
            $errors = $result['errors'] ?? ['general' => 'Could not update app.'];
        }
        $old = $_POST;
    }
}

$isEdit = true;
$categories = get_distinct_categories($pdo);

require __DIR__ . '/partials/header.php';
?>
<h1>Edit App</h1>
<?php require __DIR__ . '/partials/app_form.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
