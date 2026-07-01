<?php

declare(strict_types=1);

/**
 * Run once via CLI (recommended on live hosting):
 *   php database/seed_admin.php
 * Default: username admin / password admin123
 * DELETE this file after creating the admin user.
 * Web access is blocked by database/.htaccess on production.
 */

require dirname(__DIR__) . '/config/database.php';

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT id FROM admin_users WHERE username = ?');
$stmt->execute([$username]);

if ($stmt->fetch()) {
    echo "Admin user '{$username}' already exists.\n";
    exit;
}

$insert = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
$insert->execute([$username, $hash]);

echo "Admin created.\n";
echo "Username: {$username}\n";
echo "Password: {$password}\n";
echo "Login at: /admin/login.php\n";
echo "Change password after first login.\n";
