<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function sanitizeTool(array $input): array
{
    $tool = [
        'name' => trim((string)($input['name'] ?? '')),
        'developer_name' => trim((string)($input['developer_name'] ?? '')),
        'subtitle' => trim((string)($input['subtitle'] ?? '')),
        'category' => trim((string)($input['category'] ?? '')),
        'version' => trim((string)($input['version'] ?? '')),
        'version_date' => trim((string)($input['version_date'] ?? '')),
        'description' => trim((string)($input['description'] ?? '')),
        'iconURL' => trim((string)($input['iconURL'] ?? '')),
        'downloadURL' => trim((string)($input['downloadURL'] ?? '')),
        'price' => is_numeric($input['price'] ?? null) ? (float)$input['price'] : 0.0,
        'tool_type' => strtolower(trim((string)($input['tool_type'] ?? 'free'))) === 'paid' ? 'paid' : 'free',
        'screenshots' => normalizeJsonArrayField($input['screenshots'] ?? []),
        'icon_asset' => trim((string)($input['icon_asset'] ?? '')),
        'screenshot_assets' => normalizeJsonArrayField($input['screenshot_assets'] ?? []),
    ];

    if ($tool['category'] === '') {
        $tool['category'] = 'Other';
    }

    return $tool;
}

function rowToTool(array $row): array
{
    return [
        '_id' => (int)$row['id'],
        'name' => (string)$row['name'],
        'developer_name' => (string)$row['developer_name'],
        'subtitle' => (string)($row['subtitle'] ?? ''),
        'category' => (string)$row['category'],
        'version' => (string)$row['version'],
        'version_date' => (string)$row['version_date'],
        'description' => (string)($row['description'] ?? ''),
        'iconURL' => (string)$row['iconURL'],
        'downloadURL' => (string)$row['downloadURL'],
        'price' => (float)$row['price'],
        'tool_type' => (string)$row['tool_type'],
        'screenshots' => normalizeJsonArrayField($row['screenshots'] ?? []),
        'icon_asset' => (string)$row['icon_asset'],
        'screenshot_assets' => normalizeJsonArrayField($row['screenshot_assets'] ?? []),
    ];
}

try {
    $pdo = getDb();
} catch (Throwable $e) {
    respond(500, ['ok' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM ipa ORDER BY id ASC');
    $rows = $stmt->fetchAll();
    $tools = array_map('rowToTool', $rows);
    respond(200, ['ok' => true, 'tools' => $tools]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Method not allowed']);
}

$rawBody = file_get_contents('php://input');
$payload = json_decode((string)$rawBody, true);
if (!is_array($payload)) {
    respond(400, ['ok' => false, 'message' => 'Invalid JSON body']);
}

$action = (string)($payload['action'] ?? '');

if ($action === 'create') {
    $tool = sanitizeTool((array)($payload['tool'] ?? []));
    if ($tool['name'] === '') {
        respond(422, ['ok' => false, 'message' => 'Tool name is required']);
    }

    $stmt = $pdo->prepare('INSERT INTO ipa (name, developer_name, subtitle, category, version, version_date, description, iconURL, downloadURL, price, tool_type, screenshots, icon_asset, screenshot_assets) VALUES (:name, :developer_name, :subtitle, :category, :version, :version_date, :description, :iconURL, :downloadURL, :price, :tool_type, :screenshots, :icon_asset, :screenshot_assets)');
    $stmt->execute([
        ':name' => $tool['name'],
        ':developer_name' => $tool['developer_name'],
        ':subtitle' => $tool['subtitle'],
        ':category' => $tool['category'],
        ':version' => $tool['version'],
        ':version_date' => $tool['version_date'],
        ':description' => $tool['description'],
        ':iconURL' => $tool['iconURL'],
        ':downloadURL' => $tool['downloadURL'],
        ':price' => $tool['price'],
        ':tool_type' => $tool['tool_type'],
        ':screenshots' => json_encode($tool['screenshots'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':icon_asset' => $tool['icon_asset'],
        ':screenshot_assets' => json_encode($tool['screenshot_assets'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    respond(200, ['ok' => true, 'message' => 'Tool created']);
}

if ($action === 'update') {
    $id = isset($payload['id']) ? (int)$payload['id'] : -1;
    if ($id <= 0) {
        respond(404, ['ok' => false, 'message' => 'Tool not found']);
    }

    $tool = sanitizeTool((array)($payload['tool'] ?? []));
    if ($tool['name'] === '') {
        respond(422, ['ok' => false, 'message' => 'Tool name is required']);
    }

    $stmt = $pdo->prepare('UPDATE ipa SET name=:name, developer_name=:developer_name, subtitle=:subtitle, category=:category, version=:version, version_date=:version_date, description=:description, iconURL=:iconURL, downloadURL=:downloadURL, price=:price, tool_type=:tool_type, screenshots=:screenshots, icon_asset=:icon_asset, screenshot_assets=:screenshot_assets WHERE id=:id');
    $stmt->execute([
        ':id' => $id,
        ':name' => $tool['name'],
        ':developer_name' => $tool['developer_name'],
        ':subtitle' => $tool['subtitle'],
        ':category' => $tool['category'],
        ':version' => $tool['version'],
        ':version_date' => $tool['version_date'],
        ':description' => $tool['description'],
        ':iconURL' => $tool['iconURL'],
        ':downloadURL' => $tool['downloadURL'],
        ':price' => $tool['price'],
        ':tool_type' => $tool['tool_type'],
        ':screenshots' => json_encode($tool['screenshots'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':icon_asset' => $tool['icon_asset'],
        ':screenshot_assets' => json_encode($tool['screenshot_assets'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    if ($stmt->rowCount() === 0) {
        respond(404, ['ok' => false, 'message' => 'Tool not found']);
    }

    respond(200, ['ok' => true, 'message' => 'Tool updated']);
}

if ($action === 'delete') {
    $id = isset($payload['id']) ? (int)$payload['id'] : -1;
    if ($id <= 0) {
        respond(404, ['ok' => false, 'message' => 'Tool not found']);
    }

    $stmt = $pdo->prepare('DELETE FROM ipa WHERE id = :id');
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        respond(404, ['ok' => false, 'message' => 'Tool not found']);
    }

    respond(200, ['ok' => true, 'message' => 'Tool deleted']);
}

respond(400, ['ok' => false, 'message' => 'Unknown action']);
