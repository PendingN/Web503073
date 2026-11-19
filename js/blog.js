(function () {
    'use strict';

    var motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (motionQuery.matches) {
        return;
    }

    function initPlateReveals() {
        var plates = Array.from(document.querySelectorAll('[data-mono-plate]'));
        if (!plates.length || !('IntersectionObserver' in window)) {
            return;
        }

        var viewportH = window.innerHeight || 900;
        var deferred = [];
        plates.forEach(function (plate, index) {
            var rect = plate.getBoundingClientRect();
            if (rect.top > viewportH * 0.94) {
                plate.classList.add('mono-plate-pending');
                plate.style.setProperty('--mono-stagger', ((index % 3) * 65) + 'ms');
                deferred.push(plate);
            }
        });

        if (!deferred.length) {
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                var el = entry.target;
                el.classList.add('mono-plate-visible');
                el.classList.remove('mono-plate-pending');
                observer.unobserve(el);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -36px 0px' });

        deferred.forEach(function (plate) {
            observer.observe(plate);
        });
    }

    function initSpotlightParallax() {
        var targets = Array.from(document.querySelectorAll('[data-mono-parallax]'));
        if (!targets.length) {
            return;
        }

        var ticking = false;
        function update() {
            ticking = false;
            var vh = window.innerHeight || 900;
            targets.forEach(function (el) {
                var rect = el.getBoundingClientRect();
                if (rect.bottom < -60 || rect.top > vh + 60) {
                    return;
                }
                var center = rect.top + rect.height * 0.5;
                var normalized = (center - vh * 0.5) / (vh * 0.65);
                var clamped = Math.max(-1, Math.min(1, normalized));
                el.style.setProperty('--mono-scroll-shift', (clamped * 14).toFixed(2) + 'px');
            });
        }

        function onScroll() {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        update();
    }

    function initReadingProgress() {
        var bar = document.querySelector('[data-reading-progress]');
        var article = document.querySelector('[data-reading-article]');
        if (!bar || !article) {
            return;
        }

        var ticking = false;
        function update() {
            ticking = false;
            var docScrollSpan = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
            var articleRect = article.getBoundingClientRect();
            var articleBottom = articleRect.top + window.scrollY + article.offsetHeight;
            var articleSpan = Math.max(240, articleBottom - window.innerHeight * 0.65);
            var targetSpan = Math.min(docScrollSpan, articleSpan);
            var progress = Math.max(0, Math.min(1, window.scrollY / targetSpan));
            bar.style.transform = 'scaleX(' + progress.toFixed(4) + ')';
        }

        function onScroll() {
            if (!ticking) {
                ticking = true;
                window.requestAnimationFrame(update);
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        update();
    }

    function init() {
        initPlateReveals();
        initSpotlightParallax();
        initReadingProgress();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
