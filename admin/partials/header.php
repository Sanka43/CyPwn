<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/config/site.php';
$flash = flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($pageTitle ?? 'Admin') ?> — <?= e(page_title()) ?></title>
    <?= favicon_tags() ?>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="admin-header-inner">
            <a href="index.php" class="admin-brand"><?= e($config['brand_short'] ?? $config['site_name']) ?> Admin</a>
            <nav class="admin-nav">
                <a href="index.php">Apps</a>
                <a href="app_add.php">Add App</a>
                <a href="../index.php" target="_blank">View Site</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    <main class="admin-main">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
