<?php



declare(strict_types=1);



require dirname(__DIR__) . '/includes/auth.php';

require dirname(__DIR__) . '/includes/functions.php';

require dirname(__DIR__) . '/config/database.php';



require_admin();



$pageTitle = 'Manage Apps';



$apps = get_admin_apps($pdo);

$canReorder = apps_has_sort_order_column($pdo);

$orderScript = dirname(__DIR__) . '/assets/js/admin-order.js';
$pageScripts = $canReorder
    ? ['../assets/js/admin-order.js?v=' . (is_file($orderScript) ? (string) filemtime($orderScript) : '1')]
    : [];



require __DIR__ . '/partials/header.php';

?>



<div class="admin-toolbar">

    <h1>Apps</h1>

    <a href="app_add.php" class="btn btn-primary">+ Add App</a>

</div>



<?php if ($canReorder && !empty($apps)): ?>

    <p class="order-hint">Drag rows to set the order shown on the home page (within each store: IPA or TrollStore).</p>

    <p id="order-save-status" class="order-save-status" aria-live="polite"></p>

<?php endif; ?>



<?php if (empty($apps)): ?>

    <p class="muted">No apps yet. <a href="app_add.php">Add an app</a>.</p>

<?php else: ?>

    <div class="table-wrap">

        <table class="admin-table">

            <thead>

                <tr>

                    <?php if ($canReorder): ?>

                        <th class="col-order" scope="col"><span class="sr-only">Order</span></th>

                    <?php endif; ?>

                    <th>Icon</th>

                    <th>Name</th>

                    <th>Store</th>

                    <th>Version</th>

                    <th>Size</th>

                    <th>Category</th>

                    <th>Actions</th>

                </tr>

            </thead>

            <tbody

                id="apps-sortable"

                data-sortable="<?= $canReorder ? '1' : '0' ?>"

                data-csrf="<?= e(csrf_token()) ?>"

            >

                <?php foreach ($apps as $app): ?>

                    <tr

                        data-id="<?= (int) $app['id'] ?>"

                        data-store-type="<?= e($app['store_type']) ?>"

                    >

                        <?php if ($canReorder): ?>

                            <td class="col-order" title="Drag to reorder">

                                <span class="drag-handle" aria-hidden="true">⋮⋮</span>

                            </td>

                        <?php endif; ?>

                        <td>

                            <?php if ($app['icon']): ?>

                                <img src="../<?= e($app['icon']) ?>" alt="" class="table-icon" width="40" height="40">

                            <?php endif; ?>

                        </td>

                        <td><?= e($app['name']) ?></td>

                        <td><span class="badge badge-<?= e($app['store_type']) ?>"><?= e(ucfirst($app['store_type'])) ?></span></td>

                        <td><?= e($app['version']) ?></td>

                        <td><?= e(display_app_size($app)) ?></td>

                        <td><?= e($app['category']) ?></td>

                        <td class="actions">

                            <a href="app_edit.php?id=<?= (int) $app['id'] ?>" class="btn btn-sm">Edit</a>

                            <form method="post" action="delete.php" class="inline-form" onsubmit="return confirm('Delete this app?');">

                                <?= csrf_field() ?>

                                <input type="hidden" name="id" value="<?= (int) $app['id'] ?>">

                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>



<?php require __DIR__ . '/partials/footer.php'; ?>

