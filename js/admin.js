(function () {
    'use strict';

    var sidebar = document.getElementById('admin-sidebar');
    var content = document.getElementById('admin-content');
    var toggle = document.querySelector('[data-admin-menu-toggle]');
    var close = document.querySelector('[data-admin-menu-close]');
    var backdrop = document.querySelector('[data-admin-backdrop]');
    var desktop = window.matchMedia('(min-width: 1024px)');

    function setMenu(open) {
        var wasOpen = sidebar.dataset.open === 'true';
        var modal = open && !desktop.matches;
        sidebar.dataset.open = String(modal);
        sidebar.inert = !open && !desktop.matches;
        content.inert = modal;
        backdrop.hidden = !modal;
        toggle.setAttribute('aria-expanded', String(modal));
        document.body.classList.toggle('overflow-hidden', modal);
        if (modal) close.focus();
        else if (wasOpen && !desktop.matches) toggle.focus();
    }

    if (sidebar && content && toggle && close && backdrop) {
        toggle.addEventListener('click', function () { setMenu(sidebar.dataset.open !== 'true'); });
        close.addEventListener('click', function () { setMenu(false); });
        backdrop.addEventListener('click', function () { setMenu(false); });
        desktop.addEventListener('change', function () { setMenu(false); });
        document.addEventListener('keydown', function (event) {
            if (desktop.matches || sidebar.dataset.open !== 'true') return;
            if (event.key === 'Escape') {
                event.preventDefault();
                setMenu(false);
            } else if (event.key === 'Tab') {
                var links = sidebar.querySelectorAll('a[href], button');
                var first = links[0];
                var last = links[links.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });
        setMenu(false);
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
            form.querySelectorAll('button[type="submit"]').forEach(function (button) {
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
    document.querySelectorAll('[data-auto-dismiss]').forEach(function (note) {
        window.setTimeout(function () { note.remove(); }, 5000);
    });
}());
