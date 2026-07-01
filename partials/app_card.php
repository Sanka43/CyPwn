<?php

declare(strict_types=1);

/** @var array<string, mixed> $app */
$appCategory = trim((string) ($app['category'] ?? ''));
$appCategorySlug = $appCategory === '' ? 'uncategorized' : strtolower($appCategory);
?>
<article
    class="app-card"
    data-id="<?= (int) $app['id'] ?>"
    data-app-category="<?= e($appCategorySlug) ?>"
    data-search="<?= e(strtolower(
        ($app['name'] ?? '') . ' '
        . ($app['developer_name'] ?? '') . ' '
        . ($app['category'] ?? '') . ' '
        . ($app['version'] ?? '')
    )) ?>"
    tabindex="0"
    role="button"
>
    <div class="app-card-icon">
        <?php if (!empty($app['icon'])): ?>
            <img src="<?= e($app['icon']) ?>" alt="" width="54" height="54" loading="lazy">
        <?php else: ?>
            <div class="app-card-placeholder"></div>
        <?php endif; ?>
    </div>
    <div class="app-card-body">
        <h3 class="app-card-title"><?= e($app['name']) ?></h3>
        <ul class="app-card-meta-list">
            <li class="app-card-meta app-card-meta--version">
                <span class="meta-label">Version:</span>
                <span class="meta-value"><?= e($app['version']) ?></span>
            </li>
            <li class="app-card-meta app-card-meta--size">
                <span class="meta-label">Size:</span>
                <span class="meta-value"><?= e(display_app_size($app)) ?></span>
            </li>
            <li class="app-card-meta app-card-meta--date">
                <span class="meta-label">Upload Date:</span>
                <span class="meta-value"><?= e($app['version_date'] ?? '—') ?></span>
            </li>
        </ul>
    </div>
    <button
        type="button"
        class="app-card-view"
        aria-label="View details"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    </button>
</article>
