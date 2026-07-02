<?php

declare(strict_types=1);

require __DIR__ . '/includes/functions.php';

$config = require __DIR__ . '/config/site.php';
$pageTitle = ($config['site_name'] ?? 'CyPwn') . ' - Sources';
$repoUrl = 'https://repo.cypwn.xyz/';
$ipaRepoUrl = 'https://ipa.cypwn.xyz/cypwn.json';

$repoDataPath = __DIR__ . '/assets/repo/packages.json';
$repoData = is_file($repoDataPath)
    ? json_decode((string) file_get_contents($repoDataPath), true)
    : [];

$packages = is_array($repoData['packages'] ?? null) ? $repoData['packages'] : [];
$sections = is_array($repoData['sections'] ?? null) ? $repoData['sections'] : [];
$sectionCounts = is_array($repoData['section_counts'] ?? null) ? $repoData['section_counts'] : [];
$totalCount = (int) ($repoData['total_count'] ?? count($packages));

$sourceLinks = [
    [
        'label' => 'Cydia',
        'url' => 'https://cydia.saurik.com/api/share#?source=' . $repoUrl,
        'theme' => 'orange',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/></svg>',
    ],
    [
        'label' => 'Sileo',
        'url' => 'sileo://source/' . $repoUrl,
        'theme' => 'pink',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
    ],
    [
        'label' => 'Zebra',
        'url' => 'zbra://sources/add/' . $repoUrl,
        'theme' => 'grey',
        'icon' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>',
    ],
    [
        'label' => 'Installer',
        'url' => 'installer://add/' . $repoUrl,
        'theme' => 'blue',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>',
    ],
];

$popularIpaRepos = [
    ['name' => '9Animator', 'url' => 'https://9ani.app/api/altstore'],
    ['name' => 'AltStore Complete', 'url' => 'https://bit.ly/Altstore-complete'],
    ['name' => "Burrito's AltStore", 'url' => 'https://burritosoftware.github.io/altstore/channels/burritosource.json'],
    ['name' => 'Omni Repository', 'url' => 'https://raw.githubusercontent.com/Omni-Development/The-Omni-Repository/refs/heads/main/app-repo.json'],
    ['name' => "Qn_'s AltStore Repo", 'url' => 'https://bit.ly/40Isul6'],
    ['name' => 'Quantum V2 (Tweaks, Streaming Apps & More)', 'url' => 'https://quarksources.github.io/dist/quantumsource%2B%2B.min.json'],
    ['name' => 'Quantum V1 (Emulators, Jailbreak Tools & Utilities)', 'url' => 'https://quarksources.github.io/dist/quantumsource.min.json'],
    ['name' => 'RandomBlock', 'url' => 'https://github.com/luh-99/SideStoreAndAltStore-Sources/blob/main/randomblock1.com/altstore/apps.json'],
    ['name' => "Wuxu's Library++", 'url' => 'https://github.com/luh-99/SideStoreAndAltStore-Sources/blob/main/wuxu1.github.io/wuxu-complete-plus.json'],
    ['name' => 'iSH AltStore Repo', 'url' => 'https://ish.app/altstore.json'],
    ['name' => 'EthMods (Deprecated)', 'url' => 'https://repo.ethsign.fyi/'],
];

function repo_package_icon(array $package): string
{
    $icon = trim((string) ($package['icon_local'] ?? ''));
    if ($icon === '') {
        $icon = trim((string) ($package['icon_url'] ?? ''));
    }
    if ($icon === '') {
        return '';
    }
    if (str_starts_with($icon, 'repoimg/')) {
        return asset_url('assets/repo/' . $icon);
    }
    return $icon;
}

function repo_package_category(array $package): string
{
    $categories = $package['categories'] ?? [];
    if (is_array($categories) && $categories !== []) {
        return (string) $categories[0];
    }
    $tags = $package['tags'] ?? [];
    if (is_array($tags) && $tags !== []) {
        return (string) $tags[0];
    }
    return 'Source';
}

