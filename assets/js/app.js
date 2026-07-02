(function () {
    'use strict';

    const appsMap = {};
    (window.CYPWN_APPS || []).forEach(function (app) {
        appsMap[app.id] = app;
    });

    const searchInput = document.getElementById('search-input');
    const searchClear = document.getElementById('search-clear');
    const filterBtn = document.getElementById('filter-btn');
    const filterPanel = document.getElementById('filter-panel');
    const appsCatalog = document.getElementById('apps-catalog');
    const noResults = document.getElementById('no-results');
    const filterOptions = document.querySelectorAll('.filter-option');
    let activeCategory = 'all';
    const overlay = document.getElementById('modal-overlay');
    const modal = document.getElementById('modal');
    const modalClose = document.getElementById('modal-close');

    const modalIcon = document.getElementById('modal-icon');
    const modalTitle = document.getElementById('modal-title');
    const modalSubtitle = document.getElementById('modal-subtitle');
    const modalDeveloper = document.getElementById('modal-developer');
    const modalCategory = document.getElementById('modal-category');
    const modalVersion = document.getElementById('modal-version');
    const modalDate = document.getElementById('modal-date');
    const modalScreenshots = document.getElementById('modal-screenshots');
    const modalDescription = document.getElementById('modal-description');
    const modalDownload = document.getElementById('modal-download');

    /* Mobile header menu */
    const siteHeader = document.querySelector('.site-header');
    const navToggle = document.getElementById('nav-menu-toggle');
    const headerNav = document.getElementById('header-nav');

    function setNavOpen(open) {
        if (!siteHeader || !navToggle) return;
        siteHeader.classList.toggle('nav-open', open);
        navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }

    if (navToggle && headerNav) {
        navToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            setNavOpen(!siteHeader.classList.contains('nav-open'));
        });

        headerNav.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                setNavOpen(false);
            });
        });

        document.addEventListener('click', function (e) {
            if (!siteHeader.classList.contains('nav-open')) return;
            if (!e.target.closest('.header-actions')) {
                setNavOpen(false);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                setNavOpen(false);
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                setNavOpen(false);
            }
        });
    }

    /* Search & category filter */
    function applyFilters() {
        const q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const cards = appsCatalog ? appsCatalog.querySelectorAll('.app-card') : [];
        let visible = 0;

        cards.forEach(function (card) {
            const hay = card.dataset.search || '';
            const matchesSearch = q === '' || hay.indexOf(q) !== -1;
            const cardCategory = card.dataset.appCategory || '';
            const matchesCategory = activeCategory === 'all' || cardCategory === activeCategory;
            const show = matchesSearch && matchesCategory;
            card.classList.toggle('hidden', !show);
            if (show) visible++;
        });

        if (searchClear) {
            searchClear.classList.toggle('visible', q.length > 0);
        }
        if (filterBtn) {
            filterBtn.classList.toggle('filter-btn-active', activeCategory !== 'all');
        }
        if (noResults) {
            noResults.classList.toggle('hidden', visible > 0 || cards.length === 0);
        }
    }

    function setActiveCategory(category, selectedOption) {
        activeCategory = category || 'all';
        filterOptions.forEach(function (btn) {
            const isActive = selectedOption ? btn === selectedOption : btn.dataset.category === activeCategory;
            btn.classList.toggle('filter-option-active', isActive);
        });
        applyFilters();
    }

    function setFilterPanelOpen(open) {
        if (!filterPanel || !filterBtn) return;
        filterPanel.classList.toggle('hidden', !open);
        filterBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    if (searchClear) {
        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            applyFilters();
            searchInput.focus();
        });
    }

    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            setFilterPanelOpen(filterPanel.classList.contains('hidden'));
        });

        filterPanel.addEventListener('click', function (e) {
            const option = e.target.closest('.filter-option');
            if (!option) return;
            setActiveCategory(option.dataset.category || 'all', option);
            setFilterPanelOpen(false);
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.filter-wrap')) {
                setFilterPanelOpen(false);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                setFilterPanelOpen(false);
            }
        });
    }

    filterOptions.forEach(function (option) {
        option.addEventListener('click', function () {
            setActiveCategory(option.dataset.category || 'all', option);
        });
    });

    /* Modal */
    function openModal(app) {
        if (!overlay || !app) return;

        modalTitle.textContent = app.name || '';
        modalSubtitle.textContent = app.subtitle || '';
        modalDeveloper.textContent = app.developer_name ? 'by ' + app.developer_name : '';
        modalCategory.textContent = app.category ? app.category : '';
        modalVersion.textContent = app.version ? 'v' + app.version : '';
        modalDate.textContent = app.version_date ? app.version_date : '';
        modalDescription.textContent = app.description || 'No description available.';

        if (app.icon) {
            modalIcon.src = app.icon;
            modalIcon.alt = app.name;
            modalIcon.style.display = '';
        } else {
            modalIcon.style.display = 'none';
        }

        const hasDownload = app.download_url && app.download_url !== '#';
        if (modalDownload) {
            if (hasDownload) {
                modalDownload.href = app.download_url;
                modalDownload.target = '_blank';
                modalDownload.rel = 'noopener noreferrer';
                modalDownload.classList.remove('hidden');
            } else {
                modalDownload.classList.add('hidden');
            }
        }

        modalScreenshots.innerHTML = '';
        const shots = app.screenshots || [];
        if (shots.length > 0) {
            modalScreenshots.classList.remove('hidden');
            shots.forEach(function (src) {
                const img = document.createElement('img');
                img.src = src;
                img.alt = 'Screenshot';
                img.loading = 'lazy';
                modalScreenshots.appendChild(img);
            });
        } else {
            modalScreenshots.classList.add('hidden');
        }

        overlay.classList.remove('hidden');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!overlay) return;
        overlay.classList.add('hidden');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function loadAndOpen(id) {
        const cached = appsMap[id];
        if (cached) {
            openModal(cached);
            return;
        }
        const api = window.CYPWN_API || 'api/app.php';
        fetch(api + '?id=' + encodeURIComponent(id))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.error) {
                    appsMap[data.id] = data;
                    openModal(data);
                }
            })
            .catch(function () {});
    }

    if (appsCatalog) {
        appsCatalog.addEventListener('click', function (e) {
            const viewBtn = e.target.closest('.app-card-view');
            if (viewBtn) {
                e.preventDefault();
                e.stopPropagation();
                const card = viewBtn.closest('.app-card');
                if (!card) return;
                const id = parseInt(card.dataset.id, 10);
                if (id) loadAndOpen(id);
                return;
            }
            const card = e.target.closest('.app-card');
            if (!card) return;
            const id = parseInt(card.dataset.id, 10);
            if (id) loadAndOpen(id);
        });

        appsCatalog.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const card = e.target.closest('.app-card');
            if (!card) return;
            e.preventDefault();
            const id = parseInt(card.dataset.id, 10);
            if (id) loadAndOpen(id);
        });
    }

    if (modalClose) modalClose.addEventListener('click', closeModal);
    if (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay && !overlay.classList.contains('hidden')) {
            closeModal();
        }
    });
})();
