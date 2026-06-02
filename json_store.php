<?php
declare(strict_types=1);

const CYPWN_JSON_COLLECTIONS = [
    'premium' => 'deta/premium_ipas.json',
    'trollstore' => 'deta/trollstore_ipas.json',
];

function normalizeCollection(string $name): string
{
    $key = strtolower(trim($name));
    if (!isset(CYPWN_JSON_COLLECTIONS[$key])) {
        throw new InvalidArgumentException('Unknown collection. Use premium or trollstore.');
    }

    return $key;
}

function collectionJsonPath(string $collection): string
{
    return __DIR__ . '/' . CYPWN_JSON_COLLECTIONS[normalizeCollection($collection)];
}

function normalizeJsonArrayField($value): array
{
    if (is_array($value)) {
        return array_values(array_filter(
            array_map(static fn($item) => trim((string)$item), $value),
            static fn($item) => $item !== ''
        ));
    }

    if (is_string($value) && trim($value) !== '') {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_filter(
                array_map(static fn($item) => trim((string)$item), $decoded),
                static fn($item) => $item !== ''
            ));
        }
    }

    return [];
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
        'detailURL' => trim((string)($input['detailURL'] ?? '')),
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

function toolWithId(array $tool, int $id): array
{
    $tool['_id'] = $id;
    return $tool;
}

/** @return array<int, array<string, mixed>> */
function loadTools(string $collection): array
{
    $path = collectionJsonPath($collection);
    if (!is_readable($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        return [];
    }

    $tools = [];
    $id = 1;
    foreach ($data as $item) {
        if (!is_array($item)) {
            continue;
        }
        $tools[] = toolWithId($item, $id);
        $id++;
    }

    return $tools;
}

/** @param array<int, array<string, mixed>> $tools */
function saveTools(string $collection, array $tools): void
{
    $path = collectionJsonPath($collection);
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create directory: ' . $dir);
    }

    $payload = [];
    foreach ($tools as $tool) {
        if (!is_array($tool)) {
            continue;
        }
        unset($tool['_id']);
        $payload[] = $tool;
    }

    $json = json_encode(
        $payload,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    if ($json === false) {
        throw new RuntimeException('Failed to encode JSON for ' . $collection);
    }

    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Failed to write temp file: ' . $tmp);
    }
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Failed to save JSON file: ' . $path);
    }
}

function findToolIndexById(array $tools, int $id): int
{
    foreach ($tools as $index => $tool) {
        if ((int)($tool['_id'] ?? 0) === $id) {
            return (int)$index;
        }
    }

    return -1;
}

function createTool(string $collection, array $tool): void
{
    $tools = loadTools($collection);
    $tools[] = toolWithId($tool, count($tools) + 1);
    saveTools($collection, $tools);
}

function updateTool(string $collection, int $id, array $tool): bool
{
    $tools = loadTools($collection);
    $index = findToolIndexById($tools, $id);
    if ($index < 0) {
        return false;
    }

    $tools[$index] = toolWithId($tool, $id);
    saveTools($collection, $tools);
    return true;
}

function deleteTool(string $collection, int $id): bool
{
    $tools = loadTools($collection);
    $index = findToolIndexById($tools, $id);
    if ($index < 0) {
        return false;
    }

    array_splice($tools, $index, 1);
    $reindexed = [];
    $nextId = 1;
    foreach ($tools as $tool) {
        $reindexed[] = toolWithId($tool, $nextId);
        $nextId++;
    }

    saveTools($collection, $reindexed);
    return true;
}

function collectionCounts(): array
{
    $counts = [];
    foreach (array_keys(CYPWN_JSON_COLLECTIONS) as $name) {
        $counts[$name] = count(loadTools($name));
    }

    return $counts;
}
