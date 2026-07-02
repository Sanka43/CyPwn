(function () {
    'use strict';

    const searchInput = document.getElementById('source-search-input');
    const searchClear = document.getElementById('source-search-clear');
    const chips = document.querySelectorAll('.source-wire-chip');
    const cards = document.querySelectorAll('.source-package-card');
    const noResults = document.getElementById('source-no-results');
    let activeCategory = 'all';

    function applyFilters() {
        const q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        let visible = 0;

        cards.forEach(function (card) {
            const hay = card.dataset.search || '';
            const categories = card.dataset.sourceCategory || '';
            const matchesSearch = q === '' || hay.indexOf(q) !== -1;
            const matchesCategory = activeCategory === 'all' || categories.indexOf(activeCategory) !== -1;
            const show = matchesSearch && matchesCategory;

            card.classList.toggle('hidden', !show);
            if (show) visible++;
        });

        if (searchClear) {
            searchClear.classList.toggle('visible', q.length > 0);
        }
        if (noResults) {
            noResults.classList.toggle('hidden', visible > 0 || cards.length === 0);
        }
    }

    function setActiveChip(chip) {
        activeCategory = chip.dataset.category || 'all';
        chips.forEach(function (btn) {
            btn.classList.toggle('source-wire-chip-active', btn === chip);
        });
        applyFilters();
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    if (searchClear && searchInput) {
        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            applyFilters();
            searchInput.focus();
        });
    }

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            setActiveChip(chip);
        });
    });
})();
