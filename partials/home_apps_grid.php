<?php

declare(strict_types=1);

/** @var array<int, array<string, mixed>> $featuredApps */
?>
<section class="home-apps-section" id="apps" aria-labelledby="home-apps-heading">
    <h2 class="home-apps-heading" id="home-apps-heading">
        <span class="home-apps-heading-bold">Apps.</span>
        <span class="home-apps-heading-muted">Community top.</span>
    </h2>

    <?php if ($featuredApps === []): ?>
        <p class="home-apps-empty">No apps yet. Check back soon.</p>
    <?php else: ?>
        <div class="home-apps-grid">
            <?php foreach ($featuredApps as $app): ?>
                <a href="<?= e(url('app.php?id=' . (int) $app['id'])) ?>" class="home-app-item">
                    <div class="home-app-icon">
                        <?php if (!empty($app['icon'])): ?>
                            <img src="<?= e($app['icon']) ?>" alt="" width="52" height="52" loading="lazy">
                        <?php else: ?>
                            <span class="home-app-icon-placeholder" aria-hidden="true"></span>
                        <?php endif; ?>
                    </div>
                    <span class="home-app-name"><?= e($app['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="home-apps-footer">
            <a href="<?= e(url('store.php')) ?>" class="home-see-all-btn">See all</a>
        </div>
    <?php endif; ?>
</section>
