(function () {
    document.querySelectorAll('.media-remove-input').forEach(function (input) {
        var item = input.closest('.media-item');
        if (!item) return;

        function syncState() {
            item.classList.toggle('marked-remove', input.checked);
        }

        input.addEventListener('change', syncState);
        syncState();
    });
})();
