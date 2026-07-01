<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/functions.php';
require dirname(__DIR__) . '/config/database.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Invalid security token.');
    header('Location: index.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$app = get_app_by_id($pdo, $id);

if ($app) {
    delete_app_files($app);
    $stmt = $pdo->prepare('DELETE FROM apps WHERE id = ?');
    $stmt->execute([$id]);
    flash_set('success', 'App deleted.');
} else {
    flash_set('error', 'App not found.');
}

header('Location: index.php');
exit;
