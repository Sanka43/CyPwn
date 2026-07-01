(function () {
    'use strict';

    const DATA_URL = window.CYPWN_REPO_DATA || 'api/repo-packages.php';
    const IMG_BASE = window.CYPWN_REPO_IMG_BASE || 'assets/repo/';
    const CAROUSEL_INTERVAL = 3500;
    const CAROUSEL_GAP = 12;
    const CAROUSEL_PREVIEW_LIMIT = 30;

    let allPkgs = [];
    const carouselTimers = new WeakMap();
    let sections = [];
    let sectionCounts = {};
    let activeFilter = null;
    let searchQuery = '';

    const els = {
        loading: document.getElementById('pkg-loading'),
        catalog: document.getElementById('pkg-catalog'),
        sections: document.getElementById('pkg-sections'),
        flat: document.getElementById('pkg-flat'),
        flatHeading: document.getElementById('flat-heading'),
        grid: document.getElementById('pkg-grid'),
        noResults: document.getElementById('no-results'),
        searchInput: document.getElementById('search-input'),
        searchClear: document.getElementById('search-clear'),
        searchLabel: document.getElementById('search-results-label'),
        showSectionsBtn: document.getElementById('show-sections-btn'),
        gotoSection: document.getElementById('goto-section'),
        gotoLinks: document.getElementById('goto-section-links'),
        overlay: document.getElementById('modal-overlay'),
        modalClose: document.getElementById('modal-close'),
        modalTitle: document.getElementById('modal-title'),
        modalDev: document.getElementById('modal-developer'),
        modalVersion: document.getElementById('modal-version'),
        modalTags: document.getElementById('modal-tags'),
        modalIcon: document.getElementById('modal-icon'),
        modalLink: document.getElementById('modal-link'),
    };

    const TAG_CLASS = {
        tweaks: 'tag-tweaks', themes: 'tag-themes', other: 'tag-other',
        rootless: 'tag-rootless', xina: 'tag-xina',
        ios13: 'tag-ios13', ios14: 'tag-ios14', ios15: 'tag-ios15', ios16: 'tag-ios16',
    };

    /* ── Header mobile menu ── */
    const siteHeader = document.querySelector('.site-header');
    const navToggle = document.getElementById('nav-menu-toggle');
    const headerNav = document.getElementById('header-nav');

    if (navToggle && headerNav) {
        navToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = !siteHeader.classList.contains('nav-open');
            siteHeader.classList.toggle('nav-open', open);
            navToggle.setAttribute('aria-expanded', open);
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.header-actions')) {
                siteHeader.classList.remove('nav-open');
            }
        });
    }

    /* ── Add Repo accordion ── */
    const repoToggle = document.getElementById('repo-add-toggle');
    const repoBody = document.getElementById('repo-add-body');
    if (repoToggle && repoBody) {
        repoToggle.addEventListener('click', function () {
            const open = repoBody.classList.toggle('open');
            repoToggle.setAttribute('aria-expanded', open);
        });
    }

    /* ── Show Sections → bottom goto bar ── */
    const gotoBarToggle = document.getElementById('goto-bar-toggle');
    let gotoVisible = false;

    function setGotoBarVisible(visible) {
        gotoVisible = visible;
        if (els.gotoSection) {
            els.gotoSection.classList.toggle('hidden', !visible);
        }
        if (els.gotoLinks) {
            els.gotoLinks.classList.toggle('hidden', !visible);
        }
        if (gotoBarToggle) {
            gotoBarToggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
        }
        if (els.showSectionsBtn) {
            els.showSectionsBtn.textContent = visible ? 'Hide Sections' : 'Show Sections';
        }
    }

    if (els.showSectionsBtn) {
        els.showSectionsBtn.addEventListener('click', function () {
            setGotoBarVisible(!gotoVisible);
        });
    }

    if (gotoBarToggle && els.gotoLinks) {
        gotoBarToggle.addEventListener('click', function () {
            const menuOpen = els.gotoLinks.classList.toggle('hidden');
            gotoBarToggle.setAttribute('aria-expanded', menuOpen ? 'false' : 'true');
        });
    }

    function tagClass(tag) {
        return TAG_CLASS[tag.toLowerCase().replace(/\s+/g, '')] || 'tag-default';
    }

    function buildTag(tag) {
        const s = document.createElement('span');
        s.className = 'pkg-tag ' + tagClass(tag);
        s.textContent = tag;
        return s;
    }

    function iconSrc(pkg) {
        const url = pkg.icon_url || pkg.icon_local || '';
        if (url.startsWith('repoimg/')) return IMG_BASE + url;
        return url;
    }

    function buildCard(pkg) {
        const card = document.createElement('article');
        card.className = 'pkg-card';
        card.dataset.id = pkg.id;
        card.tabIndex = 0;
        card.setAttribute('role', 'button');

        const src = iconSrc(pkg);
        let iconEl;
        if (src) {
            const iconWrap = document.createElement('div');
            iconWrap.className = 'pkg-icon-wrap';
            iconEl = document.createElement('img');
            iconEl.className = 'pkg-icon';
            iconEl.src = src;
            iconEl.alt = '';
            iconEl.loading = 'lazy';
            iconEl.width = 54;
            iconEl.height = 54;
            iconEl.onerror = function () {
                const ph = document.createElement('div');
                ph.className = 'pkg-icon-placeholder';
                ph.textContent = '📦';
                card.replaceChild(ph, iconWrap);
            };
            iconWrap.appendChild(iconEl);
            iconEl = iconWrap;
        } else {
            iconEl = document.createElement('div');
            iconEl.className = 'pkg-icon-placeholder';
            iconEl.textContent = '📦';
        }

        const info = document.createElement('div');
        info.className = 'pkg-info';

        const name = document.createElement('p');
        name.className = 'pkg-name';
        name.textContent = pkg.name;

        info.appendChild(name);

        if (pkg.author) {
            const authorLine = document.createElement('p');
            authorLine.className = 'pkg-meta-line';
            authorLine.innerHTML = '<span class="meta-dot meta-dot--author"></span>';
            const authorSpan = document.createElement('span');
            authorSpan.className = 'pkg-author';
            authorSpan.textContent = pkg.author;
            authorLine.appendChild(authorSpan);
            info.appendChild(authorLine);
        }

        if (pkg.version) {
            const verLine = document.createElement('p');
            verLine.className = 'pkg-meta-line';
            verLine.innerHTML = '<span class="meta-dot meta-dot--version"></span>';
            const verSpan = document.createElement('span');
            verSpan.className = 'pkg-version';
            verSpan.textContent = pkg.version;
            verLine.appendChild(verSpan);
            info.appendChild(verLine);
        }

        const tags = document.createElement('div');
        tags.className = 'pkg-tags';
        (pkg.tags || []).slice(0, 4).forEach(function (t) {
            tags.appendChild(buildTag(t));
        });
        info.appendChild(tags);

        card.appendChild(iconEl);
        card.appendChild(info);

        card.addEventListener('click', function () { openModal(pkg); });
        card.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openModal(pkg);
            }
        });

        return card;
    }

    function getVisibleCount() {
        if (window.innerWidth <= 576) return 1;
        if (window.innerWidth <= 992) return 2;
        return 3;
    }

    function getCarouselStep(carousel) {
        const card = carousel.querySelector('.pkg-card');
        if (!card) return 0;
        return card.offsetWidth + CAROUSEL_GAP;
    }

    function applyCarouselTransform(carousel, animate) {
        const track = carousel.querySelector('.pkg-track');
        if (!track) return;

        const step = getCarouselStep(carousel);
        const index = carousel.dataset.index ? parseInt(carousel.dataset.index, 10) : 0;

        if (!animate) {
            track.style.transition = 'none';
        }
        track.style.transform = 'translateX(-' + (index * step) + 'px)';
        if (!animate) {
            track.offsetHeight;
            track.style.transition = '';
        }

    }

    function slideCarousel(carousel, direction) {
        const cards = carousel.querySelectorAll('.pkg-card');
        const visible = getVisibleCount();
        if (cards.length <= visible) return;

        const maxIndex = cards.length - visible;
        let index = carousel.dataset.index ? parseInt(carousel.dataset.index, 10) : 0;

        if (direction === 1) {
            index += 1;
            if (index > maxIndex) {
                const track = carousel.querySelector('.pkg-track');
                track.style.transition = 'none';
                index = 0;
                carousel.dataset.index = '0';
                applyCarouselTransform(carousel, false);
                requestAnimationFrame(function () {
                    track.style.transition = '';
                });
                return;
            }
        } else {
            index = index <= 0 ? maxIndex : index - 1;
        }

        carousel.dataset.index = String(index);
        applyCarouselTransform(carousel, true);
    }

    function initCarousel(carousel) {
        const cards = carousel.querySelectorAll('.pkg-card');
        const visible = getVisibleCount();
        if (cards.length <= visible) return;

        carousel.dataset.index = '0';
        applyCarouselTransform(carousel, false);

        function startTimer() {
            const id = setInterval(function () {
                slideCarousel(carousel, 1);
            }, CAROUSEL_INTERVAL);
            carouselTimers.set(carousel, id);
        }

        function resetCarouselTimer(el) {
            const old = carouselTimers.get(el);
            if (old) clearInterval(old);
            startTimer();
        }

        carousel._resetTimer = function () { resetCarouselTimer(carousel); };

        carousel.addEventListener('mouseenter', function () {
            const id = carouselTimers.get(carousel);
            if (id) clearInterval(id);
        });
        carousel.addEventListener('mouseleave', function () {
            resetCarouselTimer(carousel);
        });

        startTimer();
    }

    function destroyCarousels(container) {
        if (!container) return;
        container.querySelectorAll('.pkg-carousel').forEach(function (c) {
            const id = carouselTimers.get(c);
            if (id) clearInterval(id);
        });
    }

    function buildCarousel(pkgs, previewLimit) {
        const list = previewLimit && pkgs.length > previewLimit
            ? pkgs.slice(0, previewLimit)
            : pkgs;

        const carousel = document.createElement('div');
        carousel.className = 'pkg-carousel';

        const track = document.createElement('div');
        track.className = 'pkg-track';

        list.forEach(function (pkg) {
            track.appendChild(buildCard(pkg));
        });

        carousel.appendChild(track);

        requestAnimationFrame(function () {
            initCarousel(carousel);
        });

        return carousel;
    }

    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            document.querySelectorAll('.pkg-carousel').forEach(function (c) {
                applyCarouselTransform(c, false);
            });
        }, 150);
    });

    function pkgsInSection(title) {
        return allPkgs.filter(function (p) {
            return (p.categories || []).indexOf(title) !== -1;
        });
    }

    function filterPkgs(query, sectionTitle) {
        const q = query.trim().toLowerCase();
        return allPkgs.filter(function (p) {
            const hay = [p.name, p.author, p.version, ...(p.tags || []), ...(p.categories || [])]
                .join(' ').toLowerCase();
            const matchQ = !q || hay.indexOf(q) !== -1;
            const matchS = !sectionTitle || (p.categories || []).indexOf(sectionTitle) !== -1;
            return matchQ && matchS;
        });
    }

    function buildSectionBlock(sec) {
        const pkgs = pkgsInSection(sec.title);
        if (!pkgs.length) return null;

        const block = document.createElement('section');
        block.className = 'pkg-section';
        block.id = 'section-' + sec.slug;

        const heading = document.createElement('h2');
        heading.className = 'section-heading';
        heading.setAttribute('role', 'button');
        heading.tabIndex = 0;

        heading.textContent = sec.title;

        function goToSection() {
            setFilter(sec.title);
        }
        heading.addEventListener('click', goToSection);
        heading.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                goToSection();
            }
        });

        block.appendChild(heading);
        block.appendChild(buildCarousel(pkgs, CAROUSEL_PREVIEW_LIMIT));

        return block;
    }

    function renderBrowseView() {
        destroyCarousels(els.sections);
        els.sections.innerHTML = '';
        els.sections.classList.remove('hidden');
        els.flat.classList.add('hidden');

        const frag = document.createDocumentFragment();
        sections.forEach(function (sec) {
            const block = buildSectionBlock(sec);
            if (block) frag.appendChild(block);
        });
        els.sections.appendChild(frag);
        els.noResults.classList.add('hidden');
    }

    function renderFlatView(pkgs, heading) {
        destroyCarousels(els.flat);
        els.sections.classList.add('hidden');
        els.flat.classList.remove('hidden');
        els.flatHeading.textContent = '';
        const flatTitle = document.createElement('span');
        flatTitle.textContent = heading;
        els.flatHeading.appendChild(flatTitle);
        els.grid.innerHTML = '';

        if (!pkgs.length) {
            els.noResults.classList.remove('hidden');
            return;
        }

        els.noResults.classList.add('hidden');
        els.grid.appendChild(buildCarousel(pkgs));
    }

    function updateView() {
        const q = searchQuery.trim();

        if (els.searchClear) {
            els.searchClear.classList.toggle('visible', q.length > 0);
        }

        if (q || activeFilter) {
            const pkgs = filterPkgs(q, activeFilter);
            let heading = 'Search Results';
            if (activeFilter && q) {
                heading = activeFilter + ' — "' + q + '"';
            } else if (activeFilter) {
                heading = activeFilter;
            } else {
                heading = 'Search: "' + q + '"';
            }
            renderFlatView(pkgs, heading);

            if (els.searchLabel) {
                els.searchLabel.textContent = pkgs.length + ' package' + (pkgs.length !== 1 ? 's' : '') + ' found';
                els.searchLabel.classList.remove('hidden');
            }
        } else {
            renderBrowseView();
            if (els.searchLabel) {
                els.searchLabel.classList.add('hidden');
            }
        }
    }

    function setFilter(title) {
        activeFilter = title;
        searchQuery = els.searchInput ? els.searchInput.value : '';
        updateView();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function clearFilter() {
        activeFilter = null;
        updateView();
    }

    function buildGotoNav() {
        if (!els.gotoLinks) return;
        els.gotoLinks.innerHTML = '';

        const allLink = document.createElement('a');
        allLink.href = '#';
        allLink.className = 'goto-link';
        allLink.textContent = 'All';
        allLink.addEventListener('click', function (e) {
            e.preventDefault();
            clearFilter();
            if (els.searchInput) els.searchInput.value = '';
            searchQuery = '';
            updateView();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        els.gotoLinks.appendChild(allLink);

        sections.forEach(function (sec) {
            const a = document.createElement('a');
            a.href = '#section-' + sec.slug;
            a.className = 'goto-link';
            const count = sectionCounts[sec.title];
            a.textContent = count ? sec.title.replace(' Packages', '') + ' (' + count + ')' : sec.title;
            a.addEventListener('click', function (e) {
                e.preventDefault();
                if (searchQuery || activeFilter) {
                    clearFilter();
                    if (els.searchInput) els.searchInput.value = '';
                    searchQuery = '';
                    updateView();
                    setTimeout(function () {
                        document.getElementById('section-' + sec.slug)?.scrollIntoView({ behavior: 'smooth' });
                    }, 50);
                } else {
                    document.getElementById('section-' + sec.slug)?.scrollIntoView({ behavior: 'smooth' });
                }
            });
            els.gotoLinks.appendChild(a);
        });
    }

    /* ── Modal ── */
    function openModal(pkg) {
        els.modalTitle.textContent = pkg.name || '';
        els.modalDev.textContent = pkg.author ? pkg.author : '';
        els.modalVersion.textContent = pkg.version ? 'Version ' + pkg.version : '';
        els.modalLink.href = pkg.package_url || '#';

        const src = iconSrc(pkg);
        if (src) {
            els.modalIcon.src = src;
            els.modalIcon.alt = pkg.name;
            els.modalIcon.style.display = '';
        } else {
            els.modalIcon.style.display = 'none';
        }

        els.modalTags.innerHTML = '';
        (pkg.tags || []).forEach(function (t) {
            els.modalTags.appendChild(buildTag(t));
        });

        els.overlay.classList.remove('hidden');
        els.overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        els.overlay.classList.add('hidden');
        els.overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (els.modalClose) els.modalClose.addEventListener('click', closeModal);
    if (els.overlay) {
        els.overlay.addEventListener('click', function (e) {
            if (e.target === els.overlay) closeModal();
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    /* ── Search ── */
    if (els.searchInput) {
        els.searchInput.addEventListener('input', function () {
            searchQuery = els.searchInput.value;
            activeFilter = null;
            updateView();
        });
    }
    if (els.searchClear) {
        els.searchClear.addEventListener('click', function () {
            els.searchInput.value = '';
            searchQuery = '';
            activeFilter = null;
            updateView();
            els.searchInput.focus();
        });
    }

    /* ── Load ── */
    fetch(DATA_URL)
        .then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function (data) {
            allPkgs = data.packages || [];
            sections = data.sections || [];
            sectionCounts = data.section_counts || {};

            buildGotoNav();
            updateView();

            els.loading.classList.add('hidden');
            els.catalog.classList.remove('hidden');
        })
        .catch(function (err) {
            els.loading.innerHTML = '<p style="color:var(--accent-red)">Failed to load packages.json</p>';
            console.error(err);
        });
})();
