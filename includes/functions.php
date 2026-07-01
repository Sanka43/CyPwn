<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $config = require dirname(__DIR__) . '/config/site.php';
    $configured = rtrim((string) ($config['base_path'] ?? ''), '/');
    if ($configured !== '') {
        $base = $configured;
        return $base;
    }

    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($docRoot !== '') {
        $docRoot = str_replace('\\', '/', (string) (realpath($docRoot) ?: $docRoot));
        $projectRoot = str_replace('\\', '/', (string) (realpath(dirname(__DIR__)) ?: dirname(__DIR__)));
        if ($projectRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
            $base = rtrim(substr($projectRoot, strlen($docRoot)), '/');
            return $base;
        }
    }

    $base = '';
    return $base;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $base = base_path();
    return $base === '' ? '/' . $path : $base . '/' . $path;
}

function asset_url(string $relativePath): string
{
    return url($relativePath);
}

function page_title(): string
{
    $config = require dirname(__DIR__) . '/config/site.php';

    return (string) ($config['page_title'] ?? $config['site_name'] ?? '');
}

function meta_description_tag(): string
{
    $config = require dirname(__DIR__) . '/config/site.php';
    $description = trim((string) ($config['meta_description'] ?? ''));
    if ($description === '') {
        return '';
    }

    return '<meta name="description" content="' . e($description) . '">';
}

function favicon_tags(): string
{
    $config = require dirname(__DIR__) . '/config/site.php';
    $icon = (string) ($config['favicon'] ?? $config['logo'] ?? 'assets/img/logo.svg');
    $href = e(asset_url($icon));
    $type = match (true) {
        str_ends_with($icon, '.svg') => 'image/svg+xml',
        str_ends_with($icon, '.ico') => 'image/x-icon',
        default => 'image/png',
    };

    return '<link rel="icon" href="' . $href . '" type="' . $type . '">' . "\n"
        . '    <link rel="apple-touch-icon" href="' . $href . '">';
}

function analytics_tags(): string
{
    $config = require dirname(__DIR__) . '/config/site.php';
    $id = trim((string) ($config['google_analytics_id'] ?? ''));
    if ($id === '') {
        return '';
    }

    $id = e($id);

    return '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $id . '"></script>' . "\n"
        . '    <script>' . "\n"
        . '      window.dataLayer = window.dataLayer || [];' . "\n"
        . '      function gtag(){dataLayer.push(arguments);}' . "\n"
        . '      gtag(\'js\', new Date());' . "\n"
        . '      gtag(\'config\', \'' . $id . '\');' . "\n"
        . '    </script>';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function decode_screenshots(?string $json): array
{
    if ($json === null || $json === '') {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function encode_screenshots(array $paths): string
{
    return json_encode(array_values($paths), JSON_UNESCAPED_SLASHES);
}

function store_upload_dir(string $storeType, string $subdir): string
{
    $storeType = $storeType === 'trollstore' ? 'trollstore' : 'ipa';
    $subdir = in_array($subdir, ['icons', 'screenshots'], true) ? $subdir : 'icons';
    return dirname(__DIR__) . "/assets/{$storeType}/{$subdir}";
}

function store_upload_web_path(string $storeType, string $subdir, string $filename): string
{
    $storeType = $storeType === 'trollstore' ? 'trollstore' : 'ipa';
    $subdir = in_array($subdir, ['icons', 'screenshots'], true) ? $subdir : 'icons';
    return "assets/{$storeType}/{$subdir}/{$filename}";
}

function allowed_image_types(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
}

function upload_image(array $file, string $storeType, string $subdir): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return null;
    }

    $maxSize = 5 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSize) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = allowed_image_types();
    if (!isset($allowed[$mime])) {
        return null;
    }

    $ext = $allowed[$mime];
    $filename = uniqid('img_', true) . '.' . $ext;
    $dir = store_upload_dir($storeType, $subdir);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $dest = $dir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    return store_upload_web_path($storeType, $subdir, $filename);
}

