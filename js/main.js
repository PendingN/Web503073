(function () {
    'use strict';

    function init() {
        var counters = document.querySelectorAll('[data-count-to]');
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var feedHero = document.querySelector('.feed-hero');
        if (feedHero) {
            var viewport = feedHero.querySelector('.feed-ticker-viewport');
            var track = feedHero.querySelector('.feed-ticker-track');
            var group = feedHero.querySelector('.feed-ticker-group');
            var motionToggle = feedHero.querySelector('.feed-motion-toggle');
            if (group && group.children.length && motionToggle) {
                var motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
                var originals = Array.from(group.children);
                var paused = false;
                function hideDuplicate(element) {
                    element.setAttribute('aria-hidden', 'true');
                    if (element.matches('a')) element.tabIndex = -1;
                    element.querySelectorAll('a').forEach(function (link) { link.tabIndex = -1; });
                    return element;
                }
                function buildLoop() {
                    if (track.children.length > 1) track.lastElementChild.remove();
                    group.querySelectorAll('[aria-hidden="true"]').forEach(function (card) { card.remove(); });
                    if (!motionPreference.matches) {
                        // Fill a whole viewport even when there is only one published article.
                        while (group.getBoundingClientRect().width < viewport.clientWidth) {
                            originals.forEach(function (card) { group.appendChild(hideDuplicate(card.cloneNode(true))); });
                        }
                        track.appendChild(hideDuplicate(group.cloneNode(true)));
                        track.style.setProperty('--feed-duration', (group.getBoundingClientRect().width / 22) + 's');
                    }
                    feedHero.classList.add('has-marquee');
                    motionToggle.hidden = motionPreference.matches;
                }
                motionToggle.addEventListener('click', function () {
                    paused = !paused;
                    feedHero.classList.toggle('is-paused', paused);
                    motionToggle.setAttribute('aria-pressed', String(paused));
                    motionToggle.textContent = paused ? 'Chạy tiếp' : 'Tạm dừng';
                });
                motionPreference.addEventListener('change', buildLoop);
                var lastWidth = 0;
                new ResizeObserver(function () {
                    if (viewport.clientWidth === lastWidth) return;
                    lastWidth = viewport.clientWidth;
                    buildLoop();
                }).observe(viewport);
                buildLoop();
            }
        }
        if (counters.length && !reduceMotion && 'IntersectionObserver' in window) {
            var counterObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    var counter = entry.target;
                    var target = Number(counter.getAttribute('data-count-to'));
                    counterObserver.unobserve(counter);
                    var start;
                    function tick(timestamp) {
                        if (start === undefined) start = timestamp;
                        var progress = Math.min((timestamp - start) / 850, 1);
                        counter.textContent = String(Math.round(target * (1 - Math.pow(1 - progress, 3))));
                        if (progress < 1) window.requestAnimationFrame(tick);
                    }
                    window.requestAnimationFrame(tick);
                });
            }, { threshold: 0.6 });
            counters.forEach(function (counter) {
                counter.textContent = '0';
                counterObserver.observe(counter);
            });
        }

        var menuToggle = document.querySelector('[data-menu-toggle]');
        var siteMenu = document.getElementById('site-menu');
        if (menuToggle && siteMenu) {
            function closeMenu() {
                siteMenu.classList.remove('is-open');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.setAttribute('aria-label', 'Mở menu');
            }
            menuToggle.addEventListener('click', function () {
                var isOpen = siteMenu.classList.toggle('is-open');
                menuToggle.setAttribute('aria-expanded', String(isOpen));
                menuToggle.setAttribute('aria-label', isOpen ? 'Đóng menu' : 'Mở menu');
            });
            siteMenu.addEventListener('click', function (event) {
                if (event.target.closest('a')) closeMenu();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && siteMenu.classList.contains('is-open')) {
                    closeMenu();
                    menuToggle.focus();
                }
            });
            document.addEventListener('click', function (event) {
                if (!siteMenu.contains(event.target) && !menuToggle.contains(event.target)) closeMenu();
            });
        }

        var postImage = document.getElementById('post-image');
        var imagePreview = document.getElementById('post-image-preview');
        if (postImage && imagePreview) {
            var originalImage = imagePreview.getAttribute('src');
            var previewUrl = null;
            postImage.addEventListener('change', function () {
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                var file = postImage.files[0];
                var validImage = file && ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= 2097152;
                postImage.setCustomValidity(file && !validImage ? 'Chọn ảnh JPG, PNG hoặc WebP tối đa 2 MB.' : '');
                previewUrl = validImage ? URL.createObjectURL(file) : null;
                var source = previewUrl || originalImage;
                imagePreview.hidden = !source;
                if (source) imagePreview.src = source;
                else imagePreview.removeAttribute('src');
                if (file && !validImage) postImage.reportValidity();
            });
        }

        function setReaction(button, active, animate) {
            var wasActive = button.getAttribute('aria-pressed') === 'true';
            button.setAttribute('aria-pressed', String(active));
            button.classList.toggle('is-active', active);
            if (button.classList.contains('favorite-button')) button.classList.toggle('is-favorite', active);
            if (animate && active && !wasActive && button.classList.contains('heart-button')) {
                button.classList.remove('heart-pop');
                void button.offsetWidth;
                button.classList.add('heart-pop');
            }
        }

        var reactionStatus;
        function announceReaction(message, error) {
            if (!reactionStatus) {
                reactionStatus = document.createElement('p');
                reactionStatus.setAttribute('role', 'status');
                reactionStatus.setAttribute('aria-live', 'polite');
                document.body.appendChild(reactionStatus);
            }
            reactionStatus.className = error ? 'reaction-error' : 'sr-only';
            reactionStatus.textContent = message;
        }

        document.querySelectorAll('form[data-reaction]').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                if (!window.fetch || !event.submitter) return;
                event.preventDefault();
                if (form.dataset.reacting === 'true') return;
                var button = event.submitter;
                var data = new FormData(form);
                var resourceForms = Array.from(document.querySelectorAll('form[data-reaction]')).filter(function (other) {
                    return form.dataset.reaction === 'favorite'
                        ? other.dataset.reaction === 'favorite' && other.elements.plant_id.value === data.get('plant_id')
                        : other.dataset.reaction !== 'favorite' && other.elements.slug.value === data.get('slug');
                });
                if (resourceForms.some(function (other) { return other.dataset.reacting === 'true'; })) return;
                if (button.name) data.set(button.name, button.value);
                if (form.dataset.reaction === 'favorite') {
                    data.set('favorite', button.getAttribute('aria-pressed') === 'true' ? '0' : '1');
                }
                resourceForms.forEach(function (other) {
                    other.dataset.reacting = 'true';
                    other.setAttribute('aria-busy', 'true');
                });
                var buttons = resourceForms.flatMap(function (other) { return Array.from(other.querySelectorAll('button')); });
                buttons.forEach(function (control) { control.disabled = true; });
                try {
                    var response = await fetch(form.action, {
                        method: 'POST', body: data, credentials: 'same-origin',
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!(response.headers.get('Content-Type') || '').includes('application/json')) {
                        throw new Error('Không thể cập nhật. Vui lòng thử lại.');
                    }
                    var result = await response.json();
                    if (response.status === 401 && result.login_url) {
                        window.location.assign(result.login_url);
                        return;
                    }
                    if (!response.ok) throw new Error(result.message || 'Không thể cập nhật. Vui lòng thử lại.');
                    if (form.dataset.reaction === 'favorite') {
                        document.querySelectorAll('form[data-reaction="favorite"]').forEach(function (other) {
                            if (other.elements.plant_id.value !== data.get('plant_id')) return;
                            var heart = other.querySelector('button');
                            setReaction(heart, result.favorite, true);
                            heart.setAttribute('aria-label', (result.favorite ? 'Xóa ' : 'Thêm ') + other.dataset.plantName + (result.favorite ? ' khỏi' : ' vào') + ' mục yêu thích');
                            var label = heart.querySelector('[data-reaction-label]');
                            if (label) label.textContent = result.favorite ? 'Đã yêu thích' : 'Thêm vào yêu thích';
                        });
                        document.querySelectorAll('.favorite-count').forEach(function (count) { count.textContent = result.count; });
                        var grid = form.closest('[data-favorites-grid]');
                        if (grid && !result.favorite) {
                            var card = form.closest('.plant-card');
                            var next = card.nextElementSibling || card.previousElementSibling;
                            card.remove();
                            var empty = document.querySelector('[data-favorites-empty]');
                            if (empty) empty.hidden = !!grid.children.length;
                            var focusTarget = next ? next.querySelector('.favorite-button') : empty && empty.querySelector('a');
                            if (focusTarget) focusTarget.focus({ preventScroll: true });
                        }
                    } else {
                        document.querySelectorAll('form[data-reaction="vote"], form[data-reaction="save"]').forEach(function (other) {
                            if (other.elements.slug.value !== data.get('slug')) return;
                            if (other.dataset.reaction === 'vote') {
                                other.querySelectorAll('[data-vote]').forEach(function (voteButton) {
                                    var value = Number(voteButton.dataset.vote);
                                    var active = result.vote === value;
                                    setReaction(voteButton, active, true);
                                    voteButton.value = active ? '0' : String(value);
                                    var count = voteButton.querySelector('[data-vote-count]');
                                    if (count) count.textContent = value === 1 ? result.likes : result.dislikes;
                                });
                            } else {
                                var saveButton = other.querySelector('button');
                                setReaction(saveButton, result.saved, true);
                                saveButton.value = result.saved ? '0' : '1';
                                saveButton.querySelector('[data-reaction-label]').textContent = result.saved ? 'Đã lưu' : 'Lưu';
                            }
                        });
                    }
                    announceReaction(result.message, false);
                } catch (error) {
                    announceReaction(error.message || 'Mất kết nối. Vui lòng thử lại.', true);
                } finally {
                    resourceForms.forEach(function (other) {
                        delete other.dataset.reacting;
                        other.removeAttribute('aria-busy');
                    });
                    buttons.forEach(function (control) { control.disabled = false; });
                }
            });
        });

        document.addEventListener('animationend', function (event) {
            if (event.target.classList.contains('heart-icon')) {
                var heart = event.target.closest('.heart-button');
                if (heart) heart.classList.remove('heart-pop');
            }
        });

        document.querySelectorAll('form[method="post"]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (event.defaultPrevented) return;
                if (form.dataset.submitting === 'true') {
                    event.preventDefault();
                    return;
                }
                form.dataset.submitting = 'true';
                form.setAttribute('aria-busy', 'true');
                // Keep name/value fields enabled so PHP receives the clicked operation.
                form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]').forEach(function (button) {
                    button.setAttribute('aria-disabled', 'true');
                });
            });
        });
        window.addEventListener('pageshow', function () {
            document.querySelectorAll('form[data-submitting]').forEach(function (form) {
                delete form.dataset.submitting;
                form.removeAttribute('aria-busy');
                form.querySelectorAll('[aria-disabled="true"]').forEach(function (button) {
                    button.removeAttribute('aria-disabled');
                });
            });
        });

        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.getAttribute('aria-controls'));
                if (!input) return;
                var visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.textContent = visible ? 'Hiện' : 'Ẩn';
                button.setAttribute('aria-label', visible ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
            });
        });

        document.querySelectorAll('[data-auto-dismiss]').forEach(function (note) {
            window.setTimeout(function () { note.remove(); }, 5000);
        });

    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
