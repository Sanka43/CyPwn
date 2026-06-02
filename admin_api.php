<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/json_store.php';

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function collectionFromRequest(array $source = []): string
{
    $raw = $source['collection'] ?? $_GET['collection'] ?? 'premium';
    try {
        return normalizeCollection((string)$raw);
    } catch (InvalidArgumentException $e) {
        respond(400, ['ok' => false, 'message' => $e->getMessage()]);
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $collection = collectionFromRequest();
        $tools = loadTools($collection);
        respond(200, [
            'ok' => true,
            'collection' => $collection,
            'tools' => $tools,
            'counts' => collectionCounts(),
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(405, ['ok' => false, 'message' => 'Method not allowed']);
    }

    $rawBody = file_get_contents('php://input');
    $payload = json_decode((string)$rawBody, true);
    if (!is_array($payload)) {
        respond(400, ['ok' => false, 'message' => 'Invalid JSON body']);
    }

    $collection = collectionFromRequest($payload);
    $action = (string)($payload['action'] ?? '');

    if ($action === 'create') {
        $tool = sanitizeTool((array)($payload['tool'] ?? []));
        if ($tool['name'] === '') {
            respond(422, ['ok' => false, 'message' => 'Tool name is required']);
        }

        createTool($collection, $tool);
        respond(200, ['ok' => true, 'message' => 'Tool created', 'collection' => $collection]);
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

        if (!updateTool($collection, $id, $tool)) {
            respond(404, ['ok' => false, 'message' => 'Tool not found']);
        }

        respond(200, ['ok' => true, 'message' => 'Tool updated', 'collection' => $collection]);
    }

    if ($action === 'delete') {
        $id = isset($payload['id']) ? (int)$payload['id'] : -1;
        if ($id <= 0) {
            respond(404, ['ok' => false, 'message' => 'Tool not found']);
        }

        if (!deleteTool($collection, $id)) {
            respond(404, ['ok' => false, 'message' => 'Tool not found']);
        }

        respond(200, ['ok' => true, 'message' => 'Tool deleted', 'collection' => $collection]);
    }

    respond(400, ['ok' => false, 'message' => 'Unknown action']);
} catch (Throwable $e) {
    respond(500, ['ok' => false, 'message' => $e->getMessage()]);
}