function repo_package_card(array $package): void
{
    $name = (string) ($package['name'] ?? 'Package');
    $version = (string) ($package['version'] ?? 'Latest');
    $author = trim((string) ($package['author'] ?? 'CyPwn'));
    $category = repo_package_category($package);
    $tags = is_array($package['tags'] ?? null) ? array_slice($package['tags'], 0, 3) : [];
    $categories = is_array($package['categories'] ?? null) ? $package['categories'] : [];
    $href = trim((string) ($package['package_url'] ?? '#'));
    $icon = repo_package_icon($package);
    $search = strtolower($name . ' ' . $version . ' ' . $author . ' ' . implode(' ', $tags) . ' ' . implode(' ', $categories));
    $categoryData = strtolower(implode('|', $categories));
    ?>
    <a
        href="<?= e($href !== '' ? $href : '#') ?>"
        class="source-package-card"
        data-source-category="<?= e($categoryData) ?>"
        data-search="<?= e($search) ?>"
        target="_blank"
        rel="noopener noreferrer"
    >
        <span class="source-package-icon">
            <?php if ($icon !== ''): ?>
                <img src="<?= e($icon) ?>" alt="" width="58" height="58" loading="lazy">
            <?php else: ?>
                <span class="source-package-placeholder" aria-hidden="true"></span>
            <?php endif; ?>
        </span>
        <span class="source-package-body">
            <strong><?= e($name) ?></strong>
            <span class="source-package-version">v<?= e($version !== '' ? $version : 'Latest') ?></span>
            <span class="source-package-tags"><?= e($author !== '' ? $author : 'CyPwn') ?> &bull; <?= e($category) ?><?= $tags !== [] ? ' &bull; ' . e(implode(' / ', $tags)) : '' ?></span>
        </span>
        <span class="source-package-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </span>
    </a>
    <?php
}

