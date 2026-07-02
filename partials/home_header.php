<?php

declare(strict_types=1);

/** @var array<string, mixed> $config */
$brandLabel = $config['brand_short'] ?? $config['site_name'] ?? '';
$activeNav = $activeNav ?? '';
$pageHeading = $pageHeading ?? $brandLabel;
$pageTagline = $pageTagline ?? 'Free IPAs & jailbreak tools.';
$homeUrl = url('index.php');
?>
<header class="home-header">
    <div class="home-header-top">
        <div class="home-header-left">
            <a href="<?= e($homeUrl) ?>" class="home-logo-link">
                <img src="<?= e(asset_url($config['logo'] ?? 'assets/img/logo.png')) ?>" alt="<?= e($brandLabel) ?>" class="home-logo" width="26" height="26">
            </a>
            <nav class="home-pill-nav" aria-label="Site navigation">
                <a href="<?= e(url('repo.php')) ?>" class="home-pill<?= $activeNav === 'repo' ? ' home-pill-active' : '' ?>">Repo</a>
                <a href="<?= e(url('apps.php')) ?>" class="home-pill<?= $activeNav === 'apps' ? ' home-pill-active' : '' ?>">Apps</a>
                <a href="<?= e($homeUrl . '#about') ?>" class="home-pill<?= $activeNav === 'about' ? ' home-pill-active' : '' ?>">About</a>
            </nav>
        </div>
    </div>
    <div class="home-brand">
        <h1 class="home-brand-name"><?= e($pageHeading) ?></h1>
        <p class="home-brand-tagline"><?= e($pageTagline) ?></p>
    </div>
</header>
