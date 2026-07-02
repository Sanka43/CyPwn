<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

$config = require __DIR__ . '/config/site.php';
$pageTitle = ($config['site_name'] ?? 'CyPwn') . ' - Apps';
$activeNav = 'apps';
$pageHeading = 'Apps';
$pageTagline = 'Discover IPAs, TrollStore apps, and community favorites.';

$apps = get_public_apps($pdo);
$categories = get_distinct_categories($pdo);
$groupedApps = group_apps_by_category($apps);
$latestApps = $apps;
usort($latestApps, static function (array $a, array $b): int {
    return strcmp((string) ($b['version_date'] ?? ''), (string) ($a['version_date'] ?? ''));
});
$featuredApps = array_slice($apps, 0, 12);
$latestApps = array_slice($latestApps, 0, 24);
$ipaCount = 0;
$trollStoreCount = 0;
foreach ($apps as $app) {
    if (($app['store_type'] ?? 'ipa') === 'trollstore') {
        $trollStoreCount++;
    } else {
        $ipaCount++;
    }
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

function app_store_category(array $app): string
{
    $category = trim((string) ($app['category'] ?? ''));
    return $category === '' ? 'Uncategorized' : $category;
}

function app_store_card(array $app, string $variant = ''): void
{
    $category = app_store_category($app);
    $categorySlug = strtolower($category);
    $storeType = ($app['store_type'] ?? 'ipa') === 'trollstore' ? 'TrollStore' : 'IPA';
    $subtitle = trim((string) ($app['subtitle'] ?: ($app['developer_name'] ?: $category)));
    $classes = trim('app-store-card app-card ' . $variant);
    ?>
    <article
        class="<?= e($classes) ?>"
        data-id="<?= (int) $app['id'] ?>"
        data-app-category="<?= e($categorySlug) ?>"
        data-search="<?= e(strtolower(
            ($app['name'] ?? '') . ' '
            . ($app['developer_name'] ?? '') . ' '
            . ($app['category'] ?? '') . ' '
            . ($app['version'] ?? '')
        )) ?>"
        tabindex="0"
        role="button"
    >
        <div class="app-store-icon">
            <?php if (!empty($app['icon'])): ?>
                <img src="<?= e($app['icon']) ?>" alt="" width="68" height="68" loading="lazy">
            <?php else: ?>
                <span class="app-store-icon-placeholder" aria-hidden="true"></span>
            <?php endif; ?>
        </div>
        <div class="app-store-card-body">
            <h3><?= e($app['name']) ?></h3>
            <p class="app-store-version">v<?= e((string) ($app['version'] ?: 'Latest')) ?></p>
            <p class="app-store-tags"><?= e($subtitle) ?><?= $subtitle !== '' ? ' &bull; ' : '' ?><?= e($storeType) ?> &bull; <?= e(display_app_size($app)) ?></p>
            <div class="app-store-meta">
                <span><?= e($category) ?></span>
                <span><?= e($storeType) ?></span>
            </div>
        </div>
        <button type="button" class="app-store-get app-card-view" aria-label="View <?= e($app['name']) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
        </button>
    </article>
    <?php
}

function app_store_promo(array $apps, string $title, string $subtitle, string $tone = 'blue'): void
{
    ?>
    <article class="apps-wire-promo apps-wire-promo--<?= e($tone) ?>">
        <div class="apps-wire-icons" aria-hidden="true">
            <?php foreach (array_slice($apps, 0, 5) as $app): ?>
                <span>
                    <?php if (!empty($app['icon'])): ?>
                        <img src="<?= e($app['icon']) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                </span>
            <?php endforeach; ?>
        </div>
        <div>
            <strong><?= e($title) ?></strong>
            <span><?= e($subtitle) ?></span>
        </div>
    </article>
    <?php
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
<body class="home-page apps-store-page">
    <main class="main-content">
        <?php require __DIR__ . '/partials/home_header.php'; ?>

        <div class="page-body apps-store-body">
            <?php if ($apps === []): ?>
                <p class="empty-state">No apps yet. Check back soon.</p>
            <?php else: ?>
                <section class="apps-wire-shell" aria-label="Apps catalog">
                    <div class="apps-wire-promos" aria-label="Featured collections">
                        <?php app_store_promo($featuredApps, 'Offers for the media', count($apps) . ' CyPwn tools', 'media'); ?>
                        <?php app_store_promo(array_slice($featuredApps, 5), 'Are you ready?', $trollStoreCount . ' TrollStore picks', 'purple'); ?>
                    </div>

                    <section class="apps-wire-search" aria-label="Search apps">
                        <label class="apps-wire-searchbox" for="search-input">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
                            <input type="search" id="search-input" class="search-input" placeholder="Search by app name" autocomplete="off">
                            <button type="button" class="search-clear" id="search-clear" aria-label="Clear search">&times;</button>
                        </label>
                        <button type="button" class="apps-wire-lock" aria-label="Account">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="3"/><path d="M8 10V7a4 4 0 018 0v3"/><path d="M12 14v2"/></svg>
                        </button>
                    </section>

                    <section class="apps-wire-chips" aria-label="Categories">
                        <button type="button" class="apps-wire-chip filter-option filter-option-active" data-category="all">Updates</button>
                        <button type="button" class="apps-wire-chip filter-option" data-category="all">Top</button>
                        <?php foreach (array_slice($categories, 0, 8) as $categoryName): ?>
                            <button type="button" class="apps-wire-chip filter-option" data-category="<?= e(strtolower($categoryName)) ?>"><?= e($categoryName) ?></button>
                        <?php endforeach; ?>
                    </section>

                    <div class="apps-store-stats" aria-label="Catalog stats">
                        <div><strong><?= count($apps) ?></strong><span>Apps</span></div>
                        <div><strong><?= count($categories) ?></strong><span>Categories</span></div>
                        <div><strong><?= $ipaCount ?></strong><span>IPAs</span></div>
                        <div><strong><?= $trollStoreCount ?></strong><span>TrollStore</span></div>
                    </div>

                    <div id="apps-catalog" class="apps-wire-list">
                        <?php foreach ($apps as $app): ?>
                            <?php app_store_card($app, 'app-store-card-row'); ?>
                        <?php endforeach; ?>
                    </div>
                    <p class="no-results hidden" id="no-results">No apps matched your search.</p>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <nav class="apps-wire-tabs" aria-label="App navigation">
        <a href="<?= e(url('apps.php')) ?>" class="apps-wire-tab apps-wire-tab-active">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6.5L12 3l8 3.5-8 3.5-8-3.5zm0 5L12 8l8 3.5-8 3.5-8-3.5zm0 5L12 13l8 3.5-8 3.5-8-3.5z"/></svg>
            <span>Apps</span>
        </a>
        <a href="<?= e(url('sources.php')) ?>" class="apps-wire-tab">
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

    <div class="modal-overlay hidden" id="modal-overlay" aria-hidden="true">
        <div class="modal" id="modal" role="dialog" aria-labelledby="modal-title" aria-modal="true">
            <button type="button" class="modal-close" id="modal-close" aria-label="Close">&times;</button>
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