function repo_source_button(array $link): void
{
    ?>
    <a href="<?= e((string) $link['url']) ?>" class="source-add-card source-add-card--<?= e((string) $link['theme']) ?>">
        <span><?= $link['icon'] ?></span>
        <strong><?= e((string) $link['label']) ?></strong>
        <em>Add Source</em>
    </a>
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
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/repo.css')) ?>">
</head>
<body class="home-page source-page repo-modern-page">
    <main class="main-content source-main">
        <div class="source-wire-shell">
            <section class="repo-modern-hero" aria-labelledby="repo-modern-title">
                <div class="repo-modern-hero-copy">
                    <span class="repo-modern-eyebrow">CyPwn Repository</span>
                    <h1 id="repo-modern-title">CyPwn Repos for iPhone &amp; iPad</h1>
                    <p>Add trusted IPA sources, jailbreak tools, emulators, utilities, modded apps, games, and no-jailbreak downloads for iOS 15 through iOS 26+.</p>

                    <div class="repo-modern-url" aria-label="Official CyPwn Repository URL">
                        <div>
                            <span>Official Source URL</span>
                            <a href="<?= e($ipaRepoUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($ipaRepoUrl) ?></a>
                        </div>
                        <button type="button" class="repo-copy-btn" data-copy-url="<?= e($ipaRepoUrl) ?>" aria-label="Copy official CyPwn source URL">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            <span>Copy</span>
                        </button>
                    </div>

                    <div class="repo-modern-actions">
                        <a href="<?= e($ipaRepoUrl) ?>" target="_blank" rel="noopener noreferrer">Open Source</a>
                        <button type="button" class="repo-modern-action-btn" data-copy-url="<?= e($ipaRepoUrl) ?>">
                            Copy Link
                        </button>
                        <a href="#popular-repositories">Popular Repos</a>
                    </div>
                </div>

                <div class="repo-modern-device" aria-hidden="true">
                    <div class="repo-modern-screen">
                        <div class="repo-modern-screen-top">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="repo-modern-app-grid">
                            <?php foreach (array_slice($packages, 0, 9) as $package): ?>
                                <?php $icon = repo_package_icon($package); ?>
                                <span>
                                    <?php if ($icon !== ''): ?>
                                        <img src="<?= e($icon) ?>" alt="" loading="lazy">
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="repo-modern-stats" aria-label="Repository highlights">
                <div>
                    <strong><?= $totalCount ?></strong>
                    <span>Packages Ready</span>
                </div>
                <div>
                    <strong>iOS 15-26+</strong>
                    <span>Supported Range</span>
                </div>
                <div>
                    <strong>No JB</strong>
                    <span>Jailbreak Optional</span>
                </div>
            </section>

            <section class="repo-modern-source-panel" aria-labelledby="source-install-title">
                <div class="repo-modern-section-head">
                    <div>
                        <span>Add CyPwn Repo</span>
                        <h2 id="source-install-title">Choose your package manager</h2>
                    </div>
                    <p>Quick links for Cydia, Sileo, Zebra, and Installer.</p>
                </div>

                <div class="source-add-grid" aria-label="Add CyPwn Repo">
                    <?php foreach ($sourceLinks as $link): ?>
                        <div class="repo-source-copy-card">
                            <?php repo_source_button($link); ?>
                            <button type="button" class="repo-copy-btn repo-copy-btn-small" data-copy-url="<?= e((string) $link['url']) ?>" aria-label="Copy <?= e((string) $link['label']) ?> source link">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                <span>Copy</span>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="source-info-panel" aria-labelledby="source-info-title">
                <div class="repo-modern-section-head">
                    <div>
                        <span>Overview</span>
                        <h2 id="source-info-title">CyPwn Repos (Source) for iPhone &amp; iPad</h2>
                    </div>
                </div>

                <div class="repo-modern-copy-grid">
                    <p>The CyPwn Repository is one of the best IPA sources for iPhone and iPad users. It supports iOS 15 through the latest iOS 26+, making it easy to install apps on both jailbroken and non-jailbroken (no-jailbreak) devices.</p>
                    <p>By adding the CyPwn Repo to your app, you can browse and install tweaked apps, jailbreak tools, emulators, utilities, modded apps, games, and other IPA files that aren't available on the App Store.</p>
                    <p>Whether you're looking for iOS customization tools, productivity apps, retro game emulators, or the latest jailbreak utilities, the CyPwn Repository gives you access to a large and regularly updated collection of trusted IPA downloads for iPhone and iPad.</p>
                </div>
            </section>

            <section class="repo-modern-repos" id="popular-repositories" aria-labelledby="popular-repositories-title">
                <div class="repo-modern-section-head">
                    <div>
                        <span>More Sources</span>
                        <h2 id="popular-repositories-title">Popular IPA Repositories</h2>
                    </div>
                    <p>Discover more IPA apps, jailbreak tools, emulators, tweaks, utilities, and games.</p>
                </div>

                <div class="repo-modern-repo-grid">
                    <?php foreach ($popularIpaRepos as $repo): ?>
                        <div class="repo-modern-repo-card">
                            <span class="repo-modern-repo-icon" aria-hidden="true"><?= e(strtoupper(substr($repo['name'], 0, 1))) ?></span>
                            <span>
                                <a href="<?= e($repo['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($repo['name']) ?></a>
                                <em><?= e($repo['url']) ?></em>
                            </span>
                            <button type="button" class="repo-copy-icon-btn" data-copy-url="<?= e($repo['url']) ?>" aria-label="Copy <?= e($repo['name']) ?> source URL">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        </div>
    </main>

    <nav class="apps-wire-tabs" aria-label="App navigation">
        <a href="<?= e(url('apps.php')) ?>" class="apps-wire-tab">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6.5L12 3l8 3.5-8 3.5-8-3.5zm0 5L12 8l8 3.5-8 3.5-8-3.5zm0 5L12 13l8 3.5-8 3.5-8-3.5z"/></svg>
            <span>Apps</span>
        </a>
        <a href="<?= e(url('sources.php')) ?>" class="apps-wire-tab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a13 13 0 010 18M12 3a13 13 0 000 18"/></svg>
            <span>Sources</span>
        </a>
        <a href="<?= e(url('repo.php')) ?>" class="apps-wire-tab apps-wire-tab-active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
            <span>Repo</span>
        </a>
        <a href="<?= e(url('index.php')) ?>" class="apps-wire-tab">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3.1L3 10.3V21h6v-6h6v6h6V10.3l-9-7.2z"/></svg>
            <span>Home</span>
        </a>
    </nav>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>

    <script>
        (function () {
            'use strict';

            function fallbackCopy(text) {
                var area = document.createElement('textarea');
                area.value = text;
                area.setAttribute('readonly', '');
                area.style.position = 'fixed';
                area.style.left = '-9999px';
                document.body.appendChild(area);
                area.select();
                try {
                    document.execCommand('copy');
                } finally {
                    document.body.removeChild(area);
                }
            }

            function setCopied(button) {
                var label = button.querySelector('span');
                var original = label ? label.textContent : '';
                button.classList.add('repo-copy-done');
                if (label) {
                    label.textContent = 'Copied';
                }
                window.setTimeout(function () {
                    button.classList.remove('repo-copy-done');
                    if (label) {
                        label.textContent = original || 'Copy';
                    }
                }, 1400);
            }

            document.querySelectorAll('[data-copy-url]').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    var url = button.getAttribute('data-copy-url') || '';
                    var copied = navigator.clipboard && window.isSecureContext
                        ? navigator.clipboard.writeText(url)
                        : Promise.resolve(fallbackCopy(url));

                    copied.then(function () {
                        setCopied(button);
                    }).catch(function () {
                        fallbackCopy(url);
                        setCopied(button);
                    });
                });
            });
        })();
    </script>
</body>
</html>