function upload_multiple_images(array $files, string $storeType): array
{
    $paths = [];
    $names = $files['name'] ?? [];
    if (!is_array($names)) {
        return $paths;
    }

    $count = count($names);
    for ($i = 0; $i < $count; $i++) {
        $file = [
            'name' => $files['name'][$i] ?? '',
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
        $path = upload_image($file, $storeType, 'screenshots');
        if ($path !== null) {
            $paths[] = $path;
        }
    }
    return $paths;
}

function delete_file_if_exists(string $relativePath): void
{
    if ($relativePath === '') {
        return;
    }
    $full = dirname(__DIR__) . '/' . ltrim(str_replace(['../', '..\\'], '', $relativePath), '/');
    if (is_file($full)) {
        unlink($full);
    }
}

function delete_app_files(array $app): void
{
    if (!empty($app['icon'])) {
        delete_file_if_exists($app['icon']);
    }
    foreach (decode_screenshots($app['screenshots'] ?? null) as $shot) {
        delete_file_if_exists($shot);
    }
}

function display_app_size(array $app): string
{
    $manual = trim($app['app_size'] ?? '');
    if ($manual !== '') {
        return $manual;
    }
    return format_file_size($app['download_url'] ?? null);
}

function format_file_size(?string $downloadUrl): string
{
    if ($downloadUrl === null || $downloadUrl === '') {
        return '—';
    }
    if (!preg_match('#^https?://#i', $downloadUrl)) {
        $local = dirname(__DIR__) . '/' . ltrim($downloadUrl, '/');
        if (is_file($local)) {
            $bytes = filesize($local);
            return format_bytes((int) $bytes);
        }
        return '—';
    }
    return '—';
}

function format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / 1048576, 1) . ' MB';
}

function validate_download_url(string $url): bool
{
    if ($url === '') {
        return false;
    }
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return true;
    }
    return preg_match('#^assets/#', $url) === 1;
}

function get_app_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM apps WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function apps_table_exists(PDO $pdo): bool
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'apps'");
        $exists = $stmt->fetch() !== false;
    } catch (PDOException) {
        $exists = false;
    }
    return $exists;
}

function apps_has_sort_order_column(PDO $pdo): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    if (!apps_table_exists($pdo)) {
        $has = false;
        return $has;
    }
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM apps LIKE 'sort_order'");
        $has = $stmt->fetch() !== false;
    } catch (PDOException) {
        $has = false;
    }
    return $has;
}

function apps_public_order_sql(PDO $pdo): string
{
    return apps_has_sort_order_column($pdo)
        ? 'sort_order ASC, name ASC'
        : 'version_date DESC, name ASC';
}

function apps_admin_order_sql(PDO $pdo): string
{
    return apps_has_sort_order_column($pdo)
        ? 'store_type ASC, sort_order ASC, name ASC'
        : 'store_type ASC, version_date DESC, name ASC';
}

function get_apps_by_store(PDO $pdo, string $storeType): array
{
    $storeType = $storeType === 'trollstore' ? 'trollstore' : 'ipa';
    $orderBy = apps_public_order_sql($pdo);
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE store_type = ? ORDER BY {$orderBy}");
    $stmt->execute([$storeType]);
    return $stmt->fetchAll();
}

