<?php

declare(strict_types=1);

$localConfig = __DIR__ . '/database.local.php';

if (!is_file($localConfig)) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Configuration required</title></head><body style="font-family:system-ui,sans-serif;max-width:36rem;margin:3rem auto;padding:0 1rem">';
    echo '<h1>Configuration required</h1>';
    echo '<p>Copy <code>config/database.example.php</code> to <code>config/database.local.php</code> and set your MySQL credentials.</p>';
    echo '</body></html>';
    exit;
}

$dbConfig = require $localConfig;
if (!is_array($dbConfig)) {
    http_response_code(500);
    exit('Invalid database.local.php — must return an array.');
}

$dbHost = (string) ($dbConfig['host'] ?? 'localhost');
$dbName = (string) ($dbConfig['name'] ?? '');
$dbUser = (string) ($dbConfig['user'] ?? '');
$dbPass = (string) ($dbConfig['pass'] ?? '');

if ($dbName === '' || $dbUser === '') {
    http_response_code(500);
    exit('Database name and user are required in config/database.local.php.');
}

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Check config/database.local.php.');
}

try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'apps'");
    if ($tableCheck->fetch() === false) {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Setup required</title></head><body style="font-family:system-ui,sans-serif;max-width:36rem;margin:3rem auto;padding:0 1rem;line-height:1.5">';
        echo '<h1>Database setup required</h1>';
        echo '<p>Import <strong>database/schema_tables_only.sql</strong> in phpMyAdmin, then create an admin user (see DEPLOY.md).</p>';
        echo '</body></html>';
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error.');
}
