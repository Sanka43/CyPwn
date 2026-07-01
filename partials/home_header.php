<?php

declare(strict_types=1);

/** @var array<string, mixed> $config */
$brandLabel = $config['brand_short'] ?? $config['site_name'] ?? '';
$discordUrl = 'https://discord.com/invite/UvHZz3HfN9';
foreach ($config['nav_links'] as $link) {
    if (($link['label'] ?? '') === 'Discord') {
        $discordUrl = (string) ($link['url'] ?? $discordUrl);
        break;
    }
}
?>
<header class="home-header">
    <div class="home-header-top">
        <div class="home-header-left">
            <a href="<?= e(url('index.php')) ?>" class="home-logo-link">
                <img src="<?= e(asset_url($config['logo'] ?? 'assets/img/logo.png')) ?>" alt="<?= e($brandLabel) ?>" class="home-logo" width="26" height="26">
            </a>
            <nav class="home-pill-nav" aria-label="Home navigation">
                <a href="<?= e(url('store.php')) ?>" class="home-pill">Store</a>
                <a href="<?= e(url('Packages.php')) ?>" class="home-pill">Repo</a>
                <a href="#apps" class="home-pill">Apps</a>
                <a href="<?= e($discordUrl) ?>" class="home-pill" target="_blank" rel="noopener noreferrer">Discord</a>
                <a href="#about" class="home-pill">About</a>
            </nav>
        </div>
        <a href="<?= e(url('store.php')) ?>" class="home-get-btn">GET</a>
    </div>
    <div class="home-brand">
        <h1 class="home-brand-name"><?= e($brandLabel) ?></h1>
        <p class="home-brand-tagline">Free IPAs &amp; jailbreak tools.</p>
    </div>
</header>
