<?php

declare(strict_types=1);

require __DIR__ . '/includes/functions.php';

$config = require __DIR__ . '/config/site.php';
$pageTitle = ($config['site_name'] ?? 'CyPwn') . ' — Repo Packages';
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
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/repo.css')) ?>">
</head>
<body class="repo-page">
    <?php require __DIR__ . '/partials/site_header.php'; ?>

    <main class="main-content repo-main">
        <div class="repo-panel">
            <button type="button" class="repo-add-toggle" id="repo-add-toggle" aria-expanded="true">
                <span>Add our Repo</span>
                <svg class="toggle-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </button>
            <div class="repo-add-body open" id="repo-add-body">
                <div class="repo-add-list">
                    <a href="https://cydia.saurik.com/api/share#?source=https://repo.cypwn.xyz/"
                       class="repo-add-wrap btn-cydia">
                        <span class="repo-add-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/></svg>
                            Cydia
                        </span>
                    </a>
                    <a href="installer://add/https://repo.cypwn.xyz/"
                       class="repo-add-wrap btn-installer">
                        <span class="repo-add-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Installer
                        </span>
                    </a>
                    <a href="sileo://source/https://repo.cypwn.xyz/"
                       class="repo-add-wrap btn-sileo">
                        <span class="repo-add-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                            Sileo
                        </span>
                    </a>
                    <a href="zbra://sources/add/https://repo.cypwn.xyz/"
                       class="repo-add-wrap btn-zebra">
                        <span class="repo-add-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                            Zebra
                        </span>
                    </a>
                </div>
                <button type="button" class="show-sections-btn" id="show-sections-btn">Show Sections</button>
            </div>
        </div>

        <section class="search-section repo-search">
            <div class="search-row">
                <div class="search-wrap">
                    <span class="search-icon" aria-hidden="true">🔍</span>
                    <input type="search" id="search-input" class="search-input"
                           placeholder="Search all Packages..." autocomplete="off">
                    <button type="button" class="search-clear" id="search-clear" aria-label="Clear search">×</button>
                </div>
            </div>
            <p class="search-results-label hidden" id="search-results-label"></p>
        </section>

        <div id="pkg-loading" class="repo-loading">Loading packages…</div>

        <section class="apps-section repo-apps-section">
            <div id="pkg-catalog" class="hidden">
                <div id="pkg-sections"></div>
                <div id="pkg-flat" class="hidden">
                    <h2 class="section-heading" id="flat-heading"></h2>
                    <div id="pkg-grid"></div>
                </div>
                <p class="no-results hidden" id="no-results">No packages match your search.</p>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>

    <nav class="goto-bar hidden" id="goto-section" aria-label="Go to section">
        <button type="button" class="goto-bar-toggle" id="goto-bar-toggle" aria-expanded="false">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            Go to Section
        </button>
        <div class="goto-bar-menu hidden" id="goto-section-links"></div>
    </nav>

    <div class="modal-overlay hidden" id="modal-overlay" aria-hidden="true">
        <div class="modal" id="modal" role="dialog" aria-labelledby="modal-title" aria-modal="true">
            <button type="button" class="modal-close" id="modal-close" aria-label="Close">×</button>
            <div class="modal-header">
                <img src="" alt="" class="pkg-modal-icon" id="modal-icon" width="80" height="80"
                     onerror="this.style.display='none'">
                <div class="modal-header-text">
                    <h2 id="modal-title"></h2>
                    <p class="modal-developer" id="modal-developer"></p>
                    <p class="modal-version-line" id="modal-version"></p>
                    <div class="pkg-modal-tags" id="modal-tags"></div>
                </div>
            </div>
            <div class="modal-actions">
                <a href="#" class="btn-download" id="modal-link" target="_blank" rel="noopener noreferrer">
                    View on Repo →
                </a>
            </div>
        </div>
    </div>

    <script>
        window.CYPWN_REPO_DATA = <?= json_encode(asset_url('api/repo-packages.php'), JSON_UNESCAPED_SLASHES) ?>;
        window.CYPWN_REPO_IMG_BASE = <?= json_encode(rtrim(asset_url('assets/repo/'), '/') . '/', JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= e(asset_url('assets/js/repo.js')) ?>"></script>
</body>
</html>
