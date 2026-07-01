<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$app = get_app_by_id($pdo, $id);

if (!$app) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

$screenshots = decode_screenshots($app['screenshots'] ?? null);

echo json_encode([
    'id' => (int) $app['id'],
    'store_type' => $app['store_type'],
    'name' => $app['name'],
    'icon' => $app['icon'],
    'developer_name' => $app['developer_name'],
    'subtitle' => $app['subtitle'],
    'category' => $app['category'],
    'version' => $app['version'],
    'version_date' => $app['version_date'],
    'description' => $app['description'],
    'download_url' => $app['download_url'],
    'screenshots' => $screenshots,
    'size' => display_app_size($app),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
