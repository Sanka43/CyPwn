<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

$config = require __DIR__ . '/config/site.php';
$source = null;

if (isset($_GET['id'])) {
    $source = get_source_by_id($pdo, (int) $_GET['id']);
} else {
    $sourceUrl = trim((string) ($_GET['source_url'] ?? ''));
    if ($sourceUrl !== '' && filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
        $source = [
            'repository' => trim((string) ($_GET['repository'] ?? 'Source')),
            'source_url' => $sourceUrl,
            'notes' => '',
        ];
    }
}

if ($source === null) {
    http_response_code(404);
    exit('Source not found.');
}

$repository = (string) ($source['repository'] ?? 'Source');
$sourceUrl = (string) ($source['source_url'] ?? '');
$pageTitle = ($config['site_name'] ?? 'CyPwn') . ' - ' . $repository;

function source_json_fetch(string $url): array
{
    $body = false;
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'CyPwn Source Reader',
        ]);
        $body = curl_exec($curl);
        $error = curl_error($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if ($body === false || $status >= 400) {
            return ['data' => null, 'error' => $error !== '' ? $error : 'Could not load source JSON.'];
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'CyPwn Source Reader',
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return ['data' => null, 'error' => 'Could not load source JSON.'];
        }
    }

    $data = json_decode((string) $body, true);
    if (!is_array($data)) {
        return ['data' => null, 'error' => 'Source did not return valid JSON.'];
    }
    return ['data' => $data, 'error' => null];
}

function source_url_join(string $baseUrl, string $url): string
{
    if ($url === '' || preg_match('#^https?://#i', $url)) {
        return $url;
    }
    $base = parse_url($baseUrl);
    if (!is_array($base) || empty($base['scheme']) || empty($base['host'])) {
        return $url;
    }
    $root = $base['scheme'] . '://' . $base['host'];
    if (str_starts_with($url, '/')) {
        return $root . $url;
    }
    $path = isset($base['path']) ? rtrim(dirname($base['path']), '/\\') : '';
    return $root . ($path !== '' ? $path . '/' : '/') . $url;
}

function source_latest_version(array $item): array
{
    $versions = $item['versions'] ?? [];
    if (is_array($versions) && isset($versions[0]) && is_array($versions[0])) {
        return $versions[0];
    }
    return [];
}

function source_normalize_tools(array $data, string $baseUrl): array
{
    $items = [];
    foreach (['apps', 'tools', 'packages', 'items'] as $key) {
        if (isset($data[$key]) && is_array($data[$key])) {
            $items = $data[$key];
            break;
        }
    }
    if ($items === [] && array_is_list($data)) {
        $items = $data;
    }

    $tools = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $latest = source_latest_version($item);
        $name = trim((string) ($item['name'] ?? $item['title'] ?? 'Tool'));
        $subtitle = trim((string) ($item['subtitle'] ?? $item['localizedDescription'] ?? $item['description'] ?? $item['developerName'] ?? ''));
        $developer = trim((string) ($item['developerName'] ?? $item['author'] ?? $item['developer'] ?? ''));
        $version = trim((string) ($latest['version'] ?? $item['version'] ?? 'Latest'));
        $download = trim((string) ($latest['downloadURL'] ?? $item['downloadURL'] ?? $item['url'] ?? '#'));
        $icon = trim((string) ($item['iconURL'] ?? $item['icon'] ?? $item['icon_url'] ?? ''));
        $category = trim((string) ($item['category'] ?? $item['type'] ?? 'Source'));

        $tools[] = [
            'name' => $name,
            'subtitle' => $subtitle,
            'developer' => $developer,
            'version' => $version,
            'download' => source_url_join($baseUrl, $download),
            'icon' => source_url_join($baseUrl, $icon),
            'category' => $category,
            'search' => strtolower($name . ' ' . $subtitle . ' ' . $developer . ' ' . $version . ' ' . $category),
        ];
    }

    return $tools;
}

function source_tool_card(array $tool): void
{
    $href = trim((string) ($tool['download'] ?? '#'));
    if ($href === '') {
        $href = '#';
    }
    ?>
    <a
        href="<?= e($href) ?>"
        class="source-package-card"
        data-source-category="<?= e(strtolower((string) ($tool['category'] ?? 'source'))) ?>"
        data-search="<?= e((string) ($tool['search'] ?? '')) ?>"
        target="_blank"
        rel="noopener noreferrer"
    >
        <span class="source-package-icon">
            <?php if (!empty($tool['icon'])): ?>
                <img src="<?= e((string) $tool['icon']) ?>" alt="" width="58" height="58" loading="lazy">
            <?php else: ?>
                <span class="source-package-placeholder" aria-hidden="true"></span>
            <?php endif; ?>
        </span>
        <span class="source-package-body">
            <strong><?= e((string) ($tool['name'] ?? 'Tool')) ?></strong>
            <span class="source-package-version">v<?= e((string) ($tool['version'] ?? 'Latest')) ?></span>
            <span class="source-package-tags">
                <?= e((string) (($tool['developer'] ?? '') !== '' ? $tool['developer'] : ($tool['subtitle'] ?? 'Source tool'))) ?>
                &bull; <?= e((string) ($tool['category'] ?? 'Source')) ?>
            </span>
        </span>
        <span class="source-package-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </span>
    </a>
    <?php
}

