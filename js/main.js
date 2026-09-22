(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var menuToggle = document.querySelector('[data-menu-toggle]');
        var siteMenu = document.getElementById('site-menu');
        if (menuToggle && siteMenu) {
            menuToggle.addEventListener('click', function () {
                var isOpen = siteMenu.classList.toggle('is-open');
                menuToggle.setAttribute('aria-expanded', String(isOpen));
                menuToggle.setAttribute('aria-label', isOpen ? 'Đóng menu' : 'Mở menu');
            });
        }

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

        document.querySelectorAll('[data-newsletter-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                form.hidden = true;
                var success = form.parentElement.querySelector('[data-newsletter-success]');
                if (success) success.hidden = false;
            });
        });

        document.querySelectorAll('[data-auto-dismiss]').forEach(function (note) {
            window.setTimeout(function () { note.remove(); }, 5000);
        });

        var toast = null;
        function showToast(message) {
            if (toast) toast.remove();
            toast = document.createElement('div');
            toast.className = 'admin-toast';
            toast.setAttribute('role', 'status');
            toast.textContent = message;
            document.body.appendChild(toast);
            window.setTimeout(function () { if (toast) toast.remove(); }, 2800);
        }

        document.querySelectorAll('[data-admin-action]').forEach(function (button) {
            button.addEventListener('click', function () { showToast(button.getAttribute('data-admin-action')); });
        });

        document.querySelectorAll('[data-admin-nav]').forEach(function (button) {
            button.addEventListener('click', function () {
                document.querySelectorAll('[data-admin-nav]').forEach(function (item) { item.classList.remove('is-active'); });
                button.classList.add('is-active');
                var section = button.getAttribute('data-admin-nav');
                var target = document.querySelector('[data-admin-section="' + section + '"]');
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                else showToast('Khu vực ' + button.textContent.trim() + ' đang được chuẩn bị.');
            });
        });

        var publishToggle = document.querySelector('[data-publish-toggle]');
        var publishLabel = document.querySelector('[data-publish-label]');
        if (publishToggle && publishLabel) {
            publishToggle.addEventListener('change', function () {
                publishLabel.textContent = publishToggle.checked ? 'Đang xuất bản' : 'Bản nháp';
                showToast(publishToggle.checked ? 'Bài viết đã được bật xuất bản.' : 'Bài viết đã chuyển về bản nháp.');
            });
        }
    });
})();
