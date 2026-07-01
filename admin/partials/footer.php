    </main>
    <script src="../assets/js/admin-category.js" defer></script>
    <script src="../assets/js/admin-media.js" defer></script>
    <?php if (!empty($pageScripts)): ?>
        <?php foreach ($pageScripts as $scriptSrc): ?>
            <script src="<?= e($scriptSrc) ?>" defer></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
