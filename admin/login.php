<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/functions.php';

if (admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$config = require dirname(__DIR__) . '/config/site.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require dirname(__DIR__) . '/config/database.php';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (admin_login($pdo, $username, $password)) {
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login — <?= e(page_title()) ?></title>
    <?= favicon_tags() ?>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-login-box">
        <h1>Admin Login</h1>
        <p class="muted"><?= e($config['brand_short'] ?? $config['site_name']) ?></p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autocomplete="username">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
            <button type="submit" class="btn btn-primary">Sign In</button>
        </form>
        <p class="back-link"><a href="../store.php">← Back to store</a></p>
    </div>
</body>
</html>