function get_featured_apps(PDO $pdo, int $limit = 56): array
{
    $limit = max(1, min($limit, 100));
    $orderBy = apps_public_order_sql($pdo);
    $fetchLimit = min($limit * 4, 400);
    $stmt = $pdo->prepare("SELECT id, name, icon FROM apps ORDER BY {$orderBy} LIMIT ?");
    $stmt->bindValue(1, $fetchLimit, PDO::PARAM_INT);
    $stmt->execute();

    $seen = [];
    $featured = [];
    foreach ($stmt->fetchAll() as $app) {
        $key = strtolower(trim((string) ($app['name'] ?? '')));
        if ($key === '' || isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $featured[] = $app;
        if (count($featured) >= $limit) {
            break;
        }
    }

    return $featured;
}

function get_distinct_categories(PDO $pdo, ?string $storeType = null): array
{
    if ($storeType === 'ipa' || $storeType === 'trollstore') {
        $stmt = $pdo->prepare(
            "SELECT DISTINCT category FROM apps
             WHERE store_type = ? AND TRIM(category) <> '' ORDER BY category ASC"
        );
        $stmt->execute([$storeType]);
    } else {
        $stmt = $pdo->query(
            "SELECT DISTINCT category FROM apps WHERE TRIM(category) <> '' ORDER BY category ASC"
        );
    }
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return array_values(array_map('strval', $rows));
}

function get_admin_apps(PDO $pdo, ?string $storeType = null, ?string $category = null): array
{
    $sql = 'SELECT * FROM apps WHERE 1=1';
    $params = [];

    if ($storeType === 'ipa' || $storeType === 'trollstore') {
        $sql .= ' AND store_type = ?';
        $params[] = $storeType;
    }

    if ($category !== null && $category !== '') {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }

    $sql .= ' ORDER BY ' . apps_admin_order_sql($pdo);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_next_sort_order(PDO $pdo, string $storeType, ?string $category = null): int
{
    if (!apps_has_sort_order_column($pdo)) {
        return 0;
    }
    $storeType = $storeType === 'trollstore' ? 'trollstore' : 'ipa';
    $stmt = $pdo->prepare(
        'SELECT COALESCE(MAX(sort_order), 0) FROM apps WHERE store_type = ?'
    );
    $stmt->execute([$storeType]);
    return ((int) $stmt->fetchColumn()) + 10;
}

/**
 * @param array<int, int> $orderedIds
 */
function save_apps_sort_order(
    PDO $pdo,
    string $storeType,
    array $orderedIds,
    ?string $category = null
): bool {
    if (!apps_has_sort_order_column($pdo)) {
        return false;
    }
    $storeType = $storeType === 'trollstore' ? 'trollstore' : 'ipa';
    $category = $category !== null ? trim($category) : '';

    if ($orderedIds === []) {
        return false;
    }

    $orderedIds = array_values(array_map('intval', $orderedIds));
    $placeholders = implode(',', array_fill(0, count($orderedIds), '?'));

    if ($category !== '') {
        $stmt = $pdo->prepare(
            "SELECT id FROM apps WHERE store_type = ? AND category = ? AND id IN ({$placeholders})"
        );
        $stmt->execute(array_merge([$storeType, $category], $orderedIds));
        $update = $pdo->prepare(
            'UPDATE apps SET sort_order = ? WHERE id = ? AND store_type = ? AND category = ?'
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT id FROM apps WHERE store_type = ? AND id IN ({$placeholders})"
        );
        $stmt->execute(array_merge([$storeType], $orderedIds));
        $update = $pdo->prepare(
            'UPDATE apps SET sort_order = ? WHERE id = ? AND store_type = ?'
        );
    }

    $validIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    if (count($validIds) !== count($orderedIds)) {
        return false;
    }

    $order = 10;
    foreach ($orderedIds as $id) {
        if ($category !== '') {
            $update->execute([$order, $id, $storeType, $category]);
        } else {
            $update->execute([$order, $id, $storeType]);
        }
        $order += 10;
    }

    return true;
}

/**
 * @param array<int, array<string, mixed>> $apps
 * @return array<string, array<int, array<string, mixed>>>
 */
function group_apps_by_category(array $apps): array
{
    $grouped = [];
    foreach ($apps as $app) {
        $category = trim((string) ($app['category'] ?? ''));
        if ($category === '') {
            $category = 'Uncategorized';
        }
        $grouped[$category][] = $app;
    }

    uksort($grouped, static function (string $a, string $b) use ($apps): int {
        $minOrder = static function (string $cat) use ($apps): int {
            $min = PHP_INT_MAX;
            foreach ($apps as $app) {
                $appCat = trim((string) ($app['category'] ?? ''));
                if ($appCat === '') {
                    $appCat = 'Uncategorized';
                }
                if ($appCat === $cat) {
                    $min = min($min, (int) ($app['sort_order'] ?? 0));
                }
            }
            return $min === PHP_INT_MAX ? 0 : $min;
        };
        $cmp = $minOrder($a) <=> $minOrder($b);
        if ($cmp !== 0) {
            return $cmp;
        }
        return strnatcasecmp($a, $b);
    });

    if (isset($grouped['Uncategorized'])) {
        $uncategorized = $grouped['Uncategorized'];
        unset($grouped['Uncategorized']);
        $grouped['Uncategorized'] = $uncategorized;
    }

    return $grouped;
}