$fetch = source_json_fetch($sourceUrl);
$sourceData = is_array($fetch['data']) ? $fetch['data'] : [];
$tools = $sourceData !== [] ? source_normalize_tools($sourceData, $sourceUrl) : [];
$categories = [];
foreach ($tools as $tool) {
    $category = trim((string) ($tool['category'] ?? ''));
    if ($category !== '') {
        $categories[strtolower($category)] = $category;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <?= meta_description_tag() ?>
    <?= analytics_tags() ?>
    <?= favicon_tags() ?>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/home.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/repo.css')) ?>">
</head>
<body class="home-page source-page source-detail-page">
    <main class="main-content source-main">
        <div class="source-wire-shell">
            <a href="<?= e(url('sources.php')) ?>" class="source-detail-back">Back to Sources</a>

            <section class="source-manager-hero" aria-label="<?= e($repository) ?>">
                <div class="source-manager-icons" aria-hidden="true">
                    <span><?= e(strtoupper(substr($repository, 0, 2) ?: 'S')) ?></span>
                    <span>JSON</span>
                    <span><?= count($tools) ?></span>
                </div>
                <div>
                    <strong><?= e($repository) ?></strong>
                    <span><?= count($tools) ?> tools from source JSON</span>
                </div>
            </section>

            <?php if ($fetch['error'] !== null): ?>
                <p class="source-form-error"><?= e((string) $fetch['error']) ?></p>
            <?php endif; ?>

            <section class="source-wire-search" aria-label="Search source tools">
                <label class="source-wire-searchbox" for="source-search-input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
                    <input type="search" id="source-search-input" placeholder="Search by tool name" autocomplete="off">
                    <button type="button" id="source-search-clear" aria-label="Clear search">&times;</button>
                </label>
                <button type="button" class="source-wire-lock" aria-label="Loaded source">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>
                </button>
            </section>

            <section class="source-wire-chips" aria-label="Tool categories">
                <button type="button" class="source-wire-chip source-wire-chip-active" data-category="all">Updates</button>
                <button type="button" class="source-wire-chip" data-category="all">Top</button>
                <?php foreach (array_slice(array_values($categories), 0, 8) as $category): ?>
                    <button type="button" class="source-wire-chip" data-category="<?= e(strtolower($category)) ?>"><?= e($category) ?></button>
                <?php endforeach; ?>
            </section>

            <?php if ($tools === []): ?>
                <p class="empty-state">No tools found in this source JSON.</p>
            <?php else: ?>
                <section class="source-package-list" id="source-package-list" aria-label="Source tools">
                    <?php foreach ($tools as $tool): ?>
                        <?php source_tool_card($tool); ?>
                    <?php endforeach; ?>
                </section>
                <p class="no-results hidden" id="source-no-results">No tools matched your search.</p>
            <?php endif; ?>
        </div>
    </main>

    <nav class="apps-wire-tabs" aria-label="App navigation">
        <a href="<?= e(url('apps.php')) ?>" class="apps-wire-tab">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6.5L12 3l8 3.5-8 3.5-8-3.5zm0 5L12 8l8 3.5-8 3.5-8-3.5zm0 5L12 13l8 3.5-8 3.5-8-3.5z"/></svg>
            <span>Apps</span>
        </a>
        <a href="<?= e(url('sources.php')) ?>" class="apps-wire-tab apps-wire-tab-active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a13 13 0 010 18M12 3a13 13 0 000 18"/></svg>
            <span>Sources</span>
        </a>
        <a href="<?= e(url('repo.php')) ?>" class="apps-wire-tab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
            <span>Repo</span>
        </a>
        <a href="<?= e(url('index.php')) ?>" class="apps-wire-tab">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.1L3 10.3V21h6v-6h6v6h6V10.3l-9-7.2z"/></svg>
            <span>Home</span>
        </a>
    </nav>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>
    <script src="<?= e(asset_url('assets/js/sources.js')) ?>"></script>
</body>
</html>
