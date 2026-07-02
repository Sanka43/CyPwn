<?php

declare(strict_types=1);

/** @var array<string, mixed> $config */
/** @var array<int, array<string, string>>|null $navLinks */
$navLinks = $navLinks ?? $config['nav_links'];
$brandLabel = $config['brand_short'] ?? $config['site_name'] ?? '';
$activeNav = $activeNav ?? '';
?>
<header class="site-header">
    <div class="header-inner">
        <a href="<?= e(url('index.php')) ?>" class="brand">
            <img src="<?= e(asset_url($config['logo'] ?? 'assets/img/logo.svg')) ?>" alt="<?= e($brandLabel) ?>" class="brand-logo-sm" width="28" height="28">
            <span><?= e($brandLabel) ?></span>
        </a>
        <div class="header-actions">
            <button
                type="button"
                class="nav-menu-toggle"
                id="nav-menu-toggle"
                aria-expanded="false"
                aria-controls="header-nav"
                aria-label="Open menu"
            >
                <span class="nav-menu-bars" aria-hidden="true"></span>
            </button>
            <nav class="header-nav" id="header-nav">
                <?php foreach ($navLinks as $link): ?>
                    <?php
                    $navUrl = (string) ($link['url'] ?? '#');
                    $navHref = (str_starts_with($navUrl, 'http') || str_starts_with($navUrl, '#'))
                        ? $navUrl
                        : url($navUrl);
                    $navKey = strtolower((string) ($link['icon'] ?? $link['label'] ?? ''));
                    $navClass = 'nav-link' . ($activeNav === $navKey ? ' nav-link-active' : '');
                    ?>
                    <?php $openNewTab = !empty($link['new_tab']); ?>
                    <a href="<?= e($navHref) ?>" class="<?= e($navClass) ?>"<?= $openNewTab ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= e($link['label']) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
</header>
