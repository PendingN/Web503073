// Live search for the blog: no "Search" button. 300ms after the reader stops typing (or right
// away when the sort changes or Enter is pressed) the page is fetched again with the form's
// query, and every [data-live-region] block is swapped for the server's fresh copy. The server
// searches title, content and topic names, so results match a full page load exactly.
(function () {
    'use strict';

    var DELAY = 300;
    var form = document.querySelector('form[data-live-search]');
    if (!form || !window.fetch || !window.DOMParser) return;

    var input = form.querySelector('input[name="search"]');
    var sort = form.querySelector('select[name="sort"]');
    var status = document.getElementById('blog-search-status');
    var results = document.getElementById('blog-results');
    var timer = null;
    var controller = null;
    var lastUrl = location.href.split('#')[0];

    function buildUrl() {
        var params = new URLSearchParams();
        new FormData(form).forEach(function (value, key) {
            var text = String(value).trim();
            // Mirror the server defaults so the address bar stays short.
            if ((key === 'search' && text === '') || (key === 'sort' && text === 'new') || (key === 'view' && text === 'all')) return;
            params.append(key, text);
        });
        var query = params.toString();
        var url = new URL(form.getAttribute('action').split('#')[0], location.href);
        url.search = query;
        return url.href;
    }

    function run() {
        clearTimeout(timer);
        var url = buildUrl();
        if (url === lastUrl) return;
        lastUrl = url;
        if (controller) controller.abort();
        controller = new AbortController();
        if (results) results.setAttribute('aria-busy', 'true');
        fetch(url, { signal: controller.signal, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } })
            .then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                document.querySelectorAll('[data-live-region]').forEach(function (region) {
                    var fresh = doc.getElementById(region.id);
                    if (fresh) region.replaceWith(document.importNode(fresh, true));
                });
                results = document.getElementById('blog-results');
                history.replaceState(null, '', url + '#blog-content');
                var count = document.querySelector('#blog-results-head .all-section-count');
                if (status && count) status.textContent = count.textContent.trim();
            })
            .catch(function (error) {
                if (error.name === 'AbortError') return;
                // Fall back to a normal page load so the reader still gets results.
                location.assign(url + '#blog-content');
            })
            .finally(function () {
                if (results) results.removeAttribute('aria-busy');
            });
    }

    function schedule() {
        clearTimeout(timer);
        timer = setTimeout(run, DELAY);
    }

    if (input) input.addEventListener('input', schedule);
    if (sort) sort.addEventListener('change', run);
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        run();
    });
})();
