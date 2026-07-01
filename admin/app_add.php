<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/functions.php';
require dirname(__DIR__) . '/config/database.php';
require __DIR__ . '/includes/app_handler.php';

require_admin();

$pageTitle = 'Add App';
$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    } else {
        $hasIcon = !empty($_FILES['icon']['tmp_name']) && ($_FILES['icon']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
        $errors = validate_app_post($_POST, false, $hasIcon);
        if (empty($errors)) {
            $result = save_app_from_post($pdo, $_POST, $_FILES, null);
            if ($result['ok']) {
                flash_set('success', 'App created successfully.');
                header('Location: index.php');
                exit;
            }
            $errors = $result['errors'] ?? ['general' => 'Could not save app.'];
        }
        $old = $_POST;
    }
}

$app = null;
$isEdit = false;
$categories = get_distinct_categories($pdo);

require __DIR__ . '/partials/header.php';
?>
<h1>Add App</h1>
<?php require __DIR__ . '/partials/app_form.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
