<?php

declare(strict_types=1);

function validate_app_post(array $post, bool $isEdit, bool $hasIcon): array
{
    $errors = [];
    $storeType = $post['store_type'] ?? 'ipa';
    if (!in_array($storeType, ['ipa', 'trollstore'], true)) {
        $errors['store_type'] = 'Invalid store type.';
    }
    if (trim($post['name'] ?? '') === '') {
        $errors['name'] = 'Name is required.';
    }
    if (!$isEdit && !$hasIcon) {
        $errors['icon'] = 'Icon is required.';
    }
    if (trim($post['version'] ?? '') === '') {
        $errors['version'] = 'Version is required.';
    }
    $downloadUrl = trim($post['download_url'] ?? '');
    if ($isEdit && $downloadUrl === '') {
        // Edit: empty field is saved as "#" in the database.
    } elseif ($downloadUrl === '' || !validate_download_url($downloadUrl)) {
        $errors['download_url'] = 'Valid download URL is required.';
    }
    return $errors;
}

function save_app_from_post(PDO $pdo, array $post, array $files, ?array $existing = null): array
{
    $isEdit = $existing !== null;
    $storeType = in_array($post['store_type'] ?? '', ['ipa', 'trollstore'], true)
        ? $post['store_type']
        : 'ipa';

    $iconPath = $existing['icon'] ?? '';
    if ($isEdit && !empty($post['remove_icon'])) {
        if ($iconPath !== '') {
            delete_file_if_exists($iconPath);
        }
        $iconPath = '';
    }
    if (!empty($files['icon']['tmp_name']) && ($files['icon']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($iconPath !== '') {
            delete_file_if_exists($iconPath);
        }
        $uploaded = upload_image($files['icon'], $storeType, 'icons');
        if ($uploaded === null) {
            return ['ok' => false, 'errors' => ['icon' => 'Failed to upload icon. Use JPG, PNG, GIF, or WebP under 5MB.']];
        }
        $iconPath = $uploaded;
    }

    $screenshots = $existing ? decode_screenshots($existing['screenshots'] ?? null) : [];
    if ($isEdit && !empty($post['remove_screenshot']) && is_array($post['remove_screenshot'])) {
        $toRemove = array_unique(array_filter($post['remove_screenshot'], 'is_string'));
        $screenshots = array_values(array_filter($screenshots, static function (string $shot) use ($toRemove): bool {
            if (in_array($shot, $toRemove, true)) {
                delete_file_if_exists($shot);
                return false;
            }
            return true;
        }));
    }
    if (!empty($files['screenshots']['name'][0] ?? $files['screenshots']['name'] ?? '')) {
        $newShots = upload_multiple_images($files['screenshots'], $storeType);
        $screenshots = array_merge($screenshots, $newShots);
    }

    $versionDate = trim($post['version_date'] ?? '');
    if ($versionDate === '') {
        $versionDate = null;
    }

    $downloadUrl = trim($post['download_url'] ?? '');
    if ($isEdit && $downloadUrl === '') {
        $downloadUrl = '#';
    }

    $data = [
        'store_type' => $storeType,
        'name' => trim($post['name'] ?? ''),
        'icon' => $iconPath,
        'developer_name' => trim($post['developer_name'] ?? ''),
        'subtitle' => trim($post['subtitle'] ?? ''),
        'category' => trim($post['category'] ?? ''),
        'version' => trim($post['version'] ?? ''),
        'app_size' => trim($post['app_size'] ?? ''),
        'version_date' => $versionDate,
        'description' => trim($post['description'] ?? ''),
        'download_url' => $downloadUrl,
        'screenshots' => encode_screenshots($screenshots),
    ];

    if ($isEdit) {
        $stmt = $pdo->prepare(
            'UPDATE apps SET store_type=?, name=?, icon=?, developer_name=?, subtitle=?, category=?,
             version=?, app_size=?, version_date=?, description=?, download_url=?, screenshots=? WHERE id=?'
        );
        $stmt->execute([
            $data['store_type'], $data['name'], $data['icon'], $data['developer_name'],
            $data['subtitle'], $data['category'], $data['version'], $data['app_size'], $data['version_date'],
            $data['description'], $data['download_url'], $data['screenshots'],
            (int) $existing['id'],
        ]);
        return ['ok' => true, 'id' => (int) $existing['id']];
    }

    if (apps_has_sort_order_column($pdo)) {
        $sortOrder = get_next_sort_order($pdo, $data['store_type'], $data['category']);
        $stmt = $pdo->prepare(
            'INSERT INTO apps (store_type, name, icon, developer_name, subtitle, category, sort_order, version,
             app_size, version_date, description, download_url, screenshots)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['store_type'], $data['name'], $data['icon'], $data['developer_name'],
            $data['subtitle'], $data['category'], $sortOrder, $data['version'], $data['app_size'],
            $data['version_date'], $data['description'], $data['download_url'], $data['screenshots'],
        ]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO apps (store_type, name, icon, developer_name, subtitle, category, version, app_size,
             version_date, description, download_url, screenshots) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $data['store_type'], $data['name'], $data['icon'], $data['developer_name'],
            $data['subtitle'], $data['category'], $data['version'], $data['app_size'],
            $data['version_date'], $data['description'], $data['download_url'], $data['screenshots'],
        ]);
    }
    return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
}
