<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

$config = require __DIR__ . '/config/site.php';

$id = (int) ($_GET['id'] ?? 0);
$app = get_app_by_id($pdo, $id);

if (!$app) {
    http_response_code(404);
    $pageTitle = 'App not found — ' . page_title();
} else {
    $tab = ($app['store_type'] ?? 'ipa') === 'trollstore' ? 'trollstore' : 'ipa';
    $screenshots = decode_screenshots($app['screenshots'] ?? null);
    $pageTitle = $app['name'] . ' — ' . page_title();
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
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a href="<?= e(url('index.php')) ?>" class="brand">
                <img src="<?= e(asset_url($config['logo'] ?? 'assets/img/logo.svg')) ?>" alt="<?= e($config['brand_short'] ?? $config['site_name']) ?>" class="brand-logo-sm" width="28" height="28">
                <span><?= e($config['brand_short'] ?? $config['site_name']) ?></span>
            </a>
            <div class="header-actions">
                <nav class="header-nav" id="header-nav">
                    <?php foreach ($config['nav_links'] as $link): ?>
                        <?php $openNewTab = !empty($link['new_tab']); ?>
                        <a href="<?= e($link['url']) ?>" class="nav-link"<?= $openNewTab ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= e($link['label']) ?></a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php if (!$app): ?>
            <section class="app-detail-section">
                <p class="empty-state">This app could not be found.</p>
                <p style="text-align: center; margin-top: 1rem;">
                    <a href="<?= e(url('store.php')) ?>" class="btn-download" style="display: inline-block; width: auto;">Back to catalog</a>
                </p>
            </section>
        <?php else: ?>
            <section class="app-detail-section">
                <a href="<?= e(url('store.php?tab=' . $tab)) ?>" class="app-detail-back">← Back to catalog</a>
                <article class="app-detail">
                    <div class="modal-header">
                        <?php if (!empty($app['icon'])): ?>
                            <img src="<?= e($app['icon']) ?>" alt="<?= e($app['name']) ?>" class="modal-icon" width="80" height="80">
                        <?php endif; ?>
                        <div class="modal-header-text">
                            <h1 id="app-title"><?= e($app['name']) ?></h1>
                            <?php if (!empty($app['subtitle'])): ?>
                                <p class="modal-subtitle"><?= e($app['subtitle']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($app['developer_name'])): ?>
                                <p class="modal-developer">by <?= e($app['developer_name']) ?></p>
                            <?php endif; ?>
                            <div class="modal-tags">
                                <?php if (!empty($app['category'])): ?>
                                    <span class="modal-tag"><?= e($app['category']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($app['version'])): ?>
                                    <span class="modal-tag">v<?= e($app['version']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($app['version_date'])): ?>
                                    <span class="modal-tag"><?= e($app['version_date']) ?></span>
                                <?php endif; ?>
                                <span class="modal-tag"><?= e(display_app_size($app)) ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($screenshots !== []): ?>
                        <div class="modal-screenshots">
                            <?php foreach ($screenshots as $shot): ?>
                                <img src="<?= e($shot) ?>" alt="Screenshot" loading="lazy">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="modal-description app-detail-description"><?= e($app['description'] ?: 'No description available.') ?></div>

                    <?php if (!empty($app['download_url'])): ?>
                        <a href="<?= e($app['download_url']) ?>" class="modal-download btn-download" target="_blank" rel="noopener">Download</a>
                    <?php endif; ?>
                </article>
            </section>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p><?= e($config['copyright'] ?? '© 2026 CyPwn') ?></p>
    </footer>
</body>
</html>
