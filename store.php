<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

$config = require __DIR__ . '/config/site.php';
$pageTitle = ($config['site_name'] ?? 'CyPwn') . ' — Store';

$tab = ($_GET['tab'] ?? 'ipa') === 'trollstore' ? 'trollstore' : 'ipa';
$heroTab = $tab;
$apps = get_apps_by_store($pdo, $tab);
$filterCategories = get_distinct_categories($pdo, $tab);
$hasUncategorized = false;
foreach ($apps as $app) {
    if (trim((string) ($app['category'] ?? '')) === '') {
        $hasUncategorized = true;
        break;
    }
}
if ($hasUncategorized) {
    $filterCategories[] = 'Uncategorized';
}

$appsJson = [];
foreach ($apps as $app) {
    $appsJson[] = [
        'id' => (int) $app['id'],
        'name' => $app['name'],
        'icon' => $app['icon'],
        'developer_name' => $app['developer_name'],
        'subtitle' => $app['subtitle'],
        'category' => $app['category'],
        'version' => $app['version'],
        'version_date' => $app['version_date'],
        'description' => $app['description'],
        'download_url' => $app['download_url'],
        'screenshots' => decode_screenshots($app['screenshots'] ?? null),
        'size' => display_app_size($app),
    ];
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
</head>
<body class="home-page">
    <main class="main-content">
        <?php
        $activeNav = 'store';
        $pageHeading = 'Store';
        $pageTagline = 'Browse IPAs and TrollStore apps.';
        require __DIR__ . '/partials/home_header.php';
        ?>

        <div class="page-body">
        <?php require __DIR__ . '/partials/hero_tabs.php'; ?>

        <section class="search-section">
            <div class="search-row">
                <?php if ($filterCategories !== []): ?>
                <div class="filter-wrap">
                    <button
                        type="button"
                        class="filter-btn"
                        id="filter-btn"
                        aria-expanded="false"
                        aria-controls="filter-panel"
                        aria-label="Filter by category"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 6h16M7 12h10M10 18h4"/>
                        </svg>
                    </button>
                    <div class="filter-panel hidden" id="filter-panel" role="dialog" aria-label="Category filter">
                        <button type="button" class="filter-option filter-option-active" data-category="all">All</button>
                        <?php foreach ($filterCategories as $categoryName): ?>
                            <button
                                type="button"
                                class="filter-option"
                                data-category="<?= e(strtolower($categoryName)) ?>"
                            ><?= e($categoryName) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="search-wrap">
                    <span class="search-icon" aria-hidden="true">🔍</span>
                    <input type="search" id="search-input" class="search-input" placeholder="Search apps..." autocomplete="off">
                    <button type="button" class="search-clear" id="search-clear" aria-label="Clear search">×</button>
                </div>
            </div>
        </section>

        <section class="apps-section">
            <?php if (empty($apps)): ?>
                <p class="empty-state" id="empty-state">No apps in this store yet. Check back soon.</p>
            <?php else: ?>
                <div class="apps-catalog" id="apps-catalog">
                    <div class="apps-grid">
                        <?php foreach ($apps as $app): ?>
                            <?php require __DIR__ . '/partials/app_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <p class="no-results hidden" id="no-results">No apps match your search.</p>
        </section>
        </div>
    </main>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>

    <div class="modal-overlay hidden" id="modal-overlay" aria-hidden="true">
        <div class="modal" id="modal" role="dialog" aria-labelledby="modal-title" aria-modal="true">
            <button type="button" class="modal-close" id="modal-close" aria-label="Close">×</button>
            <div class="modal-header">
                <img src="" alt="" class="modal-icon" id="modal-icon" width="80" height="80">
                <div class="modal-header-text">
                    <h2 id="modal-title"></h2>
                    <p class="modal-subtitle" id="modal-subtitle"></p>
                    <p class="modal-developer" id="modal-developer"></p>
                    <div class="modal-tags">
                        <span class="modal-tag" id="modal-category"></span>
                        <span class="modal-tag" id="modal-version"></span>
                        <span class="modal-tag" id="modal-date"></span>
                    </div>
                </div>
            </div>
            <div class="modal-screenshots hidden" id="modal-screenshots"></div>
            <div class="modal-description" id="modal-description"></div>
            <a href="#" class="modal-download btn-download" id="modal-download" target="_blank" rel="noopener">Download</a>
        </div>
    </div>

    <script>
        window.CYPWN_APPS = <?= json_encode($appsJson, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.CYPWN_API = 'api/app.php';
    </script>
    <script src="<?= e(asset_url('assets/js/app.js')) ?>"></script>
</body>
</html>
