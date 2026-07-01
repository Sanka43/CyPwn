(function () {
    const tbody = document.getElementById('apps-sortable');
    if (!tbody || tbody.dataset.sortable !== '1') {
        return;
    }

    const statusEl = document.getElementById('order-save-status');
    const csrfToken = tbody.dataset.csrf || '';
    let dragRow = null;
    let dragStore = '';
    let orderChanged = false;
    let saveTimer = null;

    function storeOf(row) {
        return (row.getAttribute('data-store-type') || row.dataset.storeType || '').trim().toLowerCase();
    }

    function rowIdsForStore(storeType) {
        return rowsForStore(storeType).map(function (tr) {
            return parseInt(tr.getAttribute('data-id') || tr.dataset.id, 10);
        }).filter(function (id) {
            return id > 0;
        });
    }

    function rowsForStore(storeType) {
        storeType = storeType.trim().toLowerCase();
        return Array.from(tbody.querySelectorAll('tr[data-id]')).filter(function (tr) {
            return storeOf(tr) === storeType;
        });
    }

    function setStatus(text, type) {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = text;
        statusEl.className = 'order-save-status' + (type ? ' order-save-status--' + type : '');
    }

    function saveOrderForStore(storeType) {
        storeType = storeType.trim().toLowerCase();
        const ids = rowIdsForStore(storeType);

        if (storeType !== 'ipa' && storeType !== 'trollstore') {
            return Promise.resolve({
                ok: false,
                data: { ok: false, error: 'Invalid store type' },
            });
        }

        if (ids.length === 0) {
            return Promise.resolve({
                ok: false,
                data: { ok: false, error: 'No apps found for this store' },
            });
        }

        return fetch('reorder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                csrf_token: csrfToken,
                store_type: storeType,
                ids: ids,
            }),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            });
    }

    function saveOrder() {
        const storeType = dragStore;
        if (storeType !== 'ipa' && storeType !== 'trollstore') {
            setStatus('Could not detect store type. Refresh and try again.', 'error');
            return;
        }

        setStatus('Saving order…', 'pending');
        saveOrderForStore(storeType)
            .then(function (result) {
                if (result.ok && result.data.ok) {
                    setStatus('Order saved. Home page updated.', 'success');
                } else {
                    setStatus(result.data.error || 'Failed to save order', 'error');
                }
            })
            .catch(function () {
                setStatus('Failed to save order', 'error');
            });
    }

    function queueSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveOrder, 300);
    }

    tbody.addEventListener('dragover', function (e) {
        e.preventDefault();
    });

    tbody.querySelectorAll('tr[data-id]').forEach(function (row) {
        row.setAttribute('draggable', 'true');

        row.addEventListener('dragstart', function (e) {
            dragRow = row;
            dragStore = storeOf(row);
            orderChanged = false;
            row.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', String(row.getAttribute('data-id') || ''));
        });

        row.addEventListener('dragend', function () {
            row.classList.remove('is-dragging');
            tbody.querySelectorAll('tr').forEach(function (tr) {
                tr.classList.remove('drag-over');
            });
            if (orderChanged && dragStore) {
                queueSave();
            }
            dragRow = null;
        });

        row.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!dragRow || dragRow === row) {
                return;
            }
            if (storeOf(dragRow) !== storeOf(row)) {
                return;
            }
            e.dataTransfer.dropEffect = 'move';
            row.classList.add('drag-over');
            const rect = row.getBoundingClientRect();
            const after = e.clientY > rect.top + rect.height / 2;
            const parent = row.parentNode;
            if (after) {
                if (dragRow !== row.nextSibling) {
                    parent.insertBefore(dragRow, row.nextSibling);
                    orderChanged = true;
                }
            } else if (dragRow !== row) {
                parent.insertBefore(dragRow, row);
                orderChanged = true;
            }
        });

        row.addEventListener('dragleave', function () {
            row.classList.remove('drag-over');
        });

        row.addEventListener('drop', function (e) {
            e.preventDefault();
            row.classList.remove('drag-over');
            orderChanged = true;
        });
    });
})();
