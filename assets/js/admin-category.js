(function () {
    const field = document.getElementById('category-field');
    if (!field) return;

    const hidden = document.getElementById('category');
    const existingPanel = document.getElementById('category-existing');
    const newPanel = document.getElementById('category-new-panel');
    const select = document.getElementById('category_select');
    const newInput = document.getElementById('category_new_input');
    const showNewBtn = document.getElementById('category-show-new');
    const showListBtn = document.getElementById('category-show-list');
    const form = field.closest('form');

    function syncFromSelect() {
        if (!hidden || !select) return;
        hidden.value = select.value;
    }

    function syncFromNew() {
        if (!hidden || !newInput) return;
        hidden.value = newInput.value.trim();
    }

    function showExisting() {
        existingPanel.classList.remove('hidden');
        newPanel.classList.add('hidden');
        syncFromSelect();
    }

    function showNew() {
        existingPanel.classList.add('hidden');
        newPanel.classList.remove('hidden');
        if (newInput) {
            if (!newInput.value && select && select.value) {
                newInput.value = select.value;
            }
            newInput.focus();
        }
        syncFromNew();
    }

    if (select) {
        select.addEventListener('change', syncFromSelect);
    }

    if (newInput) {
        newInput.addEventListener('input', syncFromNew);
    }

    if (showNewBtn) {
        showNewBtn.addEventListener('click', function (e) {
            e.preventDefault();
            showNew();
        });
    }

    if (showListBtn) {
        showListBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const name = newInput ? newInput.value.trim() : '';
            if (select && name) {
                let found = false;
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === name) {
                        select.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    const opt = document.createElement('option');
                    opt.value = name;
                    opt.textContent = name;
                    select.appendChild(opt);
                    select.value = name;
                }
            }
            showExisting();
        });
    }

    if (form) {
        form.addEventListener('submit', function () {
            if (newPanel.classList.contains('hidden')) {
                syncFromSelect();
            } else {
                syncFromNew();
            }
        });
    }

    if (!newPanel.classList.contains('hidden')) {
        syncFromNew();
    } else {
        syncFromSelect();
    }
})();
