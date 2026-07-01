<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/auth.php';
require dirname(__DIR__) . '/includes/functions.php';
require dirname(__DIR__) . '/config/database.php';

require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

if (!verify_csrf($input['csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid security token']);
    exit;
}

$storeType = strtolower(trim((string) ($input['store_type'] ?? '')));
$category = trim((string) ($input['category'] ?? ''));
$ids = $input['ids'] ?? [];

if (!is_array($ids)) {
    $ids = [];
}
$ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));

if (!in_array($storeType, ['ipa', 'trollstore'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid store type']);
    exit;
}

if ($ids === []) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'App order is required']);
    exit;
}

if (!save_apps_sort_order($pdo, $storeType, $ids, $category !== '' ? $category : null)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Could not save order']);
    exit;
}

echo json_encode(['ok' => true]);
