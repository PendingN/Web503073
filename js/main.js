(function () {
    'use strict';

    function resetForm(form) {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('[aria-disabled="true"]').forEach(function (button) {
            button.removeAttribute('aria-disabled');
        });
    }

    function showActionMessage(message, failed) {
        var notice = document.getElementById('inline-action-notice');
        if (!notice) {
            notice = document.createElement('div');
            notice.id = 'inline-action-notice';
            notice.className = 'inline-action-notice';
            notice.setAttribute('role', 'status');
            notice.setAttribute('aria-live', 'polite');
            document.body.appendChild(notice);
        }
        window.clearTimeout(notice.dismissTimer);
        notice.textContent = message;
        notice.classList.toggle('is-error', failed);
        notice.hidden = false;
        notice.dismissTimer = window.setTimeout(function () { notice.hidden = true; }, 5000);
    }

    async function submitInlineAction(form, submitter) {
        try {
            var data = new FormData(form);
            if (submitter && submitter.name) data.append(submitter.name, submitter.value);
            var response = await fetch(form.action, {
                method: 'POST', body: data, credentials: 'same-origin',
                headers: { Accept: 'application/json' }
            });
            if (response.redirected && new URL(response.url).pathname.endsWith('/login.php')) {
                window.location.assign(response.url);
                return;
            }
            if (!response.ok) {
                throw new Error(response.status === 403
                    ? 'Phiên thao tác đã hết hạn. Vui lòng tải lại trang rồi thử lại.'
                    : 'Chưa thể cập nhật. Vui lòng thử lại sau.');
            }
            if (!(response.headers.get('Content-Type') || '').includes('application/json')) {
                throw new Error('Chưa nhận được kết quả cập nhật. Vui lòng tải lại trang để kiểm tra.');
            }
            var result = await response.json();
            if (form.dataset.inlineAction === 'favorite') {
                var button = form.querySelector('.favorite-button');
                button.classList.toggle('is-favorite', result.favorite);
                button.setAttribute('aria-pressed', String(result.favorite));
                button.setAttribute('aria-label', (result.favorite ? 'Xóa ' : 'Thêm ') + result.name
                    + (result.favorite ? ' khỏi' : ' vào') + ' mục yêu thích');
                button.querySelector('svg').setAttribute('fill', result.favorite ? 'currentColor' : 'none');
                document.querySelectorAll('.favorite-count').forEach(function (count) {
                    count.textContent = String(result.count);
                });
            } else {
                var up = form.querySelector('button[name="value"][aria-label="Thích bài viết"]');
                var down = form.querySelector('button[name="value"][aria-label="Không thích bài viết"]');
                [[up, 1], [down, -1]].forEach(function (item) {
                    var selected = result.vote === item[1];
                    item[0].classList.toggle('is-active', selected);
                    item[0].setAttribute('aria-pressed', String(selected));
                    item[0].value = String(selected ? 0 : item[1]);
                });
                up.textContent = 'Thích · ' + result.score;
            }
            showActionMessage(result.message, false);
        } catch (error) {
            showActionMessage(error instanceof TypeError
                ? 'Kết nối bị gián đoạn. Vui lòng tải lại trang để kiểm tra trạng thái trước khi thử lại.'
                : error.message, true);
        } finally {
            resetForm(form);
        }
    }

    function init() {
        var counters = document.querySelectorAll('[data-count-to]');
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
                if (form.hasAttribute('data-inline-action') && 'fetch' in window) {
                    event.preventDefault();
                    submitInlineAction(form, event.submitter);
                }
            });
        });
        window.addEventListener('pageshow', function () {
            document.querySelectorAll('form[data-submitting]').forEach(function (form) {
                resetForm(form);
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
