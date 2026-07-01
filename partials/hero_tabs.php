<?php

declare(strict_types=1);

/** @var array<string, mixed> $config */
/** @var string $heroTab ipa|trollstore */
$heroTab = $heroTab ?? 'ipa';
?>
<section class="hero">
    <div class="tabs" role="tablist">
        <a href="<?= e(url('store.php?tab=ipa')) ?>" class="tab <?= $heroTab === 'ipa' ? 'tab-active' : '' ?>" role="tab" aria-selected="<?= $heroTab === 'ipa' ? 'true' : 'false' ?>">IPAs</a>
        <a href="<?= e(url('store.php?tab=trollstore')) ?>" class="tab <?= $heroTab === 'trollstore' ? 'tab-active' : '' ?>" role="tab" aria-selected="<?= $heroTab === 'trollstore' ? 'true' : 'false' ?>">TrollStore</a>
    </div>
</section>
