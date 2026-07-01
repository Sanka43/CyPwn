    <?php

    declare(strict_types=1);

    /** @var array|null $app */
    /** @var bool $isEdit */

    $app = $app ?? null;
    $isEdit = $isEdit ?? false;
    $errors = $errors ?? [];
    $old = $old ?? [];

    $value = function (string $key, string $default = '') use ($app, $old): string {
        if (isset($old[$key])) {
            return (string) $old[$key];
        }
        if ($app && isset($app[$key])) {
            return (string) $app[$key];
        }
        return $default;
    };

    $storeType = $value('store_type', 'ipa');
    $categories = $categories ?? [];
    $currentCategory = $value('category');
    $categoryInList = $currentCategory === '' || in_array($currentCategory, $categories, true);
    $showCategoryNew = $currentCategory !== '' && !$categoryInList;
    ?>
    <form method="post" enctype="multipart/form-data" class="admin-form">
        <?= csrf_field() ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <div class="form-row">
            <label for="store_type">Store Type *</label>
            <select name="store_type" id="store_type" required>
                <option value="ipa" <?= $storeType === 'ipa' ? 'selected' : '' ?>>IPA</option>
                <option value="trollstore" <?= $storeType === 'trollstore' ? 'selected' : '' ?>>TrollStore</option>
            </select>
            <?php if (!empty($errors['store_type'])): ?><span class="field-error"><?= e($errors['store_type']) ?></span><?php endif; ?>
        </div>

        <div class="form-row">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" value="<?= e($value('name')) ?>" required>
            <?php if (!empty($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>

        <div class="form-row">
            <label for="icon">Icon <?= $isEdit ? '' : '*' ?></label>
            <?php if ($isEdit && !empty($app['icon'])): ?>
                <div class="media-preview icon-preview" id="icon-preview">
                    <div class="media-item">
                        <img src="../<?= e($app['icon']) ?>" alt="Current icon" width="64" height="64">
                        <label class="media-remove">
                            <input type="checkbox" name="remove_icon" value="1" class="media-remove-input">
                            Remove
                        </label>
                    </div>
                </div>
                <span class="field-hint">Upload a new file to replace the icon, or check Remove to delete it.</span>
            <?php endif; ?>
            <input type="file" id="icon" name="icon" accept="image/jpeg,image/png,image/gif,image/webp" <?= $isEdit ? '' : 'required' ?>>
            <?php if (!empty($errors['icon'])): ?><span class="field-error"><?= e($errors['icon']) ?></span><?php endif; ?>
        </div>

        <div class="form-row">
            <label for="developer_name">Developer Name</label>
            <input type="text" id="developer_name" name="developer_name" value="<?= e($value('developer_name')) ?>">
        </div>

        <div class="form-row">
            <label for="subtitle">Subtitle</label>
            <input type="text" id="subtitle" name="subtitle" value="<?= e($value('subtitle')) ?>">
        </div>

        <div class="form-row category-field" id="category-field">
            <label for="category_select">Category</label>
            <input type="hidden" name="category" id="category" value="<?= e($currentCategory) ?>">

            <div id="category-existing" class="category-panel<?= $showCategoryNew ? ' hidden' : '' ?>">
                <select id="category_select">
                    <option value="">— Select category —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>"<?= $currentCategory === $cat ? ' selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($categories)): ?>
                    <span class="field-hint">No categories yet. Use <strong>New</strong> below to add one.</span>
                <?php endif; ?>
                <p class="field-actions">
                    <button type="button" class="link-btn" id="category-show-new">New</button>
                </p>
            </div>

            <div id="category-new-panel" class="category-panel<?= $showCategoryNew ? '' : ' hidden' ?>">
                <input type="text" id="category_new_input" value="<?= $showCategoryNew ? e($currentCategory) : '' ?>" placeholder="New category name" autocomplete="off">
                <p class="field-actions">
                    <button type="button" class="link-btn" id="category-show-list">Choose from list</button>
                </p>
            </div>
        </div>

        <div class="form-row two-col">
            <div>
                <label for="version">Version *</label>
                <input type="text" id="version" name="version" value="<?= e($value('version')) ?>" required>
                <?php if (!empty($errors['version'])): ?><span class="field-error"><?= e($errors['version']) ?></span><?php endif; ?>
            </div>
            <div>
                <label for="version_date">Version Date</label>
                <input type="date" id="version_date" name="version_date" value="<?= e($value('version_date')) ?>">
            </div>
        </div>

        <div class="form-row">
            <label for="app_size">App Size</label>
            <input type="text" id="app_size" name="app_size" value="<?= e($value('app_size')) ?>" placeholder="e.g. 125.4 MB">
            <span class="field-hint">Shown on the store. Leave empty to auto-detect from local download files only.</span>
        </div>

        <div class="form-row">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="6"><?= e($value('description')) ?></textarea>
        </div>

        <?php
        $downloadUrlValue = $value('download_url');
        if ($downloadUrlValue === '#') {
            $downloadUrlValue = '';
        }
        ?>
        <div class="form-row">
            <label for="download_url">Download URL<?= $isEdit ? '' : ' *' ?></label>
            <input type="url" id="download_url" name="download_url" value="<?= e($downloadUrlValue) ?>"<?= $isEdit ? '' : ' required' ?> placeholder="https://...">
            <?php if ($isEdit): ?>
                <span class="field-hint">Leave empty if there is no download link (saved as #).</span>
            <?php endif; ?>
            <?php if (!empty($errors['download_url'])): ?><span class="field-error"><?= e($errors['download_url']) ?></span><?php endif; ?>
        </div>

        <div class="form-row">
            <label for="screenshots">Screenshots<?= $isEdit ? ' (add or remove)' : '' ?></label>
            <?php if ($isEdit && !empty($app['screenshots'])): ?>
                <?php $existingShots = decode_screenshots($app['screenshots']); ?>
                <?php if (!empty($existingShots)): ?>
                    <div class="media-preview screenshots-preview" id="screenshots-preview">
                        <?php foreach ($existingShots as $shot): ?>
                            <div class="media-item">
                                <img src="../<?= e($shot) ?>" alt="Screenshot" width="80" height="80">
                                <label class="media-remove">
                                    <input type="checkbox" name="remove_screenshot[]" value="<?= e($shot) ?>" class="media-remove-input">
                                    Remove
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <span class="field-hint">Check Remove on screenshots you want to delete. Upload new files below to add more.</span>
                <?php endif; ?>
            <?php endif; ?>
            <input type="file" id="screenshots" name="screenshots[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update App' : 'Create App' ?></button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
