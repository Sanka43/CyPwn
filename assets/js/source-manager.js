(function () {
    'use strict';

    const modal = document.getElementById('source-modal');
    const addButton = document.getElementById('source-add-button');
    const manageButton = document.getElementById('source-manage-button');
    const closeButton = document.getElementById('source-modal-close');
    const title = document.getElementById('source-modal-title');
    const idInput = document.getElementById('source-id');
    const urlInput = document.getElementById('source-url');
    const list = document.getElementById('source-manager-list');
    const reorderForm = document.getElementById('source-reorder-form');
    const orderInput = document.getElementById('source-order-input');
    const cards = Array.from(document.querySelectorAll('.source-manage-card'));
    let isEditMode = false;
    let draggedCard = null;
    let pointerDragging = false;
    let pointerMoved = false;

    function openModal(card) {
        const isEdit = Boolean(card);
        title.textContent = isEdit ? 'Edit Source' : 'Add Source';
        idInput.value = isEdit && !card.classList.contains('source-manage-card-sample')
            ? card.dataset.id || ''
            : '';
        urlInput.value = isEdit ? card.dataset.sourceUrl || '' : '';

        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        urlInput.focus();
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function setEditMode(enabled) {
        isEditMode = enabled;
        document.body.classList.toggle('source-editing', enabled);
        if (manageButton) {
            manageButton.classList.toggle('source-action-btn-active', enabled);
            manageButton.setAttribute('aria-pressed', enabled ? 'true' : 'false');
        }
        cards.forEach(function (card) {
            const editable = enabled && Number(card.dataset.id || 0) > 0;
            card.draggable = editable;
        });
    }

    function submitSourceOrder() {
        if (!reorderForm || !orderInput) return;
        const order = Array.from(list.querySelectorAll('.source-manage-card'))
            .map(function (card) {
                return Number(card.dataset.id || 0);
            })
            .filter(function (id) {
                return id > 0;
            });
        orderInput.value = JSON.stringify(order);
        reorderForm.submit();
    }

    function getDragAfterElement(container, y) {
        const draggableElements = Array.from(container.querySelectorAll('.source-manage-card:not(.source-card-dragging)'));
        return draggableElements.reduce(function (closest, child) {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            }
            return closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
    }

    if (addButton) {
        addButton.addEventListener('click', function () {
            openModal(null);
        });
    }

    if (manageButton) {
        manageButton.setAttribute('aria-pressed', 'false');
        manageButton.addEventListener('click', function () {
            setEditMode(!isEditMode);
        });
    }

    cards.forEach(function (card) {
        card.addEventListener('click', function () {
            if (isEditMode) return;
            const detailUrl = card.dataset.detailUrl || '';
            if (detailUrl) {
                window.location.href = detailUrl;
            }
        });

        card.addEventListener('keydown', function (event) {
            if (isEditMode) return;
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            const detailUrl = card.dataset.detailUrl || '';
            if (detailUrl) {
                window.location.href = detailUrl;
            }
        });

        card.addEventListener('dragstart', function () {
            if (!isEditMode || Number(card.dataset.id || 0) <= 0) return;
            draggedCard = card;
            card.classList.add('source-card-dragging');
        });

        card.addEventListener('dragend', function () {
            if (!draggedCard) return;
            card.classList.remove('source-card-dragging');
            draggedCard = null;
            submitSourceOrder();
        });

        const dragHandle = card.querySelector('.source-row-drag-handle');
        if (dragHandle) {
            dragHandle.addEventListener('pointerdown', function (event) {
                if (!isEditMode || Number(card.dataset.id || 0) <= 0) return;
                event.preventDefault();
                pointerDragging = true;
                pointerMoved = false;
                draggedCard = card;
                card.classList.add('source-card-dragging');
                dragHandle.setPointerCapture(event.pointerId);
            });

            dragHandle.addEventListener('pointermove', function (event) {
                if (!pointerDragging || !draggedCard) return;
                event.preventDefault();
                pointerMoved = true;
                const afterElement = getDragAfterElement(list, event.clientY);
                if (afterElement === null) {
                    list.appendChild(draggedCard);
                } else {
                    list.insertBefore(draggedCard, afterElement);
                }
            });

            dragHandle.addEventListener('pointerup', function (event) {
                if (!pointerDragging || !draggedCard) return;
                dragHandle.releasePointerCapture(event.pointerId);
                draggedCard.classList.remove('source-card-dragging');
                draggedCard = null;
                pointerDragging = false;
                if (pointerMoved) {
                    submitSourceOrder();
                }
            });

            dragHandle.addEventListener('pointercancel', function () {
                if (draggedCard) {
                    draggedCard.classList.remove('source-card-dragging');
                }
                draggedCard = null;
                pointerDragging = false;
                pointerMoved = false;
            });
        }
    });

    if (list) {
        list.addEventListener('dragover', function (event) {
            if (!isEditMode || !draggedCard) return;
            event.preventDefault();
            const afterElement = getDragAfterElement(list, event.clientY);
            if (afterElement === null) {
                list.appendChild(draggedCard);
            } else {
                list.insertBefore(draggedCard, afterElement);
            }
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    if (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    }

    document.addEventListener('submit', function (event) {
        if (!event.target.matches('[data-confirm-delete]')) return;
        if (!window.confirm('Delete this source?')) {
            event.preventDefault();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
            closeModal();
        }
        if (event.key === 'Escape' && isEditMode) {
            setEditMode(false);
        }
    });

})();
