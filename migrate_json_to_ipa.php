<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

$jsonPath = __DIR__ . '/deta/premium_ipas.json';
if (!file_exists($jsonPath)) {
    fwrite(STDERR, "JSON file not found: {$jsonPath}\n");
    exit(1);
}

$json = file_get_contents($jsonPath);
$data = json_decode((string)$json, true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON data\n");
    exit(1);
}

$pdo = getDb();

$insert = $pdo->prepare('INSERT INTO ipa (legacy_index, name, developer_name, subtitle, category, version, version_date, description, iconURL, downloadURL, price, tool_type, screenshots, icon_asset, screenshot_assets) VALUES (:legacy_index, :name, :developer_name, :subtitle, :category, :version, :version_date, :description, :iconURL, :downloadURL, :price, :tool_type, :screenshots, :icon_asset, :screenshot_assets) ON DUPLICATE KEY UPDATE name=VALUES(name), developer_name=VALUES(developer_name), subtitle=VALUES(subtitle), category=VALUES(category), version=VALUES(version), version_date=VALUES(version_date), description=VALUES(description), iconURL=VALUES(iconURL), downloadURL=VALUES(downloadURL), price=VALUES(price), tool_type=VALUES(tool_type), screenshots=VALUES(screenshots), icon_asset=VALUES(icon_asset), screenshot_assets=VALUES(screenshot_assets)');

$count = 0;
foreach ($data as $index => $tool) {
    if (!is_array($tool)) {
        continue;
    }

    $screenshots = normalizeJsonArrayField($tool['screenshots'] ?? []);
    $screenshotAssets = normalizeJsonArrayField($tool['screenshot_assets'] ?? []);
    $toolType = strtolower(trim((string)($tool['tool_type'] ?? 'free'))) === 'paid' ? 'paid' : 'free';

    $insert->execute([
        ':legacy_index' => (int)$index,
        ':name' => trim((string)($tool['name'] ?? '')),
        ':developer_name' => trim((string)($tool['developer_name'] ?? '')),
        ':subtitle' => trim((string)($tool['subtitle'] ?? '')),
        ':category' => trim((string)($tool['category'] ?? '')) ?: 'Other',
        ':version' => trim((string)($tool['version'] ?? '')),
        ':version_date' => trim((string)($tool['version_date'] ?? '')),
        ':description' => trim((string)($tool['description'] ?? '')),
        ':iconURL' => trim((string)($tool['iconURL'] ?? '')),
        ':downloadURL' => trim((string)($tool['downloadURL'] ?? '')),
        ':price' => is_numeric($tool['price'] ?? null) ? (float)$tool['price'] : 0,
        ':tool_type' => $toolType,
        ':screenshots' => json_encode($screenshots, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':icon_asset' => trim((string)($tool['icon_asset'] ?? '')),
        ':screenshot_assets' => json_encode($screenshotAssets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);

    $count++;
}

echo "Imported {$count} records into ipa table.\n";
