<?php

declare(strict_types=1);

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$config = require __DIR__ . '/config/site.php';
$pageTitle = ($config['site_name'] ?? 'CyPwn') . ' - Sources';
$errors = [];
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Invalid security token. Please try again.';
    } else {
        $sourceAction = (string) ($_POST['source_action'] ?? 'save');
        $sourceId = (int) ($_POST['source_id'] ?? 0);

        if ($sourceAction === 'delete') {
            $flash = delete_source($pdo, $sourceId)
                ? 'Source deleted successfully.'
                : 'Could not delete source.';
        } elseif ($sourceAction === 'reorder') {
            $order = json_decode((string) ($_POST['source_order'] ?? '[]'), true);
            $flash = is_array($order) && reorder_sources($pdo, $order)
                ? 'Source order updated.'
                : 'Could not update source order.';
        } elseif ($sourceAction === 'move_up' || $sourceAction === 'move_down') {
            $moved = move_source($pdo, $sourceId, $sourceAction === 'move_up' ? 'up' : 'down');
            $flash = $moved ? 'Source order updated.' : 'Source is already in that position.';
        } else {
            $result = save_source_from_post($pdo, $_POST);
            if (!empty($result['ok'])) {
                $flash = ($result['mode'] ?? 'created') === 'updated'
                    ? 'Source updated successfully.'
                    : 'Source added successfully.';
            } else {
                $errors = $result['errors'] ?? ['general' => 'Could not save source.'];
            }
        }
    }
}

$sources = get_sources($pdo);
$sampleSource = [
    'id' => 0,
    'repository' => '9Animator',
    'source_url' => 'https://9ani.app/api/altstore',
    'notes' => 'Example source',
];
$displaySources = $sources !== [] ? $sources : [$sampleSource];
$sourceCount = count($sources);

function source_card(array $source, bool $isSample = false): void
{
    $repository = (string) ($source['repository'] ?? 'Repository');
    $sourceUrl = (string) ($source['source_url'] ?? '');
    $notes = trim((string) ($source['notes'] ?? ''));
    $host = parse_url($sourceUrl, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        $host = 'source';
    }
    $id = (int) ($source['id'] ?? 0);
    $detailUrl = $id > 0
        ? url('source_detail.php?id=' . $id)
        : url('source_detail.php?repository=' . rawurlencode($repository) . '&source_url=' . rawurlencode($sourceUrl));
    ?>
    <article
        class="source-manage-card<?= $isSample ? ' source-manage-card-sample' : '' ?>"
        data-id="<?= $id ?>"
        data-repository="<?= e($repository) ?>"
        data-source-url="<?= e($sourceUrl) ?>"
        data-notes="<?= e($notes) ?>"
        data-detail-url="<?= e($detailUrl) ?>"
        role="link"
        tabindex="0"
    >
        <span class="source-row-drag-handle" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M5 7h14M5 12h14M5 17h14"/></svg>
        </span>
        <span class="source-manage-icon" aria-hidden="true">
            <?= e(strtoupper(substr($repository, 0, 1) ?: 'S')) ?>
        </span>
        <span class="source-manage-body">
            <span class="source-manage-title-row">
                <strong><?= e($repository) ?></strong>
                <em><?= e($host) ?></em>
            </span>
            <span class="source-manage-url"><?= e($sourceUrl) ?></span>
            <span class="source-manage-note"><?= e($notes !== '' ? $notes : 'Ready to browse') ?></span>
        </span>
        <span class="source-row-meta" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
        </span>
        <?php if (!$isSample && $id > 0): ?>
            <form method="post" class="source-row-delete-form" data-confirm-delete>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="source_id" value="<?= $id ?>">
                <button type="submit" name="source_action" value="delete" class="source-row-delete-btn" aria-label="Delete <?= e($repository) ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M8 12h8"/></svg>
                </button>
            </form>
        <?php endif; ?>
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
    <link rel="stylesheet" href="<?= e(asset_url('assets/css/repo.css')) ?>">
</head>
<body class="home-page source-page source-manager-page">
    <main class="main-content source-main">
        <div class="source-wire-shell">
            <div class="source-manager-top-actions">
                <button type="button" class="source-action-btn source-action-btn-primary" id="source-add-button" aria-label="Add Source">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                </button>
                <button type="button" class="source-action-btn" id="source-manage-button" aria-label="Manage Sources">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                </button>
            </div>

            <?php if ($flash !== null): ?>
                <p class="source-form-flash"><?= e($flash) ?></p>
            <?php endif; ?>
            <?php if (!empty($errors['general'])): ?>
                <p class="source-form-error"><?= e($errors['general']) ?></p>
            <?php endif; ?>

            <section class="source-manager-section-head" aria-labelledby="source-list-title">
                <div>
                    <span>Repository List</span>
                    <h2 id="source-list-title"><?= $sourceCount > 0 ? 'Your Sources' : 'Sample Source' ?></h2>
                </div>
                <p><?= $sourceCount > 0 ? 'Tap a source to inspect it, or use the edit button for quick changes.' : 'Add your first source to replace this preview card.' ?></p>
            </section>

            <section class="source-manager-list" id="source-manager-list" aria-label="Saved sources">
                <?php foreach ($displaySources as $source): ?>
                    <?php source_card($source, $sources === []); ?>
                <?php endforeach; ?>
            </section>
            <form method="post" id="source-reorder-form" class="hidden">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="source_action" value="reorder">
                <input type="hidden" name="source_order" id="source-order-input" value="">
            </form>
        </div>
    </main>

    <nav class="apps-wire-tabs" aria-label="App navigation">
        <a href="<?= e(url('apps.php')) ?>" class="apps-wire-tab">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6.5L12 3l8 3.5-8 3.5-8-3.5zm0 5L12 8l8 3.5-8 3.5-8-3.5zm0 5L12 13l8 3.5-8 3.5-8-3.5z"/></svg>
            <span>Apps</span>
        </a>
        <a href="<?= e(url('sources.php')) ?>" class="apps-wire-tab apps-wire-tab-active">
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

    <div class="source-modal hidden" id="source-modal" aria-hidden="true">
        <form method="post" class="source-modal-panel" id="source-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="source_id" id="source-id" value="">
            <input type="hidden" name="source_action" value="save">
            <div class="source-modal-head">
                <strong id="source-modal-title">Add Source</strong>
                <button type="button" id="source-modal-close" aria-label="Close">&times;</button>
            </div>
            <label>
                <span>Source URL</span>
                <input type="url" name="source_url" id="source-url" placeholder="https://9ani.app/api/altstore" required>
            </label>
            <button type="submit" class="source-save-btn">Save Source</button>
        </form>
    </div>

    <?php require __DIR__ . '/partials/site_footer.php'; ?>

    <script src="<?= e(asset_url('assets/js/source-manager.js')) ?>"></script>
</body>
</html>
