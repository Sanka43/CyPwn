(function () {
    'use strict';

    var track = document.getElementById('home-carousel-track');
    if (!track) return;

    var prevBtn = document.getElementById('home-carousel-prev');
    var nextBtn = document.getElementById('home-carousel-next');
    var dotsContainer = document.getElementById('home-carousel-dots');
    var cards = track.querySelectorAll('.home-card');

    if (!cards.length) return;

    var dots = [];

    function getGap() {
        var style = window.getComputedStyle(track);
        return parseFloat(style.columnGap || style.gap) || 16;
    }

    function scrollAmount() {
        var card = cards[0];
        return card.offsetWidth + getGap();
    }

    function activeIndex() {
        var amount = scrollAmount();
        if (amount <= 0) return 0;
        return Math.round(track.scrollLeft / amount);
    }

    function updateDots() {
        var idx = Math.min(activeIndex(), cards.length - 1);
        dots.forEach(function (dot, i) {
            dot.classList.toggle('home-carousel-dot-active', i === idx);
            dot.setAttribute('aria-selected', i === idx ? 'true' : 'false');
        });
        if (prevBtn) prevBtn.disabled = track.scrollLeft <= 2;
        if (nextBtn) {
            nextBtn.disabled = track.scrollLeft >= track.scrollWidth - track.clientWidth - 2;
        }
    }

    function scrollToIndex(index) {
        var clamped = Math.max(0, Math.min(index, cards.length - 1));
        track.scrollTo({ left: clamped * scrollAmount(), behavior: 'smooth' });
    }

    cards.forEach(function (_, i) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'home-carousel-dot' + (i === 0 ? ' home-carousel-dot-active' : '');
        dot.setAttribute('role', 'tab');
        dot.setAttribute('aria-label', 'Go to card ' + (i + 1));
        dot.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
        dot.addEventListener('click', function () { scrollToIndex(i); });
        dots.push(dot);
        if (dotsContainer) dotsContainer.appendChild(dot);
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            scrollToIndex(activeIndex() - 1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            scrollToIndex(activeIndex() + 1);
        });
    }

    var scrollTimer;
    track.addEventListener('scroll', function () {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(updateDots, 60);
    }, { passive: true });

    window.addEventListener('resize', updateDots);
    updateDots();
})();
