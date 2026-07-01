<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

if (isset($_GET['tab'])) {
    header('Location: ' . url('store.php?tab=' . ($_GET['tab'] === 'trollstore' ? 'trollstore' : 'ipa')));
    exit;
}

$config = require __DIR__ . '/config/site.php';
$featuredApps = get_featured_apps($pdo, 56);

$discordUrl = 'https://discord.com/invite/UvHZz3HfN9';
foreach ($config['nav_links'] as $link) {
    if (($link['label'] ?? '') === 'Discord') {
        $discordUrl = (string) ($link['url'] ?? $discordUrl);
        break;
    }
}

$featureCards = [
    [
        'title' => 'IPA Library',
        'desc' => 'Browse free signed IPAs. No computer needed — install straight from your device.',
        'url' => url('store.php?tab=ipa'),
        'btn' => 'light',
        'theme' => 'surface',
        'icon' => '<svg class="home-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg>',
        'art' => '<svg viewBox="0 0 200 140" fill="none" aria-hidden="true"><rect x="60" y="20" width="80" height="100" rx="12" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.15)" stroke-width="2"/><circle cx="100" cy="95" r="6" fill="rgba(74,158,255,0.6)"/><rect x="72" y="35" width="56" height="8" rx="4" fill="rgba(255,255,255,0.12)"/><rect x="72" y="50" width="40" height="6" rx="3" fill="rgba(255,255,255,0.08)"/></svg>',
    ],
    [
        'title' => 'TrollStore',
        'desc' => 'Permanent installs without revokes. Apps stay on your device for good.',
        'url' => url('store.php?tab=trollstore'),
        'btn' => 'light',
        'theme' => 'blue',
        'icon' => '<svg class="home-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg>',
        'art' => '<svg viewBox="0 0 200 140" fill="none" aria-hidden="true"><circle cx="70" cy="70" r="28" fill="rgba(255,255,255,0.15)"/><circle cx="110" cy="55" r="22" fill="rgba(255,255,255,0.1)"/><circle cx="130" cy="85" r="18" fill="rgba(255,255,255,0.12)"/><path d="M70 58v24M58 70h24" stroke="rgba(255,255,255,0.4)" stroke-width="3" stroke-linecap="round"/></svg>',
    ],
    [
        'title' => 'CyPwn Repo',
        'desc' => 'Tweaks, themes, and jailbreak packages from our official repository.',
        'url' => url('repo.php'),
        'btn' => 'light',
        'theme' => 'pink',
        'icon' => '<svg class="home-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>',
        'art' => '<svg viewBox="0 0 200 140" fill="none" aria-hidden="true"><rect x="55" y="30" width="90" height="80" rx="16" fill="rgba(0,0,0,0.2)"/><path d="M75 55h50M75 70h35M75 85h45" stroke="rgba(255,255,255,0.35)" stroke-width="4" stroke-linecap="round"/></svg>',
    ],
    [
        'title' => 'Custom Releases',
        'desc' => 'Exclusive CyPwn apps and tools you won\'t find anywhere else.',
        'url' => url('store.php'),
        'btn' => 'dark',
        'theme' => 'grey',
        'icon' => '<svg class="home-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2a4 4 0 014 4v1h2a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V9a2 2 0 012-2h2V6a4 4 0 014-4z"/></svg>',
        'art' => '<svg viewBox="0 0 200 140" fill="none" aria-hidden="true"><rect x="70" y="25" width="60" height="90" rx="10" fill="rgba(255,255,255,0.06)" stroke="rgba(255,255,255,0.12)" stroke-width="2"/><circle cx="85" cy="50" r="10" fill="rgba(74,158,255,0.4)"/><circle cx="115" cy="50" r="10" fill="rgba(234,179,8,0.4)"/><circle cx="100" cy="75" r="10" fill="rgba(236,72,153,0.4)"/></svg>',
    ],
    [
        'title' => 'Sideloading',
        'desc' => 'Download and install with one tap. Quick access to every app in the store.',
        'url' => url('store.php'),
        'btn' => 'light',
        'theme' => 'navy',
        'icon' => '<svg class="home-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>',
        'art' => '<svg viewBox="0 0 200 140" fill="none" aria-hidden="true"><path d="M100 35v50M85 70l15 15 15-15" stroke="rgba(74,158,255,0.7)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><rect x="60" y="95" width="80" height="12" rx="6" fill="rgba(255,255,255,0.1)"/></svg>',
    ],
    [
        'title' => 'Community',
        'desc' => 'Join our Discord for support, updates, and requests from the CyPwn team.',
        'url' => $discordUrl,
        'btn' => 'dark',
        'theme' => 'community',
        'new_tab' => true,
        'icon' => '<svg class="home-card-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.317 4.37a19.79 19.79 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028 14.09 14.09 0 001.226-1.994.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>',
        'art' => '<svg viewBox="0 0 200 140" fill="none" aria-hidden="true"><circle cx="100" cy="70" r="40" fill="rgba(88,101,242,0.25)"/><circle cx="80" cy="65" r="8" fill="rgba(255,255,255,0.5)"/><circle cx="120" cy="65" r="8" fill="rgba(255,255,255,0.5)"/><path d="M85 85c5 8 25 8 30 0" stroke="rgba(255,255,255,0.35)" stroke-width="3" stroke-linecap="round"/></svg>',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(page_title()) ?></title>
    <?= meta_description_tag() ?>
    <?= analytics_tags() ?>
    <?= favicon_tags() ?>
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/home.css')) ?>">
</head>
<body class="home-page">
    <main class="main-content">
        <?php require __DIR__ . '/partials/home_header.php'; ?>

        <section class="home-carousel-section" aria-label="Features">
            <div class="home-carousel-track" id="home-carousel-track">
                <?php foreach ($featureCards as $card): ?>
                    <article class="home-card home-card--<?= e($card['theme']) ?>">
                        <div class="home-card-header">
                            <?= $card['icon'] ?>
                            <h2 class="home-card-title"><?= e($card['title']) ?></h2>
                        </div>
                        <p class="home-card-desc"><?= e($card['desc']) ?></p>
                        <a
                            href="<?= e($card['url']) ?>"
                            class="home-card-btn home-card-btn-<?= e($card['btn']) ?>"
                            <?php if (!empty($card['new_tab'])): ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                        >Get it</a>
                        <div class="home-card-art"><?= $card['art'] ?></div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="home-carousel-controls">
                <button type="button" class="home-carousel-arrow" id="home-carousel-prev" aria-label="Previous card">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <div class="home-carousel-dots" id="home-carousel-dots" role="tablist" aria-label="Carousel pagination"></div>
                <button type="button" class="home-carousel-arrow" id="home-carousel-next" aria-label="Next card">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
        </section>

        <?php require __DIR__ . '/partials/home_apps_grid.php'; ?>

        <section class="home-about" id="about">
            <h2>About CyPwn</h2>
            <p>
                CyPwn is a free IPA library and jailbreak repository built for the iOS community.
                Browse signed IPAs, TrollStore apps, tweaks, themes, and custom tools — all in one place.
            </p>
        </section>
    </main>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>

    <script src="<?= e(asset_url('assets/js/home.js')) ?>"></script>
</body>
</html>
